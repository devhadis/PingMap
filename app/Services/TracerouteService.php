<?php

namespace App\Services;

use InvalidArgumentException;

class TracerouteService
{
    private const FAIXAS_BLOQUEADAS = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
    ];

    public function validarDestino(string $destino): string
    {
        $valido = filter_var(
            $destino,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE
        );

        if ($valido === false) {
            throw new InvalidArgumentException(
                'O destino deve ser um endereço IPv4 público válido.'
            );
        }

        foreach (self::FAIXAS_BLOQUEADAS as $faixa) {
            if ($this->pertenceAFaixa($destino, $faixa)) {
                throw new InvalidArgumentException(
                    'O destino pertence a uma faixa de IP não permitida.'
                );
            }
        }

        return $destino;
    }

    private function pertenceAFaixa(string $ip, string $cidr): bool
    {
        [$rede, $prefixo] = explode('/', $cidr);

        $ipBinario = inet_pton($ip);
        $redeBinaria = inet_pton($rede);

        $bits = (int) $prefixo;
        $bytes = intdiv($bits, 8);
        $restante = $bits % 8;

        if (substr($ipBinario, 0, $bytes) !== substr($redeBinaria, 0, $bytes)) {
            return false;
        }

        if ($restante === 0) {
            return true;
        }

        $mascara = (0xFF << (8 - $restante)) & 0xFF;

        return (ord($ipBinario[$bytes]) & $mascara)
            === (ord($redeBinaria[$bytes]) & $mascara);
    }

    public function montarComando(string $destino): array
    {
        $destino = $this->validarDestino($destino);

        return [
            '/usr/bin/traceroute',
            '-4',
            '-n',
            '-m',
            '12',
            '-q',
            '1',
            '-w',
            '1',
            $destino,
        ];
    }

    public function interpretarSaida(string $saida): array
    {
        $saltos = [];

        foreach (preg_split('/\r\n|\r|\n/', $saida) as $linha) {
            if (!preg_match('/^\s*(\d+)\s+(.+)$/', $linha, $partes)) {
                continue;
            }

            $numero = (int) $partes[1];

            if ($numero < 1 || $numero > 12) {
                continue;
            }

            $conteudo = trim($partes[2]);

            if ($conteudo === '*') {
                $saltos[] = [
                    'salto' => $numero,
                    'ip' => null,
                    'latencia_ms' => null,
                ];

                continue;
            }

            if (!preg_match(
                '/^(\d{1,3}(?:\.\d{1,3}){3})\s+(\d+(?:\.\d+)?)\s+ms\b/',
                $conteudo,
                $dados
            )) {
                continue;
            }

            if (filter_var($dados[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                continue;
            }

            $saltos[] = [
                'salto' => $numero,
                'ip' => $dados[1],
                'latencia_ms' => (float) $dados[2],
            ];
        }

        return $saltos;
    }


    public function executar(
        string $destino,
        ?\Symfony\Component\Process\Process $processo = null
    ): array {
        $comando = $this->montarComando($destino);

        $processo ??= new \Symfony\Component\Process\Process($comando);

        $processo->setTimeout(20);

        try {
            $processo->run();
        } catch (
            \Symfony\Component\Process\Exception\ProcessTimedOutException $e
        ) {
            throw new \RuntimeException(
                'O Traceroute ultrapassou o tempo máximo de execução.',
                0,
                $e
            );
        } catch (
            \Symfony\Component\Process\Exception\ProcessFailedException $e
        ) {
            throw new \RuntimeException(
                'Falha ao executar o Traceroute.',
                0,
                $e
            );
        }

        if (!$processo->isSuccessful()) {
            throw new \RuntimeException(
                'O processo Traceroute não foi concluído com sucesso.'
            );
        }

        return $this->interpretarSaida($processo->getOutput());
    }

}
