<?php

namespace App\Services;

use InvalidArgumentException;

class TracerouteService
{
    public function validarDestino(string $destino): string
    {
        return (new EnderecoIpPublico())->validar($destino);
    }

    public function montarComando(string $destino): array
    {
        $destino = $this->validarDestino($destino);

        $familia = filter_var(
            $destino,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV6
        ) !== false ? '-6' : '-4';

        return [
            '/usr/bin/traceroute',
            $familia,
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
                '/^(\S+)\s+(\d+(?:\.\d+)?)\s+ms\b/',
                $conteudo,
                $dados
            )) {
                continue;
            }

            if (filter_var($dados[1], FILTER_VALIDATE_IP) === false) {
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
