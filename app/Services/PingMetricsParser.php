<?php

namespace App\Services;

class PingMetricsParser
{
    public function analisar(string $saida): array
    {
        $tempos = [];

        preg_match_all(
            '/(?:^|\s)time[=<]([\d.]+)\s*ms\b/mi',
            $saida,
            $correspondencias
        );

        foreach ($correspondencias[1] as $valor) {
            $numero = (float) $valor;

            if (is_finite($numero) && $numero >= 0) {
                $tempos[] = $numero;
            }
        }

        if (count($tempos) === 0) {
            return [
                'latencia_minima_ms' => null,
                'latencia_maxima_ms' => null,
                'jitter_ms' => null,
                'amostras' => 0,
            ];
        }

        $variacoes = [];

        for ($i = 1; $i < count($tempos); $i++) {
            $variacoes[] = abs(
                $tempos[$i] - $tempos[$i - 1]
            );
        }

        $jitter = count($variacoes) > 0
            ? array_sum($variacoes) / count($variacoes)
            : null;

        return [
            'latencia_minima_ms' => round(min($tempos), 3),
            'latencia_maxima_ms' => round(max($tempos), 3),
            'jitter_ms' => $jitter === null
                ? null
                : round($jitter, 3),
            'amostras' => count($tempos),
        ];
    }
}
