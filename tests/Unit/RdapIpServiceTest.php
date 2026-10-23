<?php

namespace Tests\Unit;

use App\Services\RdapIpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RdapIpServiceTest extends TestCase
{
    public function test_aceita_ipv4_publico(): void
    {
        $servico = new RdapIpService();

        $this->assertSame(
            '1.1.1.1',
            $servico->validarEntrada('1.1.1.1')
        );
    }

    public function test_aceita_ipv6_publico(): void
    {
        $servico = new RdapIpService();

        $this->assertSame(
            '2606:4700:4700::1111',
            $servico->validarEntrada('2606:4700:4700::1111')
        );
    }

    public function test_rejeita_ipv4_privado(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RdapIpService())->validarEntrada('192.168.1.1');
    }

    public function test_rejeita_ipv6_local(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RdapIpService())->validarEntrada('fc00::1');
    }

    public function test_rejeita_hostname(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RdapIpService())->validarEntrada('localhost');
    }

    public function test_rejeita_injecao_de_comando(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new RdapIpService())->validarEntrada('1.1.1.1;whoami');
    }
}
