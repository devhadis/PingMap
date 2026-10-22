<?php

namespace Tests\Unit;

use App\Services\DnssecValidadorService;
use PHPUnit\Framework\TestCase;

class DnssecRespostasContraditoriasTest extends TestCase
{
    public function test_respostas_contraditorias_sao_indeterminadas(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->interpretarSaida(
            "; fully validated\n; unsigned answer",
            0
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_erro_operacional_no_stderr_nao_e_seguro(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            function (array $comando): array {
                return [
                    'saida' => '; fully validated',
                    'erro' => 'network unreachable',
                    'codigo' => 0,
                ];
            }
        );

        $this->assertSame('indeterminado', $resultado);
    }
}
