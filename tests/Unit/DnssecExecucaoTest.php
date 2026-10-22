<?php

namespace Tests\Unit;

use App\Services\DnssecValidadorService;
use PHPUnit\Framework\TestCase;

class DnssecExecucaoTest extends TestCase
{
    public function test_executa_validacao_segura(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            function (array $comando): array {
                $this->assertSame('/usr/bin/delv', $comando[0]);
                $this->assertSame('cloudflare.com', $comando[2]);

                return [
                    'saida' => "; fully validated\ncloudflare.com. IN A 1.1.1.1",
                    'erro' => '',
                    'codigo' => 0,
                ];
            }
        );

        $this->assertSame('seguro', $resultado);
    }

    public function test_executa_resposta_insegura(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'google.com',
            fn (array $comando): array => [
                'saida' => '; unsigned answer',
                'erro' => '',
                'codigo' => 0,
            ]
        );

        $this->assertSame('inseguro', $resultado);
    }

    public function test_falha_no_stderr_nao_e_segura(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            fn (array $comando): array => [
                'saida' => '; fully validated',
                'erro' => ';; resolution failed: failure',
                'codigo' => 0,
            ]
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_codigo_de_erro_nao_e_seguro(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            fn (array $comando): array => [
                'saida' => '; fully validated',
                'erro' => '',
                'codigo' => 1,
            ]
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_rejeita_dominio_interno_antes_de_executar(): void
    {
        $executou = false;
        $servico = new DnssecValidadorService();

        try {
            $servico->executar(
                'servidor.local',
                function (array $comando) use (&$executou): array {
                    $executou = true;
                    return [];
                }
            );

            $this->fail('Domínio interno deveria ser rejeitado.');
        } catch (\InvalidArgumentException $erro) {
            $this->assertFalse($executou);
        }
    }
}
