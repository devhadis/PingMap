<?php

namespace Tests\Unit;

use App\Services\PingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class PingServiceTest extends TestCase
{
    public function test_rejeita_destino_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PingService())->executar('192.168.1.1; comando');
    }

    public function test_aceita_endereco_ipv4_valido(): void
    {
        $ping = $this->servicoSimulado();

        $this->assertIsArray($ping->executar('1.1.1.1'));
    }

    public function test_rejeita_endereco_loopback(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PingService())->executar('127.0.0.1');
    }

    public function test_rejeita_enderecos_privados(): void
    {
        foreach (['192.168.1.1', '10.0.0.1', '172.16.0.1'] as $ip) {
            try {
                (new PingService())->executar($ip);
                $this->fail("IP deveria ser rejeitado: {$ip}");
            } catch (InvalidArgumentException $e) {
                $this->assertSame(
                    'Destino não permitido.',
                    $e->getMessage()
                );
            }
        }
    }

    public function test_ping_retorna_resultado_estruturado(): void
    {
        $resultado = $this->servicoSimulado()->executar('1.1.1.1');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('destino', $resultado);
        $this->assertArrayHasKey('perda_percentual', $resultado);
        $this->assertArrayHasKey('latencia_media_ms', $resultado);
    }

    public function test_ping_extrai_metricas_de_sucesso(): void
    {
        $saida = "3 packets transmitted, 3 received, 0% packet loss\n"
            . "rtt min/avg/max/mdev = 10.000/20.500/30.000/5.000 ms\n";

        $resultado = $this->servicoSimulado($saida)->executar('1.1.1.1');

        $this->assertSame('sucesso', $resultado['status']);
        $this->assertSame(0.0, $resultado['perda_percentual']);
        $this->assertSame(20.5, $resultado['latencia_media_ms']);
    }

    public function test_ping_trata_perda_total(): void
    {
        $saida = "3 packets transmitted, 0 received, 100% packet loss\n";

        $resultado = $this->servicoSimulado($saida, 1)
            ->executar('1.1.1.1');

        $this->assertSame('sem_resposta', $resultado['status']);
        $this->assertSame(100.0, $resultado['perda_percentual']);
        $this->assertNull($resultado['latencia_media_ms']);
    }

    public function test_ping_configura_limites_de_execucao(): void
    {
        $executor = function ($processo) {
            $this->assertSame(10.0, $processo->getTimeout());

            $comando = $processo->getCommandLine();

            $this->assertStringContainsString('-c', $comando);
            $this->assertStringContainsString('-W', $comando);
            $this->assertStringContainsString('1.1.1.1', $comando);

            return ['saida' => '0% packet loss', 'codigo' => 0];
        };

        (new PingService($executor))->executar('1.1.1.1');
    }

    public function test_ping_trata_timeout(): void
    {
        $executor = function ($processo) {
            throw new ProcessTimedOutException(
                $processo,
                ProcessTimedOutException::TYPE_GENERAL
            );
        };

        $resultado = (new PingService($executor))->executar('1.1.1.1');

        $this->assertSame('timeout', $resultado['status']);
        $this->assertNull($resultado['perda_percentual']);
        $this->assertNull($resultado['latencia_media_ms']);
    }

    private function servicoSimulado(
        string $saida = '3 packets transmitted, 3 received, 0% packet loss',
        int $codigo = 0
    ): PingService {
        return new PingService(
            fn ($processo) => [
                'saida' => $saida,
                'codigo' => $codigo,
            ]
        );
    }
}
