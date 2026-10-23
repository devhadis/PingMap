<?php

namespace App\Services;

use GuzzleHttp\Handler\CurlHandler;
use RuntimeException;

class RdapTransporteSeguro
{
    public function criar(): CurlHandler
    {
        if (
            ! extension_loaded('curl') ||
            ! defined('CURLOPT_RESOLVE')
        ) {
            throw new RuntimeException(
                'Transporte cURL obrigatório para consultas RDAP.'
            );
        }

        return new CurlHandler();
    }
}
