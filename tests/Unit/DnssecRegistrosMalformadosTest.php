<?php

namespace Tests\Unit;

use App\Services\DnssecValidadorService;
use PHPUnit\Framework\TestCase;

class DnssecRegistrosMalformadosTest extends TestCase
{
    private function executarComSaida(string $saida): string
    {
        $servico = new DnssecValidadorService();

        return $servico->executar(
            'cloudflare.com',
            fn (array $comando): array => [
                'saida' => $saida,
                'erro' => '',
                'codigo' => 0,
            ]
        );
    }

    public function test_ipv4_invalido_nao_comprova_resposta(): void
    {
        $resultado = $this->executarComSaida(
            "; fully validated\n"
            . "cloudflare.com. 300 IN A 999.999.999.999"
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_cname_malformado_nao_comprova_resposta(): void
    {
        $resultado = $this->executarComSaida(
            "; fully validated\n"
            . "cloudflare.com. 300 IN CNAME ???"
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_cname_com_destino_valido_e_aceito(): void
    {
        $resultado = $this->executarComSaida(
            "; fully validated\n"
            . "cloudflare.com. 300 IN CNAME destino.exemplo.com.\n"
            . "destino.exemplo.com. 300 IN A 1.1.1.1"
        );

        $this->assertSame('seguro', $resultado);
    }
}
