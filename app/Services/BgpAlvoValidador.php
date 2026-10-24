<?php

namespace App\Services;

use InvalidArgumentException;

class BgpAlvoValidador
{
    public function normalizar(string $entrada): array
    {
        $valor = trim($entrada);

        if ($valor === '' || strlen($valor) > 253) {
            throw new InvalidArgumentException(
                'Alvo BGP inválido.'
            );
        }

        // Primeiro: verificar se é um endereço IP.
        if (filter_var($valor, FILTER_VALIDATE_IP) !== false) {
            $ip = (new EnderecoIpPublico())
                ->validar($valor);

            return [
                'tipo' => 'ip',
                'valor' => $ip,
            ];
        }

        // Segundo: verificar se é uma rede CIDR.
        if (str_contains($valor, '/')) {
            $partes = explode('/', $valor);

            if (count($partes) !== 2) {
                throw new InvalidArgumentException(
                    'Rede CIDR inválida.'
                );
            }

            [$ip, $mascara] = $partes;

            if (
                filter_var($ip, FILTER_VALIDATE_IP) === false ||
                ! preg_match(
                    '/\A(?:0|[1-9][0-9]{0,2})\z/',
                    $mascara
                )
            ) {
                throw new InvalidArgumentException(
                    'Rede CIDR inválida.'
                );
            }

            $prefixo = (int) $mascara;

            (new EnderecoIpPublico())
                ->validarFaixa($ip, $prefixo);

            // Verifica se existem bits de host ativos.
            $binario = inet_pton($ip);

            $bytesRede = intdiv($prefixo, 8);
            $bitsRede = $prefixo % 8;

            // Verifica os bits restantes do byte parcial.
            if ($bitsRede > 0) {
                $mascaraHost = (1 << (8 - $bitsRede)) - 1;

                if (
                    (ord($binario[$bytesRede]) & $mascaraHost) !== 0
                ) {
                    throw new InvalidArgumentException(
                        'CIDR deve começar no endereço da rede.'
                    );
                }

                $bytesRede++;
            }

            // Todos os bytes de host devem ser zero.
            $restante = substr($binario, $bytesRede);

            if (
                $restante !==
                str_repeat(chr(0), strlen($restante))
            ) {
                throw new InvalidArgumentException(
                    'CIDR deve começar no endereço da rede.'
                );
            }

            return [
                'tipo' => 'cidr',
                'valor' => $ip . '/' . $prefixo,
            ];
        }

        // Terceiro: verificar se é um domínio.
        $dominio = strtolower($valor);

        $formatoValido = preg_match(
            '/\A(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?\z/',
            $dominio
        );

        $sufixoReservado = preg_match(
            '/\.(?:local|localhost|internal|test|invalid|example|lan|home|onion)\z/',
            $dominio
        );

        if (! $formatoValido || $sufixoReservado) {
            throw new InvalidArgumentException(
                'Domínio inválido.'
            );
        }

        return [
            'tipo' => 'dominio',
            'valor' => $dominio,
        ];
    }
}
