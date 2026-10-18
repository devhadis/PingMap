<?php

namespace Tests\Unit;

use App\Services\EnderecoIpPublico;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnderecoIpPublicoTest extends TestCase
{
    #[DataProvider('enderecosPermitidos')]
    public function test_aceita_enderecos_publicos(string $ip): void
    {
        $validador = new EnderecoIpPublico();

        $this->assertSame($ip, $validador->validar($ip));
    }

    public static function enderecosPermitidos(): array
    {
        return [
            'Cloudflare IPv4' => ['1.1.1.1'],
            'Google IPv4' => ['8.8.8.8'],
            'Cloudflare IPv6' => ['2606:4700:4700::1111'],
            'Google IPv6' => ['2001:4860:4860::8888'],
            'Google IPv6 alternativo' => ['2001:4860:4860::8844'],
        ];
    }

    #[DataProvider('enderecosBloqueados')]
    public function test_rejeita_enderecos_nao_permitidos(string $ip): void
    {
        $validador = new EnderecoIpPublico();

        $this->expectException(InvalidArgumentException::class);

        $validador->validar($ip);
    }

    public static function enderecosBloqueados(): array
    {
        return [
            'IPv4 loopback' => ['127.0.0.1'],
            'IPv4 privado' => ['192.168.1.1'],
            'IPv4 privado 10' => ['10.0.0.1'],
            'IPv4 compartilhado' => ['100.64.0.1'],
            'IPv4 metadata' => ['169.254.169.254'],
            'IPv4 protocolo especial' => ['192.0.0.9'],
            'IPv4 broadcast' => ['255.255.255.255'],
            'IPv4 documentacao' => ['192.0.2.1'],
            'IPv4 benchmarking' => ['198.18.0.1'],
            'IPv4 multicast' => ['224.0.0.1'],
            'IPv4 reservado' => ['240.0.0.1'],
            'IPv6 loopback' => ['::1'],
            'IPv6 nao especificado' => ['::'],
            'IPv6 privado' => ['fc00::1'],
            'IPv6 link local' => ['fe80::1'],
            'IPv6 multicast' => ['ff02::1'],
            'IPv6 documentacao' => ['2001:db8::1'],
            'IPv6 documentacao adicional' => ['3fff::1'],
            'IPv6 ORCHID antigo' => ['2001:10::1'],
            'IPv6 benchmarking' => ['2001:2::1'],
            'IPv6 documentacao estendida' => ['2001:db8:abcd::1'],
            'IPv6 ORCHIDv2' => ['2001:20::1'],
            'IPv6 reservado 2001' => ['2001:db8::abcd'],
            'IPv6 6to4 com IPv4 privado' => ['2002:0a00:0001::1'],
            'IPv6 NAT64 privado' => ['64:ff9b::a00:1'],
            'IPv6 IPv4 mapeado privado' => ['::ffff:192.168.1.1'],
            'IPv6 Teredo' => ['2001::1'],
            'IPv6 6to4' => ['2002:c000:0201::1'],
            'IPv6 NAT64' => ['64:ff9b::808:808'],
            'IPv6 IPv4 mapeado' => ['::ffff:127.0.0.1'],
            'IPv6 IPv4 publico mapeado' => ['::ffff:8.8.8.8'],
            'IPv6 zona de interface' => ['fe80::1%eth0'],
            'Hostname' => ['localhost'],
            'URL' => ['http://1.1.1.1'],
            'IPv4 invalido' => ['999.999.999.999'],
            'String vazia' => [''],
            'Endereco com espacos' => [' 1.1.1.1 '],
        ];
    }
}
