<?php

namespace Tests\Unit;

use App\Services\RdapConsultaDns;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class RdapConsultaDnsTest extends TestCase
{
    public function test_aceita_resolucao_publica(): void
    {
        $consulta = new RdapConsultaDns(
            fn ($host) => [
                '8.8.8.8',
                '2606:4700:4700::1111',
            ]
        );

        $this->assertSame(
            [
                '8.8.8.8',
                '2606:4700:4700::1111',
            ],
            $consulta->resolver('rdap.arin.net')
        );
    }

    public function test_rejeita_resolucao_privada(): void
    {
        $consulta = new RdapConsultaDns(
            fn ($host) => ['192.168.1.10']
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        $consulta->resolver('rdap.arin.net');
    }

    public function test_rejeita_resolucao_mista(): void
    {
        $consulta = new RdapConsultaDns(
            fn ($host) => [
                '8.8.8.8',
                '127.0.0.1',
            ]
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        $consulta->resolver('rdap.arin.net');
    }

    public function test_rejeita_resolucao_vazia(): void
    {
        $consulta = new RdapConsultaDns(
            fn ($host) => []
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        $consulta->resolver('rdap.arin.net');
    }

    public function test_rejeita_hostname_nao_autorizado(): void
    {
        $consulta = new RdapConsultaDns(
            fn ($host) => ['8.8.8.8']
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        $consulta->resolver('exemplo.com');
    }

    public function test_rejeita_resultado_dns_malformado(): void
    {
        $consulta = new RdapConsultaDns(
            fn ($host) => ['endereco-invalido']
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        $consulta->resolver('rdap.arin.net');
    }
}
