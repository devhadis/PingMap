<?php

namespace Tests\Unit;

use App\Services\BgpResolucaoDominioService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BgpResolucaoDominioServiceTest extends TestCase
{
    public function test_resolve_ipv4_e_ipv6_publicos(): void
    {
        $servico = new BgpResolucaoDominioService(
            function (string $dominio, int $tipos): array {
                $this->assertSame('example.com', $dominio);
                $this->assertSame(
                    DNS_A | DNS_AAAA,
                    $tipos
                );

                return [
                    [
                        'type' => 'A',
                        'ip' => '8.8.8.8',
                    ],
                    [
                        'type' => 'AAAA',
                        'ipv6' => '2606:4700:4700::1111',
                    ],
                ];
            }
        );

        $resultado = $servico->resolver('Example.COM');

        $this->assertSame([
            'dominio' => 'example.com',
            'ips' => [
                '8.8.8.8',
                '2606:4700:4700::1111',
            ],
        ], $resultado);
    }

    public function test_remove_ips_duplicados(): void
    {
        $servico = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => [
                ['type' => 'A', 'ip' => '8.8.8.8'],
                ['type' => 'A', 'ip' => '8.8.8.8'],
                ['type' => 'A', 'ip' => '1.1.1.1'],
            ]
        );

        $resultado = $servico->resolver('example.com');

        $this->assertSame([
            '8.8.8.8',
            '1.1.1.1',
        ], $resultado['ips']);
    }

    public function test_rejeita_ip_privado(): void
    {
        $servico = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => [
                ['type' => 'A', 'ip' => '192.168.1.10'],
            ]
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $servico->resolver('example.com');
    }

    public function test_rejeita_resposta_mista_com_ip_privado(): void
    {
        $servico = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => [
                ['type' => 'A', 'ip' => '8.8.8.8'],
                ['type' => 'A', 'ip' => '10.0.0.5'],
            ]
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $servico->resolver('example.com');
    }

    public function test_rejeita_dominio_sem_ips(): void
    {
        $servico = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => []
        );

        $this->expectException(
            RuntimeException::class
        );

        $servico->resolver('example.com');
    }

    public function test_trata_falha_dns(): void
    {
        $servico = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): bool => false
        );

        $this->expectException(
            RuntimeException::class
        );

        $servico->resolver('example.com');
    }

    public function test_rejeita_url_como_dominio(): void
    {
        $servico = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => []
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $servico->resolver('https://example.com');
    }

    public function test_limita_resultado_a_oito_ips(): void
    {
        $registros = [];

        for ($i = 1; $i <= 10; $i++) {
            $registros[] = [
                'type' => 'A',
                'ip' => '8.8.8.' . $i,
            ];
        }

        $servico = new BgpResolucaoDominioService(
            fn (string $dominio, int $tipos): array => $registros
        );

        $resultado = $servico->resolver('example.com');

        $this->assertCount(8, $resultado['ips']);
    }
}
