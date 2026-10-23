<?php

namespace Tests\Unit;

use App\Services\RdapBootstrapIp;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use InvalidArgumentException;

class RdapBootstrapIpTest extends TestCase
{
    private function catalogo(): array
    {
        return [
            'version' => '1.0',
            'services' => [
                [
                    ['1.0.0.0/8'],
                    ['https://rdap.apnic.net/'],
                ],
                [
                    ['1.1.1.0/24'],
                    ['https://rdap.exemplo.org/'],
                ],
                [
                    ['2606:4700::/32'],
                    ['https://rdap.arin.net/registry/'],
                ],
            ],
        ];
    }

    public function test_encontra_servidor_ipv4(): void
    {
        $url = (new RdapBootstrapIp())->localizar(
            '1.2.3.4',
            $this->catalogo()
        );

        $this->assertSame(
            'https://rdap.apnic.net/',
            $url
        );
    }

    public function test_prioriza_prefixo_mais_especifico(): void
    {
        $url = (new RdapBootstrapIp())->localizar(
            '1.1.1.1',
            $this->catalogo()
        );

        $this->assertSame(
            'https://rdap.exemplo.org/',
            $url
        );
    }

    public function test_encontra_servidor_ipv6(): void
    {
        $url = (new RdapBootstrapIp())->localizar(
            '2606:4700:4700::1111',
            $this->catalogo()
        );

        $this->assertSame(
            'https://rdap.arin.net/registry/',
            $url
        );
    }

    public function test_rejeita_ip_privado(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new RdapBootstrapIp())->localizar(
            '192.168.1.1',
            $this->catalogo()
        );
    }

    public function test_rejeita_catalogo_vazio(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapBootstrapIp())->localizar(
            '1.1.1.1',
            ['services' => []]
        );
    }

    public function test_rejeita_servidor_http(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapBootstrapIp())->localizar(
            '1.1.1.1',
            [
                'services' => [
                    [
                        ['1.1.1.0/24'],
                        ['http://rdap.exemplo.org/'],
                    ],
                ],
            ]
        );
    }

    public function test_rejeita_destino_local(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapBootstrapIp())->localizar(
            '1.1.1.1',
            [
                'services' => [
                    [
                        ['1.1.1.0/24'],
                        ['https://127.0.0.1/'],
                    ],
                ],
            ]
        );
    }

    public function test_rejeita_ip_sem_bloco_correspondente(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapBootstrapIp())->localizar(
            '8.8.8.8',
            $this->catalogo()
        );
    }
}
