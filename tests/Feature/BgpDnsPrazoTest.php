<?php

namespace Tests\Feature;

use App\Services\BgpDnsConsultaProtegida;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class BgpDnsPrazoTest extends TestCase
{
    public function test_nao_inicia_dns_com_prazo_esgotado(): void
    {
        $tempo = 100.0;

        $relogio = function () use (&$tempo): float {
            return $tempo;
        };

        Http::fake([
            '*' => Http::response(['Status' => 0], 200),
        ]);

        $erro = null;

        try {
            (new BgpDnsConsultaProtegida($relogio))
                ->consultar('dns.google', 100.0);
        } catch (RuntimeException $excecao) {
            $erro = $excecao;
        }

        $this->assertNotNull($erro);

        $this->assertStringContainsString(
            'tempo',
            strtolower($erro->getMessage())
        );

        Http::assertNothingSent();
    }

    public function test_nao_inicia_aaaa_se_prazo_acabar_apos_a(): void
    {
        $tempo = 100.0;

        $relogio = function () use (&$tempo): float {
            return $tempo;
        };

        Http::fake(function (Request $request) use (&$tempo) {
            // Simula que a primeira consulta consumiu 13 segundos.
            $tempo += 13.0;

            return Http::response([
                'Status' => 0,
                'Answer' => [
                    [
                        'type' => 1,
                        'data' => '8.8.8.8',
                    ],
                ],
            ], 200);
        });

        $erro = null;

        try {
            (new BgpDnsConsultaProtegida($relogio))
                ->consultar('dns.google', 112.0);
        } catch (RuntimeException $excecao) {
            $erro = $excecao;
        }

        $this->assertNotNull($erro);

        $this->assertStringContainsString(
            'tempo',
            strtolower($erro->getMessage())
        );

        // A segunda requisição DNS não deve começar.
        Http::assertSentCount(1);
    }

    public function test_consulta_dns_normal_com_prazo(): void
    {
        $tempo = 100.0;

        $relogio = function () use (&$tempo): float {
            return $tempo;
        };

        Http::fake(function (Request $request) {
            parse_str(
                (string) parse_url(
                    $request->url(),
                    PHP_URL_QUERY
                ),
                $parametros
            );

            if (($parametros['type'] ?? '') === 'A') {
                return Http::response([
                    'Status' => 0,
                    'Answer' => [
                        [
                            'type' => 1,
                            'data' => '8.8.8.8',
                        ],
                    ],
                ], 200);
            }

            return Http::response([
                'Status' => 0,
                'Answer' => [
                    [
                        'type' => 28,
                        'data' => '2001:4860:4860::8888',
                    ],
                ],
            ], 200);
        });

        $resultado = (
            new BgpDnsConsultaProtegida($relogio)
        )->consultar('dns.google', 112.0);

        $this->assertSame([
            ['type' => 'A', 'ip' => '8.8.8.8'],
            [
                'type' => 'AAAA',
                'ipv6' => '2001:4860:4860::8888',
            ],
        ], $resultado);

        Http::assertSentCount(2);
    }
}
