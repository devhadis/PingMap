<?php

namespace Tests\Feature;

use App\Services\RdapCatalogoIana;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class RdapCatalogoIanaSegurancaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function simular(string $cidr, string $url): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response([
                    'version' => '1.0',
                    'services' => [
                        [
                            [$cidr],
                            [$url],
                        ],
                    ],
                ], 200),
        ]);
    }

    public function test_rejeita_prefixo_invalido(): void
    {
        $this->simular(
            '1.1.1.0/33',
            'https://rdap.apnic.net/'
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapCatalogoIana())->obter('1.1.1.1');
    }

    public function test_rejeita_ipv6_em_catalogo_ipv4(): void
    {
        $this->simular(
            '2606:4700::/32',
            'https://rdap.apnic.net/'
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapCatalogoIana())->obter('1.1.1.1');
    }

    public function test_rejeita_url_http(): void
    {
        $this->simular(
            '1.0.0.0/8',
            'http://rdap.apnic.net/'
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapCatalogoIana())->obter('1.1.1.1');
    }

    public function test_rejeita_destino_local(): void
    {
        $this->simular(
            '1.0.0.0/8',
            'https://127.0.0.1/'
        );

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapCatalogoIana())->obter('1.1.1.1');
    }
}
