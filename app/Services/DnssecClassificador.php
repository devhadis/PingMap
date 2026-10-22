<?php

namespace App\Services;

use InvalidArgumentException;

class DnssecClassificador
{
    public function classificar(
        string $validacao,
        bool $possuiDs,
        bool $possuiDnskey
    ): string {
        return match ($validacao) {
            'validado' => $possuiDs && $possuiDnskey
                ? 'seguro'
                : 'indeterminado',

            'falhou' => 'bogus',

            'inseguro' => 'inseguro',

            'indeterminada' => 'indeterminado',

            default => throw new InvalidArgumentException(
                'Estado de validação DNSSEC inválido.'
            ),
        };
    }
}
