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

    public function validarFaixa(
        string $ip,
        int $prefixo
    ): void {
        // Reutiliza a validação do endereço inicial.
        $this->validar($ip);

        $binario = inet_pton($ip);
        $ipv4 = strlen($binario) === 4;
        $limite = $ipv4 ? 32 : 128;

        if ($prefixo < 0 || $prefixo > $limite) {
            throw new InvalidArgumentException(
                'Máscara CIDR inválida.'
            );
        }

        // Uma rede IPv6 precisa permanecer em 2000::/3.
        if (! $ipv4 && $prefixo < 3) {
            throw new InvalidArgumentException(
                'Rede IPv6 não permitida.'
            );
        }

        $bloqueados = $ipv4
            ? self::IPV4_BLOQUEADOS
            : self::IPV6_BLOQUEADOS;

        foreach ($bloqueados as $cidr) {
            [$rede, $mascara] = explode('/', $cidr);

            // Duas redes se sobrepõem quando seus primeiros
            // bits coincidem até a menor das máscaras.
            $bits = min($prefixo, (int) $mascara);

            if ($this->pertenceAoCidr(
                $binario,
                $rede . '/' . $bits
            )) {
                throw new InvalidArgumentException(
                    'Rede CIDR inclui uma faixa bloqueada.'
                );
            }
        }
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
