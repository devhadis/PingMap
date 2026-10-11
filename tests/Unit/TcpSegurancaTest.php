<?php

namespace Tests\Unit;

use App\Services\TcpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TcpSegurancaTest extends TestCase
{
    public static function ipsBloqueados(): array
    {
        return [
            'loopback' => ['127.0.0.1'],
            'rede privada 10' => ['10.0.0.1'],
            'rede privada 172' => ['172.16.0.1'],
            'rede privada 192' => ['192.168.1.1'],
            'link local' => ['169.254.169.254'],
            'CGNAT' => ['100.64.0.1'],
            'documentacao TEST-NET-1' => ['192.0.2.1'],
            'documentacao TEST-NET-2' => ['198.51.100.1'],
            'documentacao TEST-NET-3' => ['203.0.113.1'],
            'benchmark' => ['198.18.0.1'],
            'multicast' => ['224.0.0.1'],
            'broadcast' => ['255.255.255.255'],
            'IPv6 loopback' => ['::1'],
            'hostname' => ['localhost'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ipsBloqueados')]
    public function test_bloqueia_destinos_nao_publicos(
        string $ip
    ): void {
        $tcp = new TcpService(
            function () {
                $this->fail(
                    'O conector não pode ser executado.'
                );
            }
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $tcp->verificar($ip, 443);
    }
}
