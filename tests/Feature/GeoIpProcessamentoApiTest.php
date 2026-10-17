<?php

namespace Tests\Feature;

use App\Models\User;
use App\Jobs\ExecutarGeoIpSaltos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GeoIpProcessamentoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
        Queue::fake();
        Http::preventStrayRequests();
    }

    public function test_traceroute_concluido_solicita_geo_assincrono(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $this->actingAs($usuario)
            ->getJson(
                "/api/v1/traceroute/consultas/{$consulta->id}/geo"
            )
            ->assertStatus(202)
            ->assertJsonPath('status', 'pendente');

        Queue::assertPushed(ExecutarGeoIpSaltos::class, 1);
        Http::assertNothingSent();
    }

    public function test_mtr_concluido_solicita_geo_assincrono(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $this->actingAs($usuario)
            ->getJson(
                "/api/v1/mtr/consultas/{$consulta->id}/geo"
            )
            ->assertStatus(202)
            ->assertJsonPath('status', 'pendente');

        Queue::assertPushed(ExecutarGeoIpSaltos::class, 1);
        Http::assertNothingSent();
    }

    public function test_consulta_repetida_nao_enfileira_job_duplicado(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [['salto' => 1, 'ip' => '1.1.1.1']],
        ]);

        $url = "/api/v1/traceroute/consultas/{$consulta->id}/geo";

        $this->actingAs($usuario);

        $this->getJson($url)->assertStatus(202);
        $this->getJson($url)->assertStatus(202);

        Queue::assertPushed(ExecutarGeoIpSaltos::class, 1);
        Http::assertNothingSent();
    }

    public function test_consulta_geo_concluida_retorna_coordenadas(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [['salto' => 1, 'ip' => '1.1.1.1']],
        ]);

        $chave = ExecutarGeoIpSaltos::chave(
            'mtr',
            $consulta->id,
            $usuario->id
        );

        Cache::put($chave, [
            'status' => 'concluido',
            'saltos' => [
                [
                    'salto' => 1,
                    'ip' => '1.1.1.1',
                    'geo' => [
                        'ip' => '1.1.1.1',
                        'latitude' => -27.47,
                        'longitude' => 153.03,
                    ],
                ],
            ],
        ], now()->addMinutes(10));

        $this->actingAs($usuario)
            ->getJson("/api/v1/mtr/consultas/{$consulta->id}/geo")
            ->assertOk()
            ->assertJsonPath('status', 'concluido')
            ->assertJsonPath('saltos.0.geo.latitude', -27.47)
            ->assertJsonPath('saltos.0.geo.longitude', 153.03);

        Queue::assertNotPushed(ExecutarGeoIpSaltos::class);
        Http::assertNothingSent();
    }

    public function test_erro_geo_retorna_mensagem_generica(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [],
        ]);

        $chave = ExecutarGeoIpSaltos::chave(
            'traceroute',
            $consulta->id,
            $usuario->id
        );

        Cache::put($chave, [
            'status' => 'erro',
            'message' => 'Não foi possível processar a geolocalização.',
        ], now()->addMinutes(2));

        $this->actingAs($usuario)
            ->getJson(
                "/api/v1/traceroute/consultas/{$consulta->id}/geo"
            )
            ->assertStatus(503)
            ->assertJsonPath('status', 'erro');

        Queue::assertNotPushed(ExecutarGeoIpSaltos::class);
        Http::assertNothingSent();
    }


    public function test_controller_envia_identificador_da_reserva_ao_job(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $this->actingAs($usuario)
            ->getJson("/api/v1/mtr/consultas/{$consulta->id}/geo")
            ->assertStatus(202);

        $chave = ExecutarGeoIpSaltos::chave(
            'mtr',
            $consulta->id,
            $usuario->id
        );

        $reserva = Cache::get($chave);

        $this->assertSame('pendente', $reserva['status']);

        $this->assertIsString($reserva['tentativa'] ?? null);

        $this->assertNotEmpty($reserva['tentativa']);

        Queue::assertPushed(
            ExecutarGeoIpSaltos::class,
            function ($job) use ($reserva): bool {
                return $job->tentativa === $reserva['tentativa'];
            }
        );

        Http::assertNothingSent();
    }


    public function test_api_nao_expoe_identificador_interno_da_tentativa(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $chave = ExecutarGeoIpSaltos::chave(
            'mtr',
            $consulta->id,
            $usuario->id
        );

        Cache::put($chave, [
            'status' => 'pendente',
            'tentativa' => 'identificador-interno',
        ], now()->addMinutes(10));

        $this->actingAs($usuario)
            ->getJson("/api/v1/mtr/consultas/{$consulta->id}/geo")
            ->assertStatus(202)
            ->assertJsonPath('status', 'pendente')
            ->assertJsonMissingPath('tentativa');

        $this->assertSame(
            'identificador-interno',
            Cache::get($chave)['tentativa']
        );
    }

}
