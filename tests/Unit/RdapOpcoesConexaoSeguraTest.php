<?php

namespace Tests\Unit;

use App\Services\RdapOpcoesConexaoSegura;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class RdapOpcoesConexaoSeguraTest extends TestCase
{
    public function test_fixa_ipv4_no_curl(): void
    {
        $opcoes = (new RdapOpcoesConexaoSegura())
            ->preparar(
                'rdap.arin.net',
                ['8.8.8.8']
            );

        $this->assertSame(
            ['rdap.arin.net:443:8.8.8.8'],
            $opcoes['curl'][CURLOPT_RESOLVE]
        );
    }

    public function test_fixa_ipv6_no_curl(): void
    {
        $opcoes = (new RdapOpcoesConexaoSegura())
            ->preparar(
                'rdap.arin.net',
                ['2606:4700:4700::1111']
            );

        $this->assertSame(
            ['rdap.arin.net:443:[2606:4700:4700::1111]'],
            $opcoes['curl'][CURLOPT_RESOLVE]
        );
    }

    public function test_desativa_proxy(): void
    {
        $opcoes = (new RdapOpcoesConexaoSegura())
            ->preparar(
                'rdap.arin.net',
                ['8.8.8.8']
            );

        $this->assertSame('', $opcoes['proxy']);
        $this->assertArrayNotHasKey(
            CURLOPT_PROXY,
            $opcoes['curl']
        );
    }

    public function test_mantem_validacao_https(): void
    {
        $opcoes = (new RdapOpcoesConexaoSegura())
            ->preparar(
                'rdap.arin.net',
                ['8.8.8.8']
            );

        $this->assertTrue($opcoes['verify']);
        $this->assertFalse(
            $opcoes['allow_redirects']
        );
    }

    public function test_rejeita_ip_privado(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapOpcoesConexaoSegura())
            ->preparar(
                'rdap.arin.net',
                ['127.0.0.1']
            );
    }

    public function test_rejeita_dominio_nao_autorizado(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapOpcoesConexaoSegura())
            ->preparar(
                'exemplo.com',
                ['8.8.8.8']
            );
    }
}
