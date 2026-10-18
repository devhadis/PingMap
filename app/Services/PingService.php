<?php

namespace App\Services;

use Closure;
use InvalidArgumentException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PingService
{
    public function __construct(
        private ?Closure $executor = null
    ) {}

    public function executar(string $destino): array
    {
        if (filter_var($destino, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException('Destino inválido.');
        }

        try {
            (new EnderecoIpPublico())->validar($destino);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException('Destino não permitido.', 0, $e);
        }

        $familia = filter_var(
            $destino,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV6
        ) !== false ? '-6' : '-4';

        $processo = new Process(
            ['/usr/bin/ping', $familia, '-n', '-c', '3', '-W', '2', '--', $destino],
            null,
            ['LC_ALL' => 'C']
        );

        $processo->setTimeout(10);

        try {
            if ($this->executor !== null) {
                $dados = ($this->executor)($processo);
            } else {
                $processo->run();

                $dados = [
                    'saida' => $processo->getOutput(),
                    'codigo' => $processo->getExitCode(),
                ];
            }
        } catch (ProcessTimedOutException $e) {
            return [
                'destino' => $destino,
                'perda_percentual' => null,
                'latencia_media_ms' => null,
                'latencia_minima_ms' => null,
                'latencia_maxima_ms' => null,
                'jitter_ms' => null,
                'amostras' => 0,
                'status' => 'timeout',
            ];
        }

        $saida = $dados['saida'];
        $codigo = $dados['codigo'];

        $metricasAvancadas = (new PingMetricsParser())->analisar($saida);

        $perda = null;
        $latencia = null;

        if (preg_match(
            '/(\d+(?:\.\d+)?)%\s+packet loss/',
            $saida,
            $resultado
        )) {
            $perda = (float) $resultado[1];
        }

        if (preg_match(
            '/(?:rtt|round-trip) min\/avg\/max\/(?:mdev|stddev)\s*=\s*[\d.]+\/([\d.]+)/',
            $saida,
            $resultado
        )) {
            $latencia = (float) $resultado[1];
        }

        $status = match (true) {
            $codigo === 0 => 'sucesso',
            $codigo === 1 && $metricasAvancadas['amostras'] > 0 => 'sucesso',
            $codigo === 1 => 'sem_resposta',
            default => 'erro',
        };

        return [
            'destino' => $destino,
            'perda_percentual' => $perda,
            'latencia_media_ms' => $latencia,
            ...$metricasAvancadas,
            'status' => $status,
        ];
    }
}
