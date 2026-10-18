<?php

namespace App\Services;

use InvalidArgumentException;

class EnderecoIpPublico
{
    private const IPV4_BLOQUEADOS = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
    ];

    private const IPV6_BLOQUEADOS = [
        '2001::/23',
        '2001:db8::/32',
        '2002::/16',
        '3fff::/20',
    ];

    public function validar(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException(
                'Endereço IP inválido.'
            );
        }

        $binario = inet_pton($ip);

        if ($binario === false) {
            throw new InvalidArgumentException(
                'Endereço IP inválido.'
            );
        }

        if (strlen($binario) === 4) {
            if (filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_GLOBAL_RANGE
            ) === false) {
                throw new InvalidArgumentException(
                    'Endereço IPv4 não permitido.'
                );
            }

            $bloqueados = self::IPV4_BLOQUEADOS;
        } else {
            // Permite somente IPv6 unicast global (2000::/3).
            if (! $this->pertenceAoCidr($binario, '2000::/3')) {
                throw new InvalidArgumentException(
                    'Endereço IPv6 não permitido.'
                );
            }

            $bloqueados = self::IPV6_BLOQUEADOS;
        }

        foreach ($bloqueados as $cidr) {
            if ($this->pertenceAoCidr($binario, $cidr)) {
                throw new InvalidArgumentException(
                    'Endereço IP pertence a uma faixa bloqueada.'
                );
            }
        }

        return $ip;
    }

    private function pertenceAoCidr(
        string $ipBinario,
        string $cidr
    ): bool {
        [$rede, $prefixo] = explode('/', $cidr);

        $redeBinaria = inet_pton($rede);

        if ($redeBinaria === false ||
            strlen($ipBinario) !== strlen($redeBinaria)) {
            return false;
        }

        $bits = (int) $prefixo;
        $bytes = intdiv($bits, 8);
        $restante = $bits % 8;

        if (substr($ipBinario, 0, $bytes) !==
            substr($redeBinaria, 0, $bytes)) {
            return false;
        }

        if ($restante === 0) {
            return true;
        }

        $mascara = (0xFF << (8 - $restante)) & 0xFF;

        return (ord($ipBinario[$bytes]) & $mascara) ===
            (ord($redeBinaria[$bytes]) & $mascara);
    }
}
