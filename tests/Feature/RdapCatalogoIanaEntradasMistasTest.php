<?php

namespace Tests\Feature;

use App\Services\RdapBootstrapIp;
use App\Services\RdapCatalogoIana;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class RdapCatalogoIanaEntradasMistasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_ignora_http_e_utiliza_https(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response([
                    'version' => '1.0',
                    'services' => [
                        [
                            ['1.0.0.0/8'],
                            [
                                'http://rdap.apnic.net/',
                                'https://rdap.apnic.net/',
                            ],
                        ],
                        [
                            ['41.0.0.0/8'],
                            ['http://rdap.afrinic.net/rdap/'],
                        ],
                    ],
                ], 200),
        ]);

        $catalogo = (new RdapCatalogoIana())
            ->obter('1.1.1.1');

        $this->assertSame(
            ['https://rdap.apnic.net/'],
            $catalogo['services'][0][1]
        );

        $this->assertSame(
            [],
            $catalogo['services'][1][1]
        );

        $servidor = (new RdapBootstrapIp())
            ->localizar('1.1.1.1', $catalogo);

        $this->assertSame(
            'https://rdap.apnic.net/',
            $servidor
        );
    }

    public function test_nao_utiliza_http_da_rede_consultada(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response([
                    'version' => '1.0',
                    'services' => [
                        [
                            ['1.0.0.0/8'],
                            ['http://rdap.apnic.net/'],
                        ],
                        [
                            ['8.0.0.0/8'],
                            ['https://rdap.arin.net/registry/'],
                        ],
                    ],
                ], 200),
        ]);

        $catalogo = (new RdapCatalogoIana())
            ->obter('1.1.1.1');

        $this->assertSame(
            [],
            $catalogo['services'][0][1]
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapBootstrapIp())
            ->localizar('1.1.1.1', $catalogo);
    }
}
