<?php

namespace Tests\Unit;

use App\Services\TracerouteService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TracerouteServiceTest extends TestCase
{
    public function test_aceita_ipv4_publico(): void
    {
        $service = new TracerouteService();

        $this->assertSame(
            '1.1.1.1',
            $service->validarDestino('1.1.1.1')
        );
    }

    #[DataProvider('destinosInvalidos')]
    public function test_rejeita_destinos_invalidos(string $destino): void
    {
        $service = new TracerouteService();

        $this->expectException(InvalidArgumentException::class);

        $service->validarDestino($destino);
    }

    public static function destinosInvalidos(): array
    {
        return [
            'loopback' => ['127.0.0.1'],
            'rede privada 10' => ['10.0.0.1'],
            'rede privada 172' => ['172.16.0.1'],
            'rede privada 192' => ['192.168.1.1'],
            'link local' => ['169.254.1.1'],
            'cgnat' => ['100.64.0.1'],
            'documentacao' => ['192.0.2.1'],
            'multicast' => ['224.0.0.1'],
            'hostname' => ['localhost'],
            'comando' => ['1.1.1.1; whoami'],
        ];
    }

    public function test_monta_comando_com_limites(): void
    {
        $service = new TracerouteService();

        $this->assertSame(
            [
                '/usr/bin/traceroute',
                '-4',
                '-n',
                '-m',
                '12',
                '-q',
                '1',
                '-w',
                '1',
                '1.1.1.1',
            ],
            $service->montarComando('1.1.1.1')
        );
    }

    public function test_interpreta_saltos_do_traceroute(): void
    {
        $service = new TracerouteService();

        $saida = "traceroute to 1.1.1.1\n"
            . " 1  203.0.113.1  1.234 ms\n"
            . " 2  1.1.1.1  12.500 ms\n";

        $this->assertSame(
            [
                [
                    'salto' => 1,
                    'ip' => '203.0.113.1',
                    'latencia_ms' => 1.234,
                ],
                [
                    'salto' => 2,
                    'ip' => '1.1.1.1',
                    'latencia_ms' => 12.5,
                ],
            ],
            $service->interpretarSaida($saida)
        );
    }

    public function test_interpreta_salto_sem_resposta(): void
    {
        $service = new TracerouteService();

        $this->assertSame(
            [
                [
                    'salto' => 1,
                    'ip' => null,
                    'latencia_ms' => null,
                ],
            ],
            $service->interpretarSaida(" 1  *\n")
        );
    }

    public function test_executa_traceroute_com_timeout_global(): void
    {
        $service = new TracerouteService();

        $processo = $this->createMock(
            \Symfony\Component\Process\Process::class
        );

        $processo->expects($this->once())
            ->method('setTimeout')
            ->with(20);

        $processo->expects($this->once())
            ->method('run')
            ->willReturn(0);

        $processo->method('isSuccessful')
            ->willReturn(true);

        $processo->method('getOutput')
            ->willReturn(" 1  1.1.1.1  12.500 ms\n");

        $resultado = $service->executar('1.1.1.1', $processo);

        $this->assertSame([
            [
                'salto' => 1,
                'ip' => '1.1.1.1',
                'latencia_ms' => 12.5,
            ],
        ], $resultado);
    }

    public function test_trata_falha_do_processo(): void
    {
        $service = new TracerouteService();

        $processo = $this->createMock(
            \Symfony\Component\Process\Process::class
        );

        $processo->method('run')->willReturn(1);
        $processo->method('isSuccessful')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $service->executar('1.1.1.1', $processo);
    }

    public function test_trata_timeout_do_processo(): void
    {
        $service = new TracerouteService();

        $processo = $this->createMock(
            \Symfony\Component\Process\Process::class
        );

        $processo->method('run')->willThrowException(
            new \Symfony\Component\Process\Exception\ProcessTimedOutException(
                new \Symfony\Component\Process\Process(['true']),
                \Symfony\Component\Process\Exception\ProcessTimedOutException::TYPE_GENERAL
            )
        );

        $this->expectException(\RuntimeException::class);

        $service->executar('1.1.1.1', $processo);
    }

}
