<?php

namespace App\Services;

use UnexpectedValueException;

class RdapDestinoSeguro
{
    private const HOSTS_PERMITIDOS = [
        'rdap.arin.net',
        'rdap.db.ripe.net',
        'rdap.apnic.net',
        'rdap.lacnic.net',
        'rdap.afrinic.net',
    ];

    public function validar(string $url): string
    {
        if (
            preg_match('/[\x00-\x20\x7F\\\\]/', $url) ||
            ! str_starts_with($url, 'https://')
        ) {
            throw new UnexpectedValueException(
                'URL RDAP inválida ou sem HTTPS.'
            );
        }

        $partes = parse_url($url);

        if (
            $partes === false ||
            ! isset($partes['scheme'], $partes['host']) ||
            $partes['scheme'] !== 'https' ||
            isset($partes['user']) ||
            isset($partes['pass']) ||
            isset($partes['query']) ||
            isset($partes['fragment']) ||
            (isset($partes['port']) && $partes['port'] !== 443)
        ) {
            throw new UnexpectedValueException(
                'Componentes de URL RDAP não permitidos.'
            );
        }

        $host = strtolower($partes['host']);

        if (! in_array($host, self::HOSTS_PERMITIDOS, true)) {
            throw new UnexpectedValueException(
                'Servidor RDAP não autorizado.'
            );
        }

        $caminho = $partes['path'] ?? '/';

        if (
            str_contains($caminho, '%') ||
            str_contains($caminho, '..') ||
            str_contains($caminho, '//') ||
            preg_match('#[^a-zA-Z0-9/_-]#', $caminho)
        ) {
            throw new UnexpectedValueException(
                'Caminho RDAP não permitido.'
            );
        }

        return $url;
    }
}
