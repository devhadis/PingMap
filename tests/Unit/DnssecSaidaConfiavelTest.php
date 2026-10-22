<?php

namespace Tests\Unit;

use App\Services\DnssecValidadorService;
use PHPUnit\Framework\TestCase;

class DnssecSaidaConfiavelTest extends TestCase
{
    public function test_mensagem_validada_apenas_no_stderr_nao_e_segura(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            function (array $comando): array {
                return [
                    'saida' => '',
                    'erro' => '; fully validated',
                    'codigo' => 0,
                ];
            }
        );

        $this->assertSame('indeterminado', $resultado);
    }
}
