<?php

namespace Tests\Feature;

use App\Services\GeoIpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoIpCacheSegurancaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();

        Http::preventStrayRequests();
    }

    private function respostaValida(): array
    {
        return [
            'success' => true,
            'ip' => '1.1.1.1',
            'country' => 'Australia',
            'city' => 'Sydney',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
        ];
    }

    public function test_ignora_coordenadas_invalidas_no_cache(): void
    {
        Cache::put('pingmap:geoip:v1:1.1.1.1', [
            'ip' => '1.1.1.1',
            'latitude' => 999,
            'longitude' => 999,
        ], 3600);

        Http::fake([
            'https://ipwho.is/*' => Http::response(
                $this->respostaValida()
            ),
        ]);

        $resultado = app(GeoIpService::class)->localizar('1.1.1.1');

        $this->assertSame(-33.8688, $resultado['latitude']);
        $this->assertSame(151.2093, $resultado['longitude']);

        Http::assertSentCount(1);
    }

    public function test_ignora_ip_divergente_no_cache(): void
    {
        Cache::put('pingmap:geoip:v1:1.1.1.1', [
            'ip' => '8.8.8.8',
            'latitude' => 10,
            'longitude' => 20,
        ], 3600);

        Http::fake([
            'https://ipwho.is/*' => Http::response(
                $this->respostaValida()
            ),
        ]);

        $resultado = app(GeoIpService::class)->localizar('1.1.1.1');

        $this->assertSame('1.1.1.1', $resultado['ip']);
        $this->assertSame(-33.8688, $resultado['latitude']);

        Http::assertSentCount(1);
    }

    public function test_nao_aceita_redirecionamento_do_provedor(): void
    {
        Http::fake([
            'https://ipwho.is/*' => Http::response(
                '',
                302,
                ['Location' => 'https://example.com/']
            ),
        ]);

        $resultado = app(GeoIpService::class)->localizar('1.1.1.1');

        $this->assertNull($resultado);
        Http::assertSentCount(1);
    }
}
