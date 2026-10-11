<?php

namespace Tests\Unit;

use App\Services\PingService;
use PHPUnit\Framework\TestCase;

class PingPerdaParcialTest extends TestCase
{
    public function test_perda_parcial_com_respostas_nao_e_sem_resposta(): void
    {
        $saida = implode("\n", [
            '64 bytes from 1.1.1.1: icmp_seq=1 ttl=53 time=31.8 ms',
            '64 bytes from 1.1.1.1: icmp_seq=2 ttl=53 time=60.6 ms',
            '3 packets transmitted, 2 received, 33.3333% packet loss',
            'rtt min/avg/max/mdev = 31.811/46.210/60.609/14.399 ms',
        ]);

        $ping = new PingService(
            executor: fn ($processo) => [
                'saida' => $saida,
                'codigo' => 1,
            ]
        );

        $resultado = $ping->executar('1.1.1.1');

        $this->assertSame('sucesso', $resultado['status']);
        $this->assertEqualsWithDelta(
            33.3333,
            $resultado['perda_percentual'],
            0.001
        );
        $this->assertSame(2, $resultado['amostras']);
        $this->assertSame(31.8, $resultado['latencia_minima_ms']);
        $this->assertSame(60.6, $resultado['latencia_maxima_ms']);
        $this->assertSame(28.8, $resultado['jitter_ms']);
    }
}
