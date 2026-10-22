<?php

namespace Tests\Unit;

use App\Services\DnssecValidadorService;
use PHPUnit\Framework\TestCase;

class DnssecValidadorServiceTest extends TestCase
{
    public function test_resposta_validada_e_segura(): void
    {
        $servico = new DnssecValidadorService();

        $this->assertSame(
            'seguro',
            $servico->interpretarSaida(
                "; fully validated\ncloudflare.com. IN A 104.16.132.229",
                0
            )
        );
    }

    public function test_resposta_sem_assinatura_e_insegura(): void
    {
        $servico = new DnssecValidadorService();

        $this->assertSame(
            'inseguro',
            $servico->interpretarSaida(
                "; unsigned answer\ngoogle.com. IN A 142.250.78.174",
                0
            )
        );
    }

    public function test_falha_de_resolucao_e_indeterminada(): void
    {
        $servico = new DnssecValidadorService();

        $this->assertSame(
            'indeterminado',
            $servico->interpretarSaida(
                ';; resolution failed: failure',
                0
            )
        );
    }

    public function test_saida_desconhecida_e_indeterminada(): void
    {
        $servico = new DnssecValidadorService();

        $this->assertSame(
            'indeterminado',
            $servico->interpretarSaida('resposta desconhecida', 0)
        );
    }

    public function test_processo_com_erro_nao_e_seguro(): void
    {
        $servico = new DnssecValidadorService();

        $this->assertSame(
            'indeterminado',
            $servico->interpretarSaida('; fully validated', 1)
        );
    }

    public function test_falha_prevalece_sobre_mensagem_de_validacao(): void
    {
        $servico = new DnssecValidadorService();

        $this->assertSame(
            'indeterminado',
            $servico->interpretarSaida(
                "; fully validated\n;; resolution failed: failure",
                0
            )
        );
    }
}
