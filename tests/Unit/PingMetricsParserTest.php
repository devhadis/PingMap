<?php

namespace Tests\Unit;

use App\Services\PingMetricsParser;
use PHPUnit\Framework\TestCase;

class PingMetricsParserTest extends TestCase
{
    public function test_calcula_metricas_de_tres_respostas(): void
    {
        $saida = implode("\n", [
            '64 bytes from 1.1.1.1: icmp_seq=1 ttl=56 time=10.0 ms',
            '64 bytes from 1.1.1.1: icmp_seq=2 ttl=56 time=20.0 ms',
            '64 bytes from 1.1.1.1: icmp_seq=3 ttl=56 time=15.0 ms',
        ]);

        $resultado = (new PingMetricsParser())->analisar($saida);

        $this->assertSame(10.0, $resultado['latencia_minima_ms']);
        $this->assertSame(20.0, $resultado['latencia_maxima_ms']);
        $this->assertSame(7.5, $resultado['jitter_ms']);
        $this->assertSame(3, $resultado['amostras']);
    }

    public function test_sem_resposta_retorna_metricas_nulas(): void
    {
        $resultado = (new PingMetricsParser())->analisar(
            '100% packet loss'
        );

        $this->assertNull($resultado['latencia_minima_ms']);
        $this->assertNull($resultado['latencia_maxima_ms']);
        $this->assertNull($resultado['jitter_ms']);
        $this->assertSame(0, $resultado['amostras']);
    }

    public function test_uma_resposta_nao_tem_jitter(): void
    {
        $resultado = (new PingMetricsParser())->analisar(
            '64 bytes from 1.1.1.1: icmp_seq=1 time=12.5 ms'
        );

        $this->assertSame(12.5, $resultado['latencia_minima_ms']);
        $this->assertSame(12.5, $resultado['latencia_maxima_ms']);
        $this->assertNull($resultado['jitter_ms']);
        $this->assertSame(1, $resultado['amostras']);
    }
}
