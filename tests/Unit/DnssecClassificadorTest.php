<?php

namespace Tests\Unit;

use App\Services\DnssecClassificador;
use PHPUnit\Framework\TestCase;

class DnssecClassificadorTest extends TestCase
{
    public function test_validacao_criptografica_aprovada(): void
    {
        $servico = new DnssecClassificador();

        $resultado = $servico->classificar(
            validacao: 'validado',
            possuiDs: true,
            possuiDnskey: true
        );

        $this->assertSame('seguro', $resultado);
    }

    public function test_registros_publicados_nao_comprovam_seguranca(): void
    {
        $servico = new DnssecClassificador();

        $resultado = $servico->classificar(
            validacao: 'indeterminada',
            possuiDs: true,
            possuiDnskey: true
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_falha_criptografica_e_classificada_como_bogus(): void
    {
        $servico = new DnssecClassificador();

        $resultado = $servico->classificar(
            validacao: 'falhou',
            possuiDs: true,
            possuiDnskey: true
        );

        $this->assertSame('bogus', $resultado);
    }

    public function test_ausencia_de_registros_nao_prova_inseguranca(): void
    {
        $servico = new DnssecClassificador();

        $resultado = $servico->classificar(
            validacao: 'indeterminada',
            possuiDs: false,
            possuiDnskey: false
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_resolvedor_confirma_delegacao_insegura(): void
    {
        $servico = new DnssecClassificador();

        $resultado = $servico->classificar(
            validacao: 'inseguro',
            possuiDs: false,
            possuiDnskey: false
        );

        $this->assertSame('inseguro', $resultado);
    }
}
