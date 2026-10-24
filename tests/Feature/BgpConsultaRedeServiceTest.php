<?php

namespace Tests\Feature;

use App\Services\BgpConsultaRedeService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;
use UnexpectedValueException;

class BgpConsultaRedeServiceTest extends TestCase
{
    public function test_consulta_ipv4_publico(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => '8.8.8.0/24',
                    'asns' => ['15169'],
                ],
            ], 200),
        ]);

        $resultado = (new BgpConsultaRedeService())
            ->consultar('8.8.8.8');

        $this->assertSame([
            'tipo' => 'ip',
            'consulta' => '8.8.8.8',
            'prefixo' => '8.8.8.0/24',
            'anunciado' => true,
            'asns' => [15169],
            'coberto_por_prefixo_maior' => false,
        ], $resultado);

        Http::assertSent(function (Request $request) {
            parse_str(
                (string) parse_url(
                    $request->url(),
                    PHP_URL_QUERY
                ),
                $parametros
            );

            return parse_url(
                $request->url(),
                PHP_URL_PATH
            ) === '/data/network-info/data.json'
                && ($parametros['resource'] ?? null)
                    === '8.8.8.8';
        });
    }

    public function test_ipv6_com_multiplas_origens(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => '2606:4700:4700::/48',
                    'asns' => ['13335', '15169'],
                ],
            ], 200),
        ]);

        $resultado = (new BgpConsultaRedeService())
            ->consultar('2606:4700:4700::1111');

        $this->assertSame([13335, 15169], $resultado['asns']);
        $this->assertTrue($resultado['anunciado']);
        $this->assertSame('ip', $resultado['tipo']);
    }

    public function test_ip_sem_anuncio_bgp(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => null,
                    'asns' => [],
                ],
            ], 200),
        ]);

        $resultado = (new BgpConsultaRedeService())
            ->consultar('8.8.8.8');

        $this->assertNull($resultado['prefixo']);
        $this->assertFalse($resultado['anunciado']);
        $this->assertSame([], $resultado['asns']);
    }

    public function test_consulta_cidr_anunciado(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'resource' => '8.8.8.0/24',
                    'announced' => true,
                    'asns' => [
                        [
                            'asn' => 15169,
                            'holder' => 'GOOGLE',
                        ],
                    ],
                    'is_less_specific' => false,
                ],
            ], 200),
        ]);

        $resultado = (new BgpConsultaRedeService())
            ->consultar('8.8.8.0/24');

        $this->assertSame([
            'tipo' => 'cidr',
            'consulta' => '8.8.8.0/24',
            'prefixo' => '8.8.8.0/24',
            'anunciado' => true,
            'asns' => [15169],
            'coberto_por_prefixo_maior' => false,
        ], $resultado);

        Http::assertSent(function (Request $request) {
            parse_str(
                (string) parse_url(
                    $request->url(),
                    PHP_URL_QUERY
                ),
                $parametros
            );

            return parse_url(
                $request->url(),
                PHP_URL_PATH
            ) === '/data/prefix-overview/data.json'
                && ($parametros['resource'] ?? null)
                    === '8.8.8.0/24';
        });
    }

    public function test_identifica_cobertura_por_prefixo_maior(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'resource' => '8.8.8.128/25',
                    'announced' => true,
                    'asns' => [
                        ['asn' => 15169],
                    ],
                    'is_less_specific' => true,
                ],
            ], 200),
        ]);

        $resultado = (new BgpConsultaRedeService())
            ->consultar('8.8.8.128/25');

        $this->assertTrue(
            $resultado['coberto_por_prefixo_maior']
        );
    }

    public function test_rejeita_ip_privado(): void
    {
        Http::fake();

        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpConsultaRedeService())
            ->consultar('192.168.1.1');
    }

    public function test_rejeita_resposta_invalida(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => '8.8.8.0/24',
                    'asns' => '15169',
                ],
            ], 200),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new BgpConsultaRedeService())
            ->consultar('8.8.8.8');
    }

    public function test_rejeita_prefixo_de_outro_ip(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => '1.1.1.0/24',
                    'asns' => ['15169'],
                ],
            ], 200),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new BgpConsultaRedeService())
            ->consultar('8.8.8.8');
    }

    public function test_trata_erro_http_da_ripestat(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        $this->expectException(
            RuntimeException::class
        );

        (new BgpConsultaRedeService())
            ->consultar('8.8.8.8');
    }
}
