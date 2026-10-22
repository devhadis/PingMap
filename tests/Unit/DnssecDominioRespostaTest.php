<?php

namespace Tests\Unit;

use App\Services\DnssecValidadorService;
use PHPUnit\Framework\TestCase;

class DnssecDominioRespostaTest extends TestCase
{
    public function test_resposta_de_outro_dominio_nao_e_segura(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            function (array $comando): array {
                return [
                    'saida' => "; fully validated\n"
                        . "exemplo.com. 300 IN A 203.0.113.10",
                    'erro' => '',
                    'codigo' => 0,
                ];
            }
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_resposta_sem_registros_nao_e_segura(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            function (array $comando): array {
                return [
                    'saida' => '; fully validated',
                    'erro' => '',
                    'codigo' => 0,
                ];
            }
        );

        $this->assertSame('indeterminado', $resultado);
    }
}
