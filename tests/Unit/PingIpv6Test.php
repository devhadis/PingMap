<?php

namespace Tests\Unit;

use App\Services\PingService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PingIpv6Test extends TestCase
{
    public function test_executa_ipv4_com_familia_explicita(): void
    {
        $this->verificarComando(
            '1.1.1.1',
            '-4'
        );
    }

    public function test_executa_ipv6_com_familia_explicita(): void
    {
        $this->verificarComando(
            '2606:4700:4700::1111',
            '-6'
        );
    }

    public function test_ipv6_preserva_resultado_estruturado(): void
    {
        $servico = new PingService(
            executor: fn ($processo) => [
                'saida' => "3 packets transmitted, 3 received, 0% packet loss\n"
                    . "rtt min/avg/max/mdev = 10.000/20.000/30.000/5.000 ms\n",
                'codigo' => 0,
            ]
        );

        $resultado = $servico->executar('2606:4700:4700::1111');

        $this->assertSame('2606:4700:4700::1111', $resultado['destino']);
        $this->assertSame('sucesso', $resultado['status']);
        $this->assertSame(0.0, $resultado['perda_percentual']);
        $this->assertSame(20.0, $resultado['latencia_media_ms']);
    }

    #[DataProvider('destinosBloqueados')]
    public function test_rejeita_ipv6_nao_permitido(string $destino): void
    {
        $executou = false;

        $servico = new PingService(
            executor: function ($processo) use (&$executou) {
                $executou = true;

                return ['saida' => '', 'codigo' => 0];
            }
        );

        try {
            $servico->executar($destino);

            $this->fail("Destino deveria ser bloqueado: {$destino}");
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Destino não permitido.', $e->getMessage());
        }

        $this->assertFalse($executou);
    }

    public static function destinosBloqueados(): array
    {
        return [
            'loopback' => ['::1'],
            'privado' => ['fc00::1'],
            'link local' => ['fe80::1'],
            'documentacao' => ['2001:db8::1'],
            'teredo' => ['2001::1'],
            '6to4' => ['2002::1'],
            'ipv4 mapeado' => ['::ffff:127.0.0.1'],
        ];
    }

    private function verificarComando(
        string $destino,
        string $familia
    ): void {
        $executor = function ($processo) use ($destino, $familia) {
            $comando = $processo->getCommandLine();

            $this->assertStringContainsString(
                $familia,
                $comando
            );

            $this->assertStringContainsString(
                $destino,
                $comando
            );

            $this->assertSame(10.0, $processo->getTimeout());

            return [
                'saida' => '3 packets transmitted, 3 received, 0% packet loss',
                'codigo' => 0,
            ];
        };

        $resultado = (new PingService($executor))->executar($destino);

        $this->assertSame('sucesso', $resultado['status']);
    }
}
