<?php

namespace Tests\Unit;

use App\Services\BgpAlvoValidador;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BgpAlvoValidadorSegurancaTest extends TestCase
{
    public function test_rejeita_ipv4_com_bits_de_host(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('8.8.8.8/24');
    }

    public function test_rejeita_ipv6_com_bits_de_host(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('2606:4700::1/32');
    }

    public function test_rejeita_ipv6_de_documentacao(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('2001:db8::/32');
    }

    public function test_rejeita_url_no_lugar_de_dominio(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('https://example.com');
    }

    public function test_rejeita_dominio_com_porta(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar('example.com:443');
    }
}
