<?php

namespace Tests\Feature;

use App\Services\BgpResolucaoDominioService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class BgpDnsIntegracaoTest extends TestCase
{
    public function test_resolvedor_padrao_utiliza_dns_sobre_https(): void
    {
        Http::fake(function (Request $request) {
            parse_str(
                (string) parse_url($request->url(), PHP_URL_QUERY),
                $parametros
            );

            if (($parametros['type'] ?? '') === 'A') {
                return Http::response([
                    'Status' => 0,
                    'Answer' => [
                        [
                            'type' => 1,
                            'data' => '1.1.1.1',
                        ],
                    ],
                ], 200);
            }

            return Http::response([
                'Status' => 0,
                'Answer' => [
                    [
                        'type' => 28,
                        'data' => '2606:4700:4700::1111',
                    ],
                ],
            ], 200);
        });

        $resultado = (new BgpResolucaoDominioService())
            ->resolver('example.com');

        $this->assertSame(
            'example.com',
            $resultado['dominio']
        );

        $this->assertSame([
            '1.1.1.1',
            '2606:4700:4700::1111',
        ], $resultado['ips']);

        Http::assertSentCount(2);
    }

    public function test_resolvedor_padrao_trata_falha_doh(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        $this->expectException(RuntimeException::class);

        (new BgpResolucaoDominioService())
            ->resolver('example.com');
    }

    public function test_resolvedor_padrao_rejeita_ip_privado_do_doh(): void
    {
        Http::fake(function (Request $request) {
            parse_str(
                (string) parse_url($request->url(), PHP_URL_QUERY),
                $parametros
            );

            if (($parametros['type'] ?? '') === 'A') {
                return Http::response([
                    'Status' => 0,
                    'Answer' => [
                        [
                            'type' => 1,
                            'data' => '192.168.1.10',
                        ],
                    ],
                ], 200);
            }

            return Http::response([
                'Status' => 0,
            ], 200);
        });

        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpResolucaoDominioService())
            ->resolver('example.com');
    }
}
