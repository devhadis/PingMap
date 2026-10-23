<?php

namespace Tests\Unit;

use App\Services\RdapOpcoesConexaoSegura;
use App\Services\RdapTransporteSeguro;
use GuzzleHttp\Handler\CurlHandler;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RdapConfiguracaoTransporteTest extends TestCase
{
    #[Test]
    public function utiliza_transporte_curl(): void
    {
        $transporte = (new RdapTransporteSeguro())->criar();

        $this->assertInstanceOf(
            CurlHandler::class,
            $transporte
        );
    }

    #[Test]
    public function fixa_ip_sem_opcoes_incompativeis(): void
    {
        $opcoes = (new RdapOpcoesConexaoSegura())->preparar(
            'rdap.arin.net',
            ['8.8.8.8']
        );

        $this->assertSame(
            ['rdap.arin.net:443:8.8.8.8'],
            $opcoes['curl'][CURLOPT_RESOLVE]
        );

        $this->assertArrayNotHasKey(
            CURLOPT_PROXY,
            $opcoes['curl']
        );

        $this->assertArrayNotHasKey(
            CURLOPT_SSL_VERIFYPEER,
            $opcoes['curl']
        );

        $this->assertArrayNotHasKey(
            CURLOPT_SSL_VERIFYHOST,
            $opcoes['curl']
        );

        $this->assertArrayNotHasKey(
            CURLOPT_VERBOSE,
            $opcoes['curl']
        );

        $this->assertSame('', $opcoes['proxy']);
        $this->assertTrue($opcoes['verify']);
        $this->assertFalse($opcoes['allow_redirects']);
    }
}
