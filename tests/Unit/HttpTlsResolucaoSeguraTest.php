<?php

namespace Tests\Unit;

use App\Services\BgpResolucaoDominioService;
use App\Services\HttpTlsResolucaoSegura;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HttpTlsResolucaoSeguraTest extends TestCase
{
    private function servico(array $registros): HttpTlsResolucaoSegura
    {
        $dns = new BgpResolucaoDominioService(
            static fn (
                string $dominio,
                int $tipos,
                ?float $prazo = null
            ): array => $registros
        );

        return new HttpTlsResolucaoSegura($dns);
    }

    public function test_aceita_ipv4_e_ipv6_publicos(): void
    {
        $servico = $this->servico([
            ['type' => 'A', 'ip' => '8.8.8.8'],
            [
                'type' => 'AAAA',
                'ipv6' => '2001:4860:4860::8888',
            ],
        ]);

        $resultado = $servico->resolver('EXAMPLE.COM');

        $this->assertSame('example.com', $resultado['dominio']);

        $this->assertSame([
            '8.8.8.8',
            '2001:4860:4860::8888',
        ], $resultado['ips']);
    }

    public function test_rejeita_ip_privado(): void
    {
        $servico = $this->servico([
            ['type' => 'A', 'ip' => '192.168.1.10'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $servico->resolver('example.com');
    }

    public function test_rejeita_resposta_mista(): void
    {
        $servico = $this->servico([
            ['type' => 'A', 'ip' => '8.8.8.8'],
            ['type' => 'A', 'ip' => '127.0.0.1'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $servico->resolver('example.com');
    }

    public function test_rejeita_ipv6_local(): void
    {
        $servico = $this->servico([
            ['type' => 'AAAA', 'ipv6' => 'fd00::1'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $servico->resolver('example.com');
    }

    public function test_remove_ips_duplicados(): void
    {
        $servico = $this->servico([
            ['type' => 'A', 'ip' => '8.8.8.8'],
            ['type' => 'A', 'ip' => '8.8.8.8'],
        ]);

        $resultado = $servico->resolver('example.com');

        $this->assertSame(
            ['8.8.8.8'],
            $resultado['ips']
        );
    }
}
