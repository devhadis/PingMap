<?php

namespace Tests\Feature;

use App\Services\GeoIpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoIpCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();

        Http::preventStrayRequests();
    }

    private function resposta(string $ip): array
    {
        return [
            'success' => true,
            'ip' => $ip,
            'country' => 'Australia',
            'city' => 'Sydney',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
        ];
    }

    public function test_reutiliza_localizacao_do_mesmo_ip(): void
    {
        Http::fake([
            'https://ipwho.is/*' => Http::response(
                $this->resposta('1.1.1.1')
            ),
        ]);

        $servico = app(GeoIpService::class);

        $primeira = $servico->localizar('1.1.1.1');
        $segunda = $servico->localizar('1.1.1.1');

        $this->assertSame($primeira, $segunda);
        $this->assertNotNull($primeira);

        Http::assertSentCount(1);
    }

    public function test_ips_diferentes_usam_consultas_diferentes(): void
    {
        Http::fake([
            'https://ipwho.is/1.1.1.1' => Http::response(
                $this->resposta('1.1.1.1')
            ),
            'https://ipwho.is/8.8.8.8' => Http::response(
                $this->resposta('8.8.8.8')
            ),
        ]);

        $servico = app(GeoIpService::class);

        $primeiro = $servico->localizar('1.1.1.1');
        $segundo = $servico->localizar('8.8.8.8');

        $this->assertSame('1.1.1.1', $primeiro['ip']);
        $this->assertSame('8.8.8.8', $segundo['ip']);

        Http::assertSentCount(2);
    }

    public function test_falha_do_provedor_nao_e_armazenada(): void
    {
        Http::fake([
            'https://ipwho.is/*' => Http::sequence()
                ->push([], 503)
                ->push($this->resposta('1.1.1.1'), 200),
        ]);

        $servico = app(GeoIpService::class);

        $primeira = $servico->localizar('1.1.1.1');
        $segunda = $servico->localizar('1.1.1.1');

        $this->assertNull($primeira);
        $this->assertNotNull($segunda);

        Http::assertSentCount(2);
    }
}
