<?php

namespace Tests\Unit;

use App\Services\GeoIpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GeoIpServiceTest extends TestCase
{
    public function test_aceita_ipv4_publico(): void
    {
        $servico = new GeoIpService();

        $this->assertSame(
            '1.1.1.1',
            $servico->validarIp('1.1.1.1')
        );
    }

    public function test_rejeita_ipv4_privado(): void
    {
        $servico = new GeoIpService();

        $this->expectException(InvalidArgumentException::class);

        $servico->validarIp('192.168.1.1');
    }

    public function test_rejeita_endereco_local(): void
    {
        $servico = new GeoIpService();

        $this->expectException(InvalidArgumentException::class);

        $servico->validarIp('127.0.0.1');
    }

    public function test_rejeita_entrada_invalida(): void
    {
        $servico = new GeoIpService();

        $this->expectException(InvalidArgumentException::class);

        $servico->validarIp('endereco-invalido');
    }

    public function test_rejeita_ipv4_de_documentacao(): void
    {
        $servico = new GeoIpService();

        $this->expectException(InvalidArgumentException::class);

        $servico->validarIp('203.0.113.10');
    }
}
