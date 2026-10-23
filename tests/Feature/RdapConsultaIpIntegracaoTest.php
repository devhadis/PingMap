<?php

namespace Tests\Feature;

use App\Services\RdapConsultaIpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class RdapConsultaIpIntegracaoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_consulta_completa_ipv4(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response([
                    'version' => '1.0',
                    'services' => [
                        [
                            ['1.1.1.0/24'],
                            ['https://rdap.arin.net/registry/'],
                        ],
                    ],
                ], 200),

            'rdap.arin.net/*' =>
                Http::response([
                    'objectClassName' => 'ip network',
                    'startAddress' => '1.1.1.0',
                    'endAddress' => '1.1.1.255',
                    'ipVersion' => 'v4',
                ], 200),
        ]);

        $resultado = (new RdapConsultaIpService())
            ->consultar('1.1.1.1');

        $this->assertSame('IPv4', $resultado['versao']);
        $this->assertSame('1.1.1.0', $resultado['inicio']);
        $this->assertSame('1.1.1.255', $resultado['fim']);

        Http::assertSentCount(2);
    }

    public function test_consulta_completa_ipv6(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv6.json' =>
                Http::response([
                    'version' => '1.0',
                    'services' => [
                        [
                            ['2606:4700::/32'],
                            ['https://rdap.apnic.net/'],
                        ],
                    ],
                ], 200),

            'rdap.apnic.net/*' =>
                Http::response([
                    'objectClassName' => 'ip network',
                    'startAddress' => '2606:4700::',
                    'endAddress' =>
                        '2606:4700:ffff:ffff:ffff:ffff:ffff:ffff',
                    'ipVersion' => 'v6',
                ], 200),
        ]);

        $resultado = (new RdapConsultaIpService())
            ->consultar('2606:4700:4700::1111');

        $this->assertSame('IPv6', $resultado['versao']);

        Http::assertSentCount(2);
    }

    public function test_rejeita_rede_que_nao_contem_ip(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response([
                    'version' => '1.0',
                    'services' => [
                        [
                            ['1.1.1.0/24'],
                            ['https://rdap.arin.net/registry/'],
                        ],
                    ],
                ], 200),

            'rdap.arin.net/*' =>
                Http::response([
                    'objectClassName' => 'ip network',
                    'startAddress' => '8.8.8.0',
                    'endAddress' => '8.8.8.255',
                    'ipVersion' => 'v4',
                ], 200),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapConsultaIpService())
            ->consultar('1.1.1.1');
    }

    public function test_rejeita_servidor_nao_autorizado(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response([
                    'version' => '1.0',
                    'services' => [
                        [
                            ['1.1.1.0/24'],
                            ['https://rdap.arin.net.evil.example/'],
                        ],
                    ],
                ], 200),
        ]);

        try {
            (new RdapConsultaIpService())
                ->consultar('1.1.1.1');

            $this->fail(
                'Servidor não autorizado deveria ser rejeitado.'
            );
        } catch (UnexpectedValueException $e) {
            Http::assertSentCount(1);
        }
    }
}
