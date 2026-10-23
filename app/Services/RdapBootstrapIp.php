<?php

namespace App\Services;

use UnexpectedValueException;

class RdapBootstrapIp
{
    public function localizar(string $ip, array $catalogo): string
    {
        $ip = (new EnderecoIpPublico())->validar($ip);
        $ipBinario = inet_pton($ip);

        $servicos = $catalogo['services'] ?? null;

        if (! is_array($servicos) || $servicos === []) {
            throw new UnexpectedValueException(
                'Catálogo RDAP vazio ou inválido.'
            );
        }

        $melhorPrefixo = -1;
        $melhorUrl = null;

        foreach ($servicos as $servico) {
            if (
                ! is_array($servico) ||
                ! isset($servico[0], $servico[1]) ||
                ! is_array($servico[0]) ||
                ! is_array($servico[1])
            ) {
                continue;
            }

            foreach ($servico[0] as $cidr) {
                if (! is_string($cidr)) {
                    continue;
                }

                $prefixo = $this->prefixoCorrespondente(
                    $ipBinario,
                    $cidr
                );

                if ($prefixo === null || $prefixo <= $melhorPrefixo) {
                    continue;
                }

                $melhorPrefixo = $prefixo;
                $melhorUrl = $servico[1][0] ?? null;
            }
        }

        if ($melhorPrefixo < 0) {
            throw new UnexpectedValueException(
                'Nenhum servidor RDAP encontrado para o IP.'
            );
        }

        return $this->validarUrl($melhorUrl);
    }

    private function prefixoCorrespondente(
        string $ipBinario,
        string $cidr
    ): ?int {
        $partes = explode('/', $cidr);

        if (count($partes) !== 2) {
            return null;
        }

        [$rede, $prefixoTexto] = $partes;

        if (! ctype_digit($prefixoTexto)) {
            return null;
        }

        $redeBinaria = inet_pton($rede);

        if (
            $redeBinaria === false ||
            strlen($redeBinaria) !== strlen($ipBinario)
        ) {
            return null;
        }

        $prefixo = (int) $prefixoTexto;
        $maximo = strlen($ipBinario) * 8;

        if ($prefixo > $maximo) {
            return null;
        }

        $bytes = intdiv($prefixo, 8);
        $restante = $prefixo % 8;

        if (
            substr($ipBinario, 0, $bytes) !==
            substr($redeBinaria, 0, $bytes)
        ) {
            return null;
        }

        if ($restante > 0) {
            $mascara = (0xFF << (8 - $restante)) & 0xFF;

            if (
                (ord($ipBinario[$bytes]) & $mascara) !==
                (ord($redeBinaria[$bytes]) & $mascara)
            ) {
                return null;
            }
        }

        return $prefixo;
    }

    private function validarUrl(mixed $url): string
    {
        if (
            ! is_string($url) ||
            preg_match('/[\s\\\\]/', $url) ||
            ! str_starts_with($url, 'https://')
        ) {
            throw new UnexpectedValueException(
                'URL RDAP não permitida.'
            );
        }

        $partes = parse_url($url);

        if ($partes === false || ! isset($partes['host'])) {
            throw new UnexpectedValueException(
                'URL RDAP inválida.'
            );
        }

        if (
            isset($partes['user']) ||
            isset($partes['pass']) ||
            isset($partes['query']) ||
            isset($partes['fragment']) ||
            (isset($partes['port']) && $partes['port'] !== 443)
        ) {
            throw new UnexpectedValueException(
                'URL RDAP contém componentes não permitidos.'
            );
        }

        $host = strtolower($partes['host']);

        if (
            filter_var($host, FILTER_VALIDATE_IP) !== false ||
            filter_var(
                $host,
                FILTER_VALIDATE_DOMAIN,
                FILTER_FLAG_HOSTNAME
            ) === false ||
            ! str_contains($host, '.') ||
            $host === 'localhost' ||
            str_ends_with($host, '.localhost')
        ) {
            throw new UnexpectedValueException(
                'Destino RDAP não permitido.'
            );
        }

        return $url;
    }
}
