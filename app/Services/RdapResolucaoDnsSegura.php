<?php

namespace App\Services;

use InvalidArgumentException;
use UnexpectedValueException;

class RdapResolucaoDnsSegura
{
    public function validar(array $enderecos): array
    {
        if ($enderecos === []) {
            throw new UnexpectedValueException(
                'Resolução DNS RDAP vazia.'
            );
        }

        $validador = new EnderecoIpPublico();
        $aprovados = [];

        foreach ($enderecos as $ip) {
            if (! is_string($ip)) {
                throw new UnexpectedValueException(
                    'Resolução DNS RDAP contém um IP inválido.'
                );
            }

            try {
                $validador->validar($ip);
            } catch (InvalidArgumentException $e) {
                throw new UnexpectedValueException(
                    'Resolução DNS RDAP contém um IP não permitido.',
                    0,
                    $e
                );
            }

            $aprovados[] = $ip;
        }

        return array_values(array_unique($aprovados));
    }
}
