<?php

namespace Tests\Feature;

use App\Services\RdapCatalogoIana;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;
use UnexpectedValueException;

class RdapCatalogoIanaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function catalogo(string $cidr): array
    {
        return [
            'version' => '1.0',
            'services' => [
                [
                    [$cidr],
                    ['https://rdap.example.org/'],
                ],
            ],
        ];
    }

    public function test_obtem_catalogo_ipv4(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response(
                    $this->catalogo('1.0.0.0/8'),
                    200
                ),
        ]);

        $resultado = (new RdapCatalogoIana())
            ->obter('1.1.1.1');

        $this->assertSame(
            '1.0.0.0/8',
            $resultado['services'][0][0][0]
        );

        Http::assertSent(fn ($request) =>
            $request->url() ===
            'https://data.iana.org/rdap/ipv4.json'
        );

        Http::assertSentCount(1);
    }

    public function test_obtem_catalogo_ipv6(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv6.json' =>
                Http::response(
                    $this->catalogo('2606:4700::/32'),
                    200
                ),
        ]);

        $resultado = (new RdapCatalogoIana())
            ->obter('2606:4700:4700::1111');

        $this->assertSame(
            '2606:4700::/32',
            $resultado['services'][0][0][0]
        );

        Http::assertSent(fn ($request) =>
            $request->url() ===
            'https://data.iana.org/rdap/ipv6.json'
        );

        Http::assertSentCount(1);
    }

    public function test_utiliza_cache(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response(
                    $this->catalogo('1.0.0.0/8'),
                    200
                ),
        ]);

        $servico = new RdapCatalogoIana();

        $primeiro = $servico->obter('1.1.1.1');
        $segundo = $servico->obter('1.2.3.4');

        $this->assertSame($primeiro, $segundo);

        Http::assertSentCount(1);
    }

    public function test_rejeita_erro_http(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response('Erro', 503),
        ]);

        $this->expectException(RuntimeException::class);

        (new RdapCatalogoIana())->obter('1.1.1.1');
    }

    public function test_rejeita_catalogo_malformado(): void
    {
        Http::fake([
            'data.iana.org/rdap/ipv4.json' =>
                Http::response([
                    'version' => '1.0',
                    'services' => 'invalido',
                ], 200),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapCatalogoIana())->obter('1.1.1.1');
    }

    public function test_rejeita_ip_privado_sem_consultar(): void
    {
        Http::fake();

        $this->expectException(
            InvalidArgumentException::class
        );

        try {
            (new RdapCatalogoIana())->obter('192.168.1.1');
        } finally {
            Http::assertNothingSent();
        }
    }
}
