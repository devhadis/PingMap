<?php

namespace Tests\Unit;

use App\Services\MtrService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MtrServiceTest extends TestCase
{
    public function test_aceita_ipv4_publico(): void
    {
        $servico = new MtrService();

        $this->assertSame(
            '1.1.1.1',
            $servico->validarDestino('1.1.1.1')
        );
    }

    public function test_rejeita_ip_privado(): void
    {
        $servico = new MtrService();

        $this->expectException(InvalidArgumentException::class);

        $servico->validarDestino('192.168.1.1');
    }

    public function test_rejeita_injecao_de_comando(): void
    {
        $servico = new MtrService();

        $this->expectException(InvalidArgumentException::class);

        $servico->validarDestino('1.1.1.1;whoami');
    }

    public function test_monta_comando_com_limites(): void
    {
        $servico = new MtrService();

        $this->assertSame(
            [
                '/usr/bin/mtr',
                '--report',
                '--json',
                '--no-dns',
                '--report-cycles',
                '3',
                '--max-ttl',
                '12',
                '--',
                '1.1.1.1',
            ],
            $servico->montarComando('1.1.1.1')
        );
    }

    public function test_rejeita_destino_local(): void
    {
        $servico = new MtrService();

        $this->expectException(InvalidArgumentException::class);

        $servico->validarDestino('127.0.0.1');
    }
}
