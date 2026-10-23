<?php

namespace Tests\Unit;

use App\Services\RdapTransporteSeguro;
use GuzzleHttp\Handler\CurlHandler;
use PHPUnit\Framework\TestCase;

class RdapTransporteSeguroTest extends TestCase
{
    public function test_utiliza_curl_handler(): void
    {
        $transporte = new RdapTransporteSeguro();

        $handler = $transporte->criar();

        $this->assertInstanceOf(
            CurlHandler::class,
            $handler
        );
    }

    public function test_cria_transporte_independente(): void
    {
        $transporte = new RdapTransporteSeguro();

        $primeiro = $transporte->criar();
        $segundo = $transporte->criar();

        $this->assertNotSame(
            $primeiro,
            $segundo
        );
    }
}
