<?php

namespace App\Services;

use RuntimeException;

class HttpTlsOpcoesConexaoSegura
{
    public function preparar(
        string $entrada,
        array $enderecos
    ): array {
        // 1. Validar e normalizar o domínio.
        $dominio = (new HttpTlsAlvoValidador())
            ->validar($entrada);

        // 2. Rejeitar IPs privados, reservados e listas vazias.
        $ips = (new RdapResolucaoDnsSegura())
            ->validar($enderecos);

        // 3. Exigir cURL para fixar os IPs aprovados.
        if (
            ! extension_loaded('curl') ||
            ! defined('CURLOPT_RESOLVE')
        ) {
            throw new RuntimeException(
                'cURL é obrigatório para conexão HTTPS segura.'
            );
        }

        // 4. Preparar endereços IPv4 e IPv6.
        $resolucoes = [];

        foreach ($ips as $ip) {
            if (filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6
            )) {
                $ip = '[' . $ip . ']';
            }

            $resolucoes[] = $ip;
        }

        // 5. Configurar cURL para usar somente HTTPS.
        $curl = [
            CURLOPT_RESOLVE => [
                $dominio . ':443:' . implode(',', $resolucoes),
            ],
        ];

        return [
            'curl' => $curl,
            'protocols' => ['https'],
            'proxy' => '',
            'verify' => true,
            'allow_redirects' => false,
            'http_errors' => false,
            'connect_timeout' => 3,
            'timeout' => 8,
        ];
    }
}
