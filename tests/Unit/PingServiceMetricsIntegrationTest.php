<?php

namespace Tests\Unit;

use App\Services\PingService;
use PHPUnit\Framework\TestCase;

class PingServiceMetricsIntegrationTest extends TestCase
{
    public function test_ping_retorna_metricas_avancadas(): void
    {
        $saida = implode("\n", [
            '64 bytes from 1.1.1.1: icmp_seq=1 ttl=56 time=10.0 ms',
            '64 bytes from 1.1.1.1: icmp_seq=2 ttl=56 time=20.0 ms',
            '64 bytes from 1.1.1.1: icmp_seq=3 ttl=56 time=15.0 ms',
            '',
            '3 packets transmitted, 3 received, 0% packet loss',
            'rtt min/avg/max/mdev = 10.000/15.000/20.000/4.082 ms',
        ]);

        $servico = new PingService(
            executor: fn ($processo) => [
                'saida' => $saida,
                'codigo' => 0,
            ]
        );

        $resultado = $servico->executar('1.1.1.1');

        $this->assertSame('sucesso', $resultado['status']);
        $this->assertSame(15.0, $resultado['latencia_media_ms']);
        $this->assertSame(10.0, $resultado['latencia_minima_ms']);
        $this->assertSame(20.0, $resultado['latencia_maxima_ms']);
        $this->assertSame(7.5, $resultado['jitter_ms']);
        $this->assertSame(3, $resultado['amostras']);
    }
}
