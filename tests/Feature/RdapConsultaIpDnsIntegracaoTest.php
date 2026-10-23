<?php

namespace Tests\Feature;

use App\Services\RdapConsultaDns;
use App\Services\RdapConsultaIpHttp;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class RdapConsultaIpDnsIntegracaoTest extends TestCase
{
    public function test_rejeita_dns_privado_antes_do_http(): void
    {
        Http::fake();

        $dns = new RdapConsultaDns(
            fn (string $host): array => [
                '127.0.0.1',
            ]
        );

        try {
            (new RdapConsultaIpHttp($dns))
                ->consultar(
                    '8.8.8.8',
                    'https://rdap.arin.net'
                );

            $this->fail(
                'A consulta deveria rejeitar o DNS privado.'
            );
        } catch (UnexpectedValueException $e) {
            $this->assertNotEmpty($e->getMessage());
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_utiliza_resolvedor_dns_injetado(): void
    {
        $chamadas = [];

        $dns = new RdapConsultaDns(
            function (string $host) use (&$chamadas): array {
                $chamadas[] = $host;

                return ['8.8.8.8'];
            }
        );

        Http::fake([
            '*' => Http::response(
                ['objectClassName' => 'ip network'],
                200,
                ['Content-Type' => 'application/rdap+json']
            ),
        ]);

        $resultado = (new RdapConsultaIpHttp($dns))
            ->consultar(
                '8.8.8.8',
                'https://rdap.arin.net'
            );

        $this->assertSame(
            ['rdap.arin.net'],
            $chamadas
        );

        $this->assertSame(
            'ip network',
            $resultado['objectClassName']
        );

        Http::assertSentCount(1);
    }
}
