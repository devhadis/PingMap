<?php

namespace Tests\Feature;

use App\Services\GeoIpService;
use App\Services\GeoIpSaltosService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class GeoIpIpv6Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_localiza_ipv6_publico(): void
    {
        $ip = '2606:4700:4700::1111';

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

        $resultado = app(GeoIpService::class)->localizar($ip);

        $this->assertSame($ip, $resultado['ip']);
        $this->assertSame(-33.86, $resultado['latitude']);
        $this->assertSame(151.20, $resultado['longitude']);

        Http::assertSentCount(1);
    }

    public function test_rejeita_ipv6_privado_sem_http(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);

        try {
            app(GeoIpService::class)->localizar('fc00::1');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_rejeita_ipv6_link_local_sem_http(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);

        try {
            app(GeoIpService::class)->localizar('fe80::1');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_rejeita_resposta_com_ipv6_diferente(): void
    {
        Http::fake([
            'https://ipwho.is/*' => Http::response([
                'success' => true,
                'ip' => '2001:4860:4860::8888',
                'latitude' => 10,
                'longitude' => 20,
            ]),
        ]);

        $resultado = app(GeoIpService::class)
            ->localizar('2606:4700:4700::1111');

        $this->assertNull($resultado);
        Http::assertSentCount(1);
    }

    public function test_enriquece_salto_ipv6_publico(): void
    {
        $ip = '2606:4700:4700::1111';

        $geoIp = Mockery::mock(GeoIpService::class);

        $geoIp->shouldReceive('localizar')
            ->once()
            ->with($ip)
            ->andReturn([
                'ip' => $ip,
                'latitude' => -33.86,
                'longitude' => 151.20,
            ]);

        $resultado = (new GeoIpSaltosService($geoIp))
            ->enriquecer([
                [
                    'salto' => 1,
                    'ip' => $ip,
                    'media_ms' => 15.5,
                ],
            ]);

        $this->assertSame($ip, $resultado[0]['geo']['ip']);
        $this->assertSame(15.5, $resultado[0]['media_ms']);
    }

    public function test_nao_geolocaliza_salto_ipv6_local(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);

        $geoIp->shouldNotReceive('localizar');

        $resultado = (new GeoIpSaltosService($geoIp))
            ->enriquecer([
                [
                    'salto' => 1,
                    'ip' => 'fe80::1',
                    'media_ms' => 1.2,
                ],
            ]);

        $this->assertNull($resultado[0]['geo']);
        $this->assertSame(1.2, $resultado[0]['media_ms']);
    }

    public function test_reutiliza_cache_ipv6(): void
    {
        $ip = '2606:4700:4700::1111';

        Http::fake([
            'https://ipwho.is/*' => Http::response([
                'success' => true,
                'ip' => $ip,
                'latitude' => -33.86,
                'longitude' => 151.20,
            ]),
        ]);

        $servico = app(GeoIpService::class);

        $primeiro = $servico->localizar($ip);
        $segundo = $servico->localizar($ip);

        $this->assertSame($primeiro, $segundo);
        Http::assertSentCount(1);
    }

    public function test_ipv6_diferentes_usam_cache_separado(): void
    {
        $ip1 = '2606:4700:4700::1111';
        $ip2 = '2001:4860:4860::8888';

        Http::fake(function ($request) use ($ip1, $ip2) {
            $ip = str_ends_with($request->url(), $ip1)
                ? $ip1
                : $ip2;

            return Http::response([
                'success' => true,
                'ip' => $ip,
                'latitude' => 10,
                'longitude' => 20,
            ]);
        });

        $servico = app(GeoIpService::class);

        $this->assertSame($ip1, $servico->localizar($ip1)['ip']);
        $this->assertSame($ip2, $servico->localizar($ip2)['ip']);

        Http::assertSentCount(2);
    }

    public function test_rejeita_cache_ipv6_com_ip_divergente(): void
    {
        $ip = '2606:4700:4700::1111';

        Cache::put('pingmap:geoip:v1:' . $ip, [
            'ip' => '2001:4860:4860::8888',
            'latitude' => 10,
            'longitude' => 20,
        ], 3600);

        Http::fake([
            'https://ipwho.is/*' => Http::response([
                'success' => true,
                'ip' => $ip,
                'latitude' => -33.86,
                'longitude' => 151.20,
            ]),
        ]);

        $resultado = app(GeoIpService::class)->localizar($ip);

        $this->assertSame($ip, $resultado['ip']);
        $this->assertSame(-33.86, $resultado['latitude']);
        Http::assertSentCount(1);
    }

    public function test_nao_consulta_provedor_para_ipv6_reservado(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);

        try {
            app(GeoIpService::class)
                ->localizar('2001:db8::1');
        } finally {
            Http::assertNothingSent();
        }
    }
}
