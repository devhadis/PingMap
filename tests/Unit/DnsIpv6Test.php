<?php

namespace Tests\Unit;

use App\Services\DnsService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DnsIpv6Test extends TestCase
{
    public function test_consulta_registro_aaaa(): void
    {
        $servico = new DnsService(
            resolvedor: function ($dominio, $tipo) {
                $this->assertSame('cloudflare.com', $dominio);
                $this->assertSame(DNS_AAAA, $tipo);

                return [[
                    'type' => 'AAAA',
                    'ipv6' => '2606:4700::1111',
                ]];
            }
        );

        $resultado = $servico->consultar('CLOUDFLARE.COM', 'aaaa');

        $this->assertSame('sucesso', $resultado['status']);
        $this->assertSame(
            '2606:4700::1111',
            $resultado['registros'][0]['ipv6']
        );
    }

    public function test_converte_ipv6_para_ptr(): void
    {
        $ip = '2606:4700:4700::1111';

        $servico = new DnsService(
            resolvedor: function ($dominio, $tipo) use ($ip) {
                $this->assertSame(DNS_PTR, $tipo);

                $hex = bin2hex(inet_pton($ip));
                $esperado = implode(
                    '.',
                    array_reverse(str_split($hex))
                ) . '.ip6.arpa';

                $this->assertSame($esperado, $dominio);
                $this->assertCount(32, explode(
                    '.',
                    substr($dominio, 0, -9)
                ));

                return [[
                    'type' => 'PTR',
                    'target' => 'dns.example.net',
                ]];
            }
        );

        $resultado = $servico->consultar($ip, 'PTR');

        $this->assertSame('sucesso', $resultado['status']);
    }

    public function test_aceita_ipv6_publico_para_ptr(): void
    {
        $entrada = (new DnsService())->validarEntrada(
            '2001:4860:4860::8888',
            'PTR'
        );

        $this->assertSame(
            '2001:4860:4860::8888',
            $entrada['dominio']
        );
    }

    #[DataProvider('ipsBloqueados')]
    public function test_bloqueia_ipv6_especial_no_ptr(string $ip): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnsService())->validarEntrada($ip, 'PTR');
    }

    public static function ipsBloqueados(): array
    {
        return [
            'loopback' => ['::1'],
            'privado' => ['fc00::1'],
            'link-local' => ['fe80::1'],
            'documentacao' => ['2001:db8::1'],
            '6to4' => ['2002::1'],
            'teredo' => ['2001::1'],
            'ipv4 mapeado' => ['::ffff:127.0.0.1'],
        ];
    }
}
