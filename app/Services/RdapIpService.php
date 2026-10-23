<?php

namespace App\Services;

class RdapIpService
{
    public function validarEntrada(string $ip): string
    {
        return (new EnderecoIpPublico())->validar($ip);
    }
}
