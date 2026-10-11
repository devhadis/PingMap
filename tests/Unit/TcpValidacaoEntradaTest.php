<?php

namespace Tests\Unit;

use App\Services\TcpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TcpValidacaoEntradaTest extends TestCase
{
    private function servicoSemRede(): TcpService
    {
        return new TcpService(
            function (): never {
                throw new RuntimeException(
                    'A validação tentou abrir uma conexão TCP.'
                );
            }
        );
    }

    public function test_entrada_valida_sem_conexao(): void
    {
        $tcp = $this->servicoSemRede();

        $resultado = $tcp->validarEntrada(
            '1.1.1.1',
            443
        );

        $this->assertSame([
            'destino' => '1.1.1.1',
            'porta' => 443,
        ], $resultado);
    }

    public function test_rejeita_ip_privado_sem_conexao(): void
    {
        $tcp = $this->servicoSemRede();

        $this->expectException(
            InvalidArgumentException::class
        );

        $tcp->validarEntrada(
            '192.168.1.1',
            443
        );
    }

    public function test_rejeita_porta_proibida_sem_conexao(): void
    {
        $tcp = $this->servicoSemRede();

        $this->expectException(
            InvalidArgumentException::class
        );

        $tcp->validarEntrada(
            '1.1.1.1',
            22
        );
    }
}
