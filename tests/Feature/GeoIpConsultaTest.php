<?php

namespace Tests\Feature;

use App\Services\GeoIpService;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class GeoIpConsultaTest extends TestCase
{
    public function test_localiza_ipv4_publico(): void
    {
        Http::fake([
            'https://ipwho.is/*' => Http::response([
                'success' => true,
                'ip' => '1.1.1.1',
                'country' => 'Australia',
                'country_code' => 'AU',
                'city' => 'Sydney',
                'latitude' => -33.8688,
                'longitude' => 151.2093,
                'connection' => [
                    'asn' => 13335,
                    'org' => 'Cloudflare',
                ],
            ], 200),
        ]);

        $resultado = app(GeoIpService::class)->localizar('1.1.1.1');

        $this->assertSame('1.1.1.1', $resultado['ip']);
        $this->assertSame('Australia', $resultado['pais']);
        $this->assertSame('Sydney', $resultado['cidade']);
        $this->assertSame(-33.8688, $resultado['latitude']);
        $this->assertSame(151.2093, $resultado['longitude']);
        $this->assertSame(13335, $resultado['asn']);

        Http::assertSentCount(1);
    }

    public function test_nao_consulta_ip_privado(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);

        try {
            app(GeoIpService::class)->localizar('192.168.1.1');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_retorna_null_quando_provedor_falha(): void
    {
        Http::fake([
            'https://ipwho.is/*' => Http::response([], 503),
        ]);

        $resultado = app(GeoIpService::class)->localizar('1.1.1.1');

        $this->assertNull($resultado);
    }

    public function test_retorna_null_para_coordenadas_invalidas(): void
    {
        Http::fake([
            'https://ipwho.is/*' => Http::response([
                'success' => true,
                'ip' => '1.1.1.1',
                'latitude' => 200,
                'longitude' => 300,
            ]),
        ]);

        $resultado = app(GeoIpService::class)->localizar('1.1.1.1');

        $this->assertNull($resultado);
    }

    public function test_nao_aceita_ip_diferente_na_resposta(): void
    {
        Http::fake([
            'https://ipwho.is/*' => Http::response([
                'success' => true,
                'ip' => '8.8.8.8',
                'latitude' => 10,
                'longitude' => 20,
            ]),
        ]);

        $resultado = app(GeoIpService::class)->localizar('1.1.1.1');

        $this->assertNull($resultado);
    }
}
