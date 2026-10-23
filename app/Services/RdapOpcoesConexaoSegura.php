<?php

namespace App\Services;

use UnexpectedValueException;

class RdapOpcoesConexaoSegura
{
    public function preparar(
        string $host,
        array $enderecos
    ): array {
        // Verificar se o servidor RDAP é autorizado.
        (new RdapDestinoSeguro())
            ->validar('https://' . $host);

        // Validar todos os endereços DNS.
        $enderecos = (new RdapResolucaoDnsSegura())
            ->validar($enderecos);

        // Exigir a extensão cURL.
        if (! extension_loaded('curl')) {
            throw new UnexpectedValueException(
                'Extensão cURL necessária para conexão RDAP segura.'
            );
        }

        $resolucoes = [];

        foreach ($enderecos as $ip) {
            // O cURL exige colchetes para IPv6.
            if (filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6
            )) {
                $ip = '[' . $ip . ']';
            }

            $resolucoes[] = $ip;
        }

        return [
            'curl' => [
                CURLOPT_RESOLVE => [
                    $host . ':443:' . implode(',', $resolucoes),
                ],
            ],

            'proxy' => '',
            'verify' => true,
            'allow_redirects' => false,
        ];
    }
}
