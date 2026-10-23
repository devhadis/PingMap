<?php

namespace Tests\Feature;

use App\Services\RdapConsultaIpHttp;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use UnexpectedValueException;

class RdapConsultaIpHttpTest extends TestCase
{
    public function test_consulta_ipv4(): void
    {
        Http::fake([
            'rdap.arin.net/*' => Http::response([
                'objectClassName' => 'ip network',
                'startAddress' => '1.1.1.0',
                'endAddress' => '1.1.1.255',
                'ipVersion' => 'v4',
            ], 200),
        ]);

        $resultado = (new RdapConsultaIpHttp())->consultar(
            '1.1.1.1',
            'https://rdap.arin.net/registry/'
        );

        $this->assertSame(
            'ip network',
            $resultado['objectClassName']
        );

        Http::assertSent(
            fn ($request) =>
                $request->url() ===
                'https://rdap.arin.net/registry/ip/1.1.1.1'
        );
    }

    public function test_consulta_ipv6(): void
    {
        Http::fake([
            'rdap.apnic.net/*' => Http::response([
                'objectClassName' => 'ip network',
                'startAddress' => '2606:4700::',
                'endAddress' =>
                    '2606:4700:ffff:ffff:ffff:ffff:ffff:ffff',
                'ipVersion' => 'v6',
            ], 200),
        ]);

        $resultado = (new RdapConsultaIpHttp())->consultar(
            '2606:4700:4700::1111',
            'https://rdap.apnic.net/'
        );

        $this->assertSame('v6', $resultado['ipVersion']);

        Http::assertSent(
            fn ($request) =>
                $request->url() ===
                'https://rdap.apnic.net/ip/2606%3A4700%3A4700%3A%3A1111'
        );
    }

    public function test_rejeita_destino_local_sem_requisicao(): void
    {
        Http::fake();

        try {
            (new RdapConsultaIpHttp())->consultar(
                '1.1.1.1',
                'https://127.0.0.1/'
            );

            $this->fail('Destino local deveria ser rejeitado.');
        } catch (UnexpectedValueException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_rejeita_redirecionamento(): void
    {
        Http::fake([
            'rdap.arin.net/*' => Http::response(
                '',
                302,
                ['Location' => 'http://127.0.0.1/']
            ),
        ]);

        $this->expectException(RuntimeException::class);

        (new RdapConsultaIpHttp())->consultar(
            '1.1.1.1',
            'https://rdap.arin.net/registry/'
        );
    }

    public function test_rejeita_json_invalido(): void
    {
        Http::fake([
            'rdap.arin.net/*' => Http::response(
                '{"incompleto":',
                200,
                ['Content-Type' => 'application/rdap+json']
            ),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapConsultaIpHttp())->consultar(
            '1.1.1.1',
            'https://rdap.arin.net/registry/'
        );
    }

    public function test_rejeita_resposta_muito_grande(): void
    {
        Http::fake([
            'rdap.arin.net/*' => Http::response(
                str_repeat('A', 2 * 1024 * 1024 + 1),
                200
            ),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapConsultaIpHttp())->consultar(
            '1.1.1.1',
            'https://rdap.arin.net/registry/'
        );
    }
}
