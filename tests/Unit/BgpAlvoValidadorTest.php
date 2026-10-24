<?php

namespace Tests\Unit;

use App\Services\BgpAlvoValidador;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BgpAlvoValidadorTest extends TestCase
{
    public function test_aceita_ipv4_publico(): void
    {
        $resultado = (new BgpAlvoValidador())
            ->normalizar('8.8.8.8');

        $this->assertSame([
            'tipo' => 'ip',
            'valor' => '8.8.8.8',
        ], $resultado);
    }

    public function test_aceita_ipv6_publico(): void
    {
        $resultado = (new BgpAlvoValidador())
            ->normalizar('2606:4700:4700::1111');

        $this->assertSame([
            'tipo' => 'ip',
            'valor' => '2606:4700:4700::1111',
        ], $resultado);
    }

    public function test_aceita_cidr_ipv4(): void
    {
        $resultado = (new BgpAlvoValidador())
            ->normalizar('8.8.8.0/24');

        $this->assertSame([
            'tipo' => 'cidr',
            'valor' => '8.8.8.0/24',
        ], $resultado);
    }

    public function test_aceita_cidr_ipv6(): void
    {
        $resultado = (new BgpAlvoValidador())
            ->normalizar('2606:4700::/32');

        $this->assertSame([
            'tipo' => 'cidr',
            'valor' => '2606:4700::/32',
        ], $resultado);
    }

    public function test_normaliza_dominio(): void
    {
        $resultado = (new BgpAlvoValidador())
            ->normalizar('Example.COM');

        $this->assertSame([
            'tipo' => 'dominio',
            'valor' => 'example.com',
        ], $resultado);
    }

    public function test_rejeita_ip_privado(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('192.168.1.1');
    }

    public function test_rejeita_cidr_privado(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('10.0.0.0/8');
    }

    public function test_rejeita_cidr_que_inclui_rede_privada(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('8.0.0.0/6');
    }

    public function test_rejeita_mascara_invalida(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('8.8.8.0/33');
    }

    public function test_rejeita_comando_injetado(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('8.8.8.8;whoami');
    }

    public function test_rejeita_localhost(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('localhost');
    }
}
