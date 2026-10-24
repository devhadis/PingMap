<?php

namespace App\Services;

use InvalidArgumentException;

class HttpTlsAlvoValidador
{
    public function validar(string $entrada): string
    {
        $dominio = strtolower(trim($entrada));

        if ($dominio === '' || strlen($dominio) > 253) {
            throw new InvalidArgumentException(
                'Domínio vazio ou muito longo.'
            );
        }

        // Aceitar somente nomes DNS ASCII válidos.
        $padrao = '/\A(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}\z/';

        if (!preg_match($padrao, $dominio)) {
            throw new InvalidArgumentException(
                'Informe um domínio válido, sem URL ou porta.'
            );
        }

        // Bloquear sufixos locais ou reservados.
        $bloqueados = [
            '.local',
            '.localhost',
            '.internal',
            '.test',
            '.invalid',
            '.example',
            '.lan',
            '.home',
            '.arpa',
            '.onion',
        ];

        foreach ($bloqueados as $sufixo) {
            if (str_ends_with($dominio, $sufixo)) {
                throw new InvalidArgumentException(
                    'Domínio local ou reservado não permitido.'
                );
            }
        }

        return $dominio;
    }
}
