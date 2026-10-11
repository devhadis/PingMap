<?php

namespace Tests\Unit;

use App\Services\TcpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TcpServiceTest extends TestCase
{
    public function test_conexao_tcp_com_sucesso(): void
    {
        $tcp = new TcpService(
            fn ($endpoint, $timeout) => [
                'conectado' => true,
                'codigo' => 0,
            ]
        );

        $resultado = $tcp->verificar('1.1.1.1', 443);

        $this->assertSame('conectado', $resultado['status']);
        $this->assertSame('1.1.1.1', $resultado['destino']);
        $this->assertSame(443, $resultado['porta']);
        $this->assertIsNumeric($resultado['tempo_ms']);
    }

    public function test_conexao_recusada(): void
    {
        $tcp = new TcpService(
            fn ($endpoint, $timeout) => [
                'conectado' => false,
                'codigo' => 111,
            ]
        );

        $resultado = $tcp->verificar('1.1.1.1', 443);

        $this->assertSame('recusado', $resultado['status']);
    }

    public function test_conexao_com_timeout(): void
    {
        $tcp = new TcpService(
            fn ($endpoint, $timeout) => [
                'conectado' => false,
                'codigo' => 110,
            ]
        );

        $resultado = $tcp->verificar('1.1.1.1', 443);

        $this->assertSame('timeout', $resultado['status']);
    }

    public function test_rejeita_ip_privado(): void
    {
        $tcp = new TcpService();

        $this->expectException(InvalidArgumentException::class);

        $tcp->verificar('192.168.1.1', 443);
    }

    public function test_rejeita_ip_de_documentacao(): void
    {
        $tcp = new TcpService();

        $this->expectException(InvalidArgumentException::class);

        $tcp->verificar('192.0.2.10', 443);
    }

    public function test_rejeita_porta_nao_autorizada(): void
    {
        $tcp = new TcpService();

        $this->expectException(InvalidArgumentException::class);

        $tcp->verificar('1.1.1.1', 25);
    }

    public function test_rejeita_entrada_com_comando(): void
    {
        $tcp = new TcpService();

        $this->expectException(InvalidArgumentException::class);

        $tcp->verificar('1.1.1.1;whoami', 443);
    }
}
