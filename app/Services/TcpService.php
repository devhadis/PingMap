<?php

namespace App\Services;

use Closure;
use InvalidArgumentException;

class TcpService
{
    private const PORTAS_PERMITIDAS = [80, 443];

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

        $host = filter_var(
            $destino,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV6
        ) !== false ? "[{$destino}]" : $destino;

        $endpoint = "tcp://{$host}:{$porta}";
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
        try {
            (new EnderecoIpPublico())->validar($destino);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                'Destino não permitido.',
                0,
                $e
            );
        }
    }
}
