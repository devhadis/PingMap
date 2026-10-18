<?php

namespace Tests\Feature;

use App\Jobs\ExecutarGeoIpSaltos;
use App\Models\User;
use App\Services\GeoIpSaltosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoIpIpv6IntegracaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_fluxo_ipv6_traceroute(): void
    {
        $this->verificarFluxo('traceroute');
    }

    public function test_fluxo_ipv6_mtr(): void
    {
        $this->verificarFluxo('mtr');
    }

    private function verificarFluxo(string $tipo): void
    {
        $ip = '2606:4700:4700::1111';
        $usuario = User::factory()->create();

        $saltos = [
            [
                'salto' => 1,
                'ip' => 'fe80::1',
                'media_ms' => 1.5,
            ],
            [
                'salto' => 2,
                'ip' => $ip,
                'media_ms' => 20.5,
            ],
        ];

        $relacao = $tipo === 'mtr'
            ? $usuario->mtrConsultas()
            : $usuario->tracerouteConsultas();

        $consulta = $relacao->create([
            'destino' => $ip,
            'status' => 'concluido',
            'saltos' => $saltos,
        ]);

        Http::fake([
            'https://ipwho.is/*' => Http::response([
                'success' => true,
                'ip' => $ip,
                'country' => 'Australia',
                'city' => 'Sydney',
                'latitude' => -33.86,
                'longitude' => 151.20,
            ]),
        ]);

        $chave = ExecutarGeoIpSaltos::chave(
            $tipo,
            $consulta->id,
            $usuario->id
        );

        $tentativa = 'teste-ipv6-' . $tipo;

        Cache::put($chave, [
            'status' => 'pendente',
            'tentativa' => $tentativa,
        ], now()->addMinutes(10));

        $job = new ExecutarGeoIpSaltos(
            $tipo,
            $consulta->id,
            $usuario->id,
            $tentativa
        );

        $job->handle(app(GeoIpSaltosService::class));

        $resultado = Cache::get($chave);

        $this->assertSame('concluido', $resultado['status']);

        $this->assertNull($resultado['saltos'][0]['geo']);

        $this->assertSame(
            $ip,
            $resultado['saltos'][1]['geo']['ip']
        );

        $this->assertSame(
            20.5,
            $resultado['saltos'][1]['media_ms']
        );

        $this->assertSame(
            $saltos,
            $consulta->fresh()->saltos
        );

        Http::assertSentCount(1);

        Http::assertSent(
            fn ($request) =>
                $request->url() === 'https://ipwho.is/' . $ip
        );


        $this->actingAs($usuario)
            ->getJson(
                "/api/v1/{$tipo}/consultas/{$consulta->id}/geo"
            )
            ->assertOk()
            ->assertJsonPath('status', 'concluido')
            ->assertJsonPath('saltos.1.ip', $ip)
            ->assertJsonPath('saltos.1.geo.ip', $ip)
            ->assertJsonPath(
                'saltos.1.geo.latitude',
                -33.86
            )
            ->assertJsonMissingPath('tentativa');
    }
}
