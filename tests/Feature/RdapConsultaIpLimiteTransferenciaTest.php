<?php

namespace Tests\Feature;

use App\Services\RdapLimiteTransferencia;
use Tests\TestCase;
use UnexpectedValueException;

class RdapConsultaIpLimiteTransferenciaTest extends TestCase
{
    public function test_aceita_transferencia_dentro_do_limite(): void
    {
        $limite = new RdapLimiteTransferencia();

        $resultado = $limite(
            2097152,
            2097152,
            0,
            0
        );

        $this->assertNull($resultado);
    }

    public function test_rejeita_bytes_recebidos_acima_do_limite(): void
    {
        $limite = new RdapLimiteTransferencia();

        $this->expectException(
            UnexpectedValueException::class
        );

        $limite(
            0,
            2097153,
            0,
            0
        );
    }

    public function test_rejeita_total_anunciado_acima_do_limite(): void
    {
        $limite = new RdapLimiteTransferencia();

        $this->expectException(
            UnexpectedValueException::class
        );

        $limite(
            2097153,
            0,
            0,
            0
        );
    }
}
