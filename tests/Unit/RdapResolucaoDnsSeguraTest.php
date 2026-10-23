<?php

namespace Tests\Unit;

use App\Services\RdapResolucaoDnsSegura;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class RdapResolucaoDnsSeguraTest extends TestCase
{
    public function test_aceita_ipv4_publico(): void
    {
        $dns = new RdapResolucaoDnsSegura();

        $resultado = $dns->validar([
            '8.8.8.8',
        ]);

        $this->assertSame(
            ['8.8.8.8'],
            $resultado
        );
    }

    public function test_aceita_ipv6_publico(): void
    {
        $dns = new RdapResolucaoDnsSegura();

        $resultado = $dns->validar([
            '2606:4700:4700::1111',
        ]);

        $this->assertSame(
            ['2606:4700:4700::1111'],
            $resultado
        );
    }

    public function test_rejeita_ipv4_privado(): void
    {
        $dns = new RdapResolucaoDnsSegura();

        $this->expectException(
            UnexpectedValueException::class
        );

        $dns->validar(['192.168.1.10']);
    }

    public function test_rejeita_endereco_local(): void
    {
        $dns = new RdapResolucaoDnsSegura();

        $this->expectException(
            UnexpectedValueException::class
        );

        $dns->validar(['127.0.0.1']);
    }

    public function test_rejeita_ipv6_local(): void
    {
        $dns = new RdapResolucaoDnsSegura();

        $this->expectException(
            UnexpectedValueException::class
        );

        $dns->validar(['::1']);
    }

    public function test_rejeita_resolucao_mista(): void
    {
        $dns = new RdapResolucaoDnsSegura();

        $this->expectException(
            UnexpectedValueException::class
        );

        $dns->validar([
            '8.8.8.8',
            '10.0.0.5',
        ]);
    }

    public function test_rejeita_lista_vazia(): void
    {
        $dns = new RdapResolucaoDnsSegura();

        $this->expectException(
            UnexpectedValueException::class
        );

        $dns->validar([]);
    }

    public function test_rejeita_ip_invalido(): void
    {
        $dns = new RdapResolucaoDnsSegura();

        $this->expectException(
            UnexpectedValueException::class
        );

        $dns->validar(['endereco-invalido']);
    }
}
