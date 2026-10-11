<?php

namespace App\Services;

use Closure;
use InvalidArgumentException;

class TcpService
{
    private const PORTAS_PERMITIDAS = [80, 443];

    private const REDES_BLOQUEADAS = [
        ['0.0.0.0', 8],
        ['10.0.0.0', 8],
        ['100.64.0.0', 10],
        ['127.0.0.0', 8],
        ['169.254.0.0', 16],
        ['172.16.0.0', 12],
        ['192.0.0.0', 24],
        ['192.0.2.0', 24],
        ['192.88.99.0', 24],
        ['192.168.0.0', 16],
        ['198.18.0.0', 15],
        ['198.51.100.0', 24],
        ['203.0.113.0', 24],
        ['224.0.0.0', 4],
        ['240.0.0.0', 4],
    ];

    public function __construct(
        private ?Closure $conector = null
    ) {}

    public function validarEntrada(
        string $destino,
        int $porta
    ): array {
        $this->validarDestino($destino);

        if (!in_array($porta, self::PORTAS_PERMITIDAS, true)) {
            throw new InvalidArgumentException(
                'Porta TCP não permitida.'
            );
        }

        return [
            'destino' => $destino,
            'porta' => $porta,
        ];
    }

    public function verificar(string $destino, int $porta): array
    {
        $this->validarEntrada($destino, $porta);

        $endpoint = "tcp://{$destino}:{$porta}";
        $timeout = 2.0;

        $inicio = hrtime(true);

        if ($this->conector !== null) {
            $conexao = ($this->conector)($endpoint, $timeout);
        } else {
            $codigo = 0;
            $mensagem = '';

            $socket = @stream_socket_client(
                $endpoint,
                $codigo,
                $mensagem,
                $timeout,
                STREAM_CLIENT_CONNECT
            );

            $conexao = [
                'conectado' => $socket !== false,
                'codigo' => $codigo,
            ];

            if (is_resource($socket)) {
                fclose($socket);
            }
        }

        $tempoMs = round(
            (hrtime(true) - $inicio) / 1_000_000,
            3
        );

        $codigo = (int) ($conexao['codigo'] ?? 0);

        $status = match (true) {
            $conexao['conectado'] === true => 'conectado',
            in_array($codigo, [111, 61, 10061], true) => 'recusado',
            in_array($codigo, [110, 60, 10060], true) => 'timeout',
            default => 'erro',
        };

        return [
            'destino' => $destino,
            'porta' => $porta,
            'protocolo' => 'TCP',
            'status' => $status,
            'tempo_ms' => $tempoMs,
        ];
    }

    private function validarDestino(string $destino): void
    {
        if (filter_var(
            $destino,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 |
            FILTER_FLAG_NO_PRIV_RANGE |
            FILTER_FLAG_NO_RES_RANGE
        ) === false) {
            throw new InvalidArgumentException(
                'Destino IPv4 público inválido ou não permitido.'
            );
        }

        $ip = ip2long($destino);

        foreach (self::REDES_BLOQUEADAS as [$rede, $prefixo]) {
            $mascara = (0xFFFFFFFF << (32 - $prefixo))
                & 0xFFFFFFFF;

            if (($ip & $mascara) ===
                (ip2long($rede) & $mascara)) {
                throw new InvalidArgumentException(
                    'Destino não permitido.'
                );
            }
        }
    }
}
