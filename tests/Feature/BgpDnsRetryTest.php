<?php

namespace Tests\Feature;

use App\Services\BgpDnsConsultaProtegida;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class BgpDnsRetryTest extends TestCase
{
    public function test_repete_consulta_apos_falha_temporaria(): void
    {
        $chamadas = 0;

        Http::fake(function () use (&$chamadas) {
            $chamadas++;

            // Primeira tentativa: simular timeout.
            if ($chamadas === 1) {
                throw new ConnectionException(
                    'cURL error 28: Operation timed out'
                );
            }

            // Segunda tentativa: DNS IPv4 funciona.
            if ($chamadas === 2) {
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

            // Consulta IPv6 funciona normalmente.
            return Http::response([
                'Status' => 0,
                'Answer' => [],
            ], 200);
        });

        $servico = new BgpDnsConsultaProtegida();

        $resultado = $servico->consultar('dns.google');

        $this->assertSame([
            [
                'type' => 'A',
                'ip' => '8.8.8.8',
            ],
        ], $resultado);

        // 2 tentativas IPv4 + 1 consulta IPv6.
        $this->assertSame(3, $chamadas);
    }

    public function test_nao_repete_indefinidamente(): void
    {
        $chamadas = 0;

        Http::fake(function () use (&$chamadas) {
            $chamadas++;

            throw new ConnectionException(
                'cURL error 28: Operation timed out'
            );
        });

        $servico = new BgpDnsConsultaProtegida();

        try {
            $servico->consultar('dns.google');

            $this->fail(
                'Era esperado um erro de conexão DNS.'
            );
        } catch (RuntimeException $erro) {
            $this->assertSame(
                'Falha na conexão com o serviço DNS.',
                $erro->getMessage()
            );

            // Nunca permitir tentativas ilimitadas.
            $this->assertSame(2, $chamadas);
        }
    }

    public function test_nao_repete_quando_prazo_termina(): void
    {
        $agora = 100.0;
        $chamadas = 0;

        Http::fake(function () use (&$agora, &$chamadas) {
            $chamadas++;

            // Simular que o tempo acabou na primeira tentativa.
            $agora = 103.0;

            throw new ConnectionException(
                'cURL error 28: Operation timed out'
            );
        });

        $servico = new BgpDnsConsultaProtegida(
            static function () use (&$agora): float {
                return $agora;
            }
        );

        try {
            $servico->consultar('dns.google', 103.0);

            $this->fail(
                'Era esperado um erro de prazo esgotado.'
            );
        } catch (RuntimeException $erro) {
            $this->assertSame(
                'Tempo limite da consulta DNS excedido.',
                $erro->getMessage()
            );

            // Não iniciar outra requisição após o prazo.
            $this->assertSame(1, $chamadas);
        }
    }
}
