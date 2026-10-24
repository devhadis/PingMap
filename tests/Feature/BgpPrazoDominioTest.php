<?php

namespace Tests\Feature;

use App\Services\BgpConsultaRedeService;
use App\Services\BgpResolucaoDominioService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class BgpPrazoDominioTest extends TestCase
{
    public function test_nao_consulta_ripestat_se_prazo_acabar_no_dns(): void
    {
        $tempo = 100.0;

        $dns = new BgpResolucaoDominioService(
            function (string $dominio, int $tipos) use (&$tempo): array {
                // Simula DNS que consumiu 13 segundos.
                $tempo += 13.0;

                return [
                    ['type' => 'A', 'ip' => '8.8.8.8'],
                ];
            }
        );

        $relogio = function () use (&$tempo): float {
            return $tempo;
        };

        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => '8.8.8.0/24',
                    'asns' => ['15169'],
                ],
            ], 200),
        ]);

        $servico = new BgpConsultaRedeService(
            $dns,
            $relogio
        );

        try {
            $servico->consultar('example.com');

            $this->fail(
                'A consulta deveria exceder o prazo.'
            );
        } catch (RuntimeException $erro) {
            $this->assertStringContainsString(
                'tempo',
                strtolower($erro->getMessage())
            );
        }

        // Nenhuma chamada externa deve ser iniciada.
        Http::assertNothingSent();
    }

    public function test_nao_inicia_segundo_ip_apos_prazo(): void
    {
        $tempo = 100.0;
        $chamadas = 0;

        $dns = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => [
                ['type' => 'A', 'ip' => '8.8.8.8'],
                ['type' => 'A', 'ip' => '8.8.8.9'],
            ]
        );

        $relogio = function () use (&$tempo): float {
            return $tempo;
        };

        Http::fake(
            function (Request $request) use (
                &$tempo,
                &$chamadas
            ) {
                $chamadas++;

                if ($chamadas === 1) {
                    // A primeira requisição consumiu 13 segundos.
                    $tempo += 13.0;
                }

                return Http::response([
                    'status' => 'ok',
                    'data' => [
                        'prefix' => '8.8.8.0/24',
                        'asns' => ['15169'],
                    ],
                ], 200);
            }
        );

        $servico = new BgpConsultaRedeService(
            $dns,
            $relogio
        );

        try {
            $servico->consultar('example.com');

            $this->fail(
                'A consulta deveria exceder o prazo.'
            );
        } catch (RuntimeException $erro) {
            $this->assertStringContainsString(
                'tempo',
                strtolower($erro->getMessage())
            );
        }

        // Apenas o primeiro IP pode ser consultado.
        Http::assertSentCount(1);
    }

    public function test_consulta_normal_continua_funcionando(): void
    {
        $tempo = 100.0;

        $dns = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => [
                ['type' => 'A', 'ip' => '8.8.8.8'],
            ]
        );

        $relogio = function () use (&$tempo): float {
            return $tempo;
        };

        Http::fake([
            '*' => Http::response([
                'status' => 'ok',
                'data' => [
                    'prefix' => '8.8.8.0/24',
                    'asns' => ['15169'],
                ],
            ], 200),
        ]);

        $resultado = (new BgpConsultaRedeService(
            $dns,
            $relogio
        ))->consultar('example.com');

        $this->assertSame(
            'dominio',
            $resultado['tipo']
        );

        $this->assertCount(
            1,
            $resultado['resultados']
        );

        Http::assertSentCount(1);
    }
}
