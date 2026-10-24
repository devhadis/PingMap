<?php

namespace App\Services;

use UnexpectedValueException;

class HttpTlsIpConectadoValidador
{
    public function validar(
        array $ipsAprovados,
        array $estatisticas
    ): string {
        // 1. Obter o IP realmente conectado.
        $ipReal = $estatisticas['primary_ip'] ?? null;

        if (
            ! is_string($ipReal)
            || filter_var($ipReal, FILTER_VALIDATE_IP) === false
        ) {
            throw new UnexpectedValueException(
                'IP da conexão HTTPS ausente ou inválido.'
            );
        }

        // 2. Comparar endereços IPv4 e IPv6.
        $ipBinario = inet_pton($ipReal);

        if ($ipBinario === false) {
            throw new UnexpectedValueException(
                'Não foi possível validar o IP conectado.'
            );
        }

        $autorizado = false;

        foreach ($ipsAprovados as $ipAprovado) {
            if (
                ! is_string($ipAprovado)
                || filter_var(
                    $ipAprovado,
                    FILTER_VALIDATE_IP
                ) === false
            ) {
                continue;
            }

            if (inet_pton($ipAprovado) === $ipBinario) {
                $autorizado = true;
                break;
            }
        }

        if (! $autorizado) {
            throw new UnexpectedValueException(
                'O IP conectado não pertence à lista aprovada.'
            );
        }

        // 3. Exigir conexão na porta HTTPS.
        if (
            ($estatisticas['primary_port'] ?? null) !== 443
        ) {
            throw new UnexpectedValueException(
                'A conexão não utilizou a porta HTTPS 443.'
            );
        }

        // 4. Exigir verificação TLS sem erros.
        if (
            ($estatisticas['ssl_verify_result'] ?? null) !== 0
        ) {
            throw new UnexpectedValueException(
                'A verificação TLS não foi confirmada.'
            );
        }

        // 5. Retornar o IP efetivamente conectado.
        return $ipReal;
    }
}
