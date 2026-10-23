<?php

namespace Tests\Feature;

use App\Services\RdapConsultaIpHttp;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class RdapConsultaIpHttpSegurancaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_rejeita_content_length_acima_do_limite(): void
    {
        Http::fake([
            'rdap.arin.net/*' => Http::response(
                '{"objectClassName":"ip network"}',
                200,
                [
                    'Content-Type' => 'application/rdap+json',
                    'Content-Length' => '2097153',
                ]
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

    public function test_rejeita_tipo_de_conteudo_html(): void
    {
        Http::fake([
            'rdap.arin.net/*' => Http::response(
                '{"objectClassName":"ip network"}',
                200,
                ['Content-Type' => 'text/html']
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

    public function test_rejeita_content_length_invalido(): void
    {
        Http::fake([
            'rdap.arin.net/*' => Http::response(
                '{"objectClassName":"ip network"}',
                200,
                [
                    'Content-Type' => 'application/rdap+json',
                    'Content-Length' => 'invalido',
                ]
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
