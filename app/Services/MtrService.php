<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use RuntimeException;

class MtrService
{
    public function __construct(
        private readonly TracerouteService $traceroute = new TracerouteService()
    ) {
    }

    public function validarDestino(string $destino): string
    {
        return $this->traceroute->validarDestino($destino);
    }

    public function montarComando(string $destino): array
    {
        $destino = $this->validarDestino($destino);

        return [
            '/usr/bin/mtr',
            '--report',
            '--json',
            '--no-dns',
            '--report-cycles',
            '3',
            '--max-ttl',
            '12',
            '--',
            $destino,
        ];
    }


    public function interpretarResultado(string $json): array
    {
        try {
            $dados = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $erro) {
            throw new RuntimeException('JSON inválido do MTR.', 0, $erro);
        }

        $hubs = $dados['report']['hubs'] ?? null;

        if (! is_array($hubs) || ! array_is_list($hubs) || $hubs === []) {
            throw new RuntimeException('Estrutura inválida do MTR.');
        }

        $resultado = [];

        foreach ($hubs as $hub) {
            if (! is_array($hub)
                || ! isset($hub['count'], $hub['Snt'], $hub['Loss%'])
                || ! is_numeric($hub['count'])
                || ! is_numeric($hub['Snt'])
                || ! is_numeric($hub['Loss%'])
            ) {
                throw new RuntimeException('Salto inválido do MTR.');
            }

            $host = $hub['host'] ?? null;

            $ip = is_string($host)
                && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                    ? $host
                    : null;

            $perda = (float) $hub['Loss%'];

            $resultado[] = [
                'salto' => (int) $hub['count'],
                'ip' => $ip,
                'perda_pct' => $perda,
                'enviados' => (int) $hub['Snt'],
                'ultima_ms' => $ip !== null && isset($hub['Last']) && is_numeric($hub['Last'])
                    ? (float) $hub['Last'] : null,
                'media_ms' => $ip !== null && isset($hub['Avg']) && is_numeric($hub['Avg'])
                    ? (float) $hub['Avg'] : null,
                'melhor_ms' => $ip !== null && isset($hub['Best']) && is_numeric($hub['Best'])
                    ? (float) $hub['Best'] : null,
                'pior_ms' => $ip !== null && isset($hub['Wrst']) && is_numeric($hub['Wrst'])
                    ? (float) $hub['Wrst'] : null,
            ];
        }

        return $resultado;
    }

    public function executar(string $destino): array
    {
        $comando = $this->montarComando($destino);

        $processo = new Process($comando);
        $processo->setTimeout(30);
        $processo->run();

        if (! $processo->isSuccessful()) {
            throw new RuntimeException('Falha ao executar MTR.');
        }

        return $this->interpretarResultado(
            $processo->getOutput()
        );
    }
}
