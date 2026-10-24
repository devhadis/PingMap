<?php

namespace Tests\Feature;

use App\Services\BgpConsultaRedeService;
use App\Services\BgpResolucaoDominioService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class BgpPrazoCompartilhadoTest extends TestCase
{
    public function test_resolvedor_repassa_prazo_ao_dns_https(): void
    {
        // Nenhuma requisição externa real será realizada.
        Http::fake([
            '*' => Http::response([
                'Status' => 0,
            ], 200),
        ]);

        // Um prazo que já terminou.
        $prazo = hrtime(true) / 1_000_000_000 - 1;

        $erro = null;

        try {
            (new BgpResolucaoDominioService())
                ->resolver('dns.google', $prazo);
        } catch (RuntimeException $excecao) {
            $erro = $excecao;
        }

        $this->assertNotNull($erro);

        $this->assertStringContainsString(
            'tempo',
            strtolower($erro->getMessage())
        );

        // Não pode iniciar nenhuma requisição HTTPS.
        Http::assertNothingSent();
    }

    public function test_bgp_envia_prazo_ao_resolvedor(): void
    {
        $tempo = 100.0;
        $prazoRecebido = null;

        $relogio = function () use (&$tempo): float {
            return $tempo;
        };

        // DNS simulado para verificar o prazo recebido.
        $resolvedor = new BgpResolucaoDominioService(
            function (
                string $dominio,
                int $tipos,
                ?float $prazo = null
            ) use (&$prazoRecebido): array {
                $prazoRecebido = $prazo;

                throw new RuntimeException(
                    'Interrupcao controlada.'
                );
            }
        );

        Http::fake();

        $erro = null;

        try {
            (new BgpConsultaRedeService(
                $resolvedor,
                $relogio
            ))->consultar('dns.google');
        } catch (RuntimeException $excecao) {
            $erro = $excecao;
        }

        $this->assertNotNull($erro);

        $this->assertSame(
            'Interrupcao controlada.',
            $erro->getMessage()
        );

        // Relógio 100 + orçamento de 12 segundos.
        $this->assertSame(
            112.0,
            $prazoRecebido
        );

        Http::assertNothingSent();
    }
}
