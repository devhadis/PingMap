<?php

namespace App\Services;

use InvalidArgumentException;

class BgpAsnValidador
{
    public function normalizar(string $asn): int
    {
        if (! preg_match('/\A(?:AS)?([0-9]+)\z/i', $asn, $partes)) {
            throw new InvalidArgumentException('ASN inválido.');
        }

        $numero = ltrim($partes[1], '0');

        if (
            $numero === '' ||
            strlen($numero) > 10 ||
            (
                strlen($numero) === 10 &&
                strcmp($numero, '4294967294') > 0
            )
        ) {
            throw new InvalidArgumentException('ASN fora do intervalo permitido.');
        }

        return (int) $numero;
    }
}
