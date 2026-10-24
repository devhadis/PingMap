<?php

namespace Tests\Feature;

use App\Services\BgpDnsConsultaProtegida;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;
use Tests\TestCase;

class BgpDnsConsultaProtegidaTest extends TestCase
{
    public function test_consulta_a_e_aaaa_por_https(): void
    {
        Http::fake(function (Request $request) {
            parse_str(
                (string) parse_url($request->url(), PHP_URL_QUERY),
                $parametros
            );

            $tipo = $parametros['type'] ?? '';

            if ($tipo === 'A') {
                return Http::response([
                    'Status' => 0,
                    'Answer' => [
                        [
                            'type' => 1,
                            'data' => '8.8.8.8',
                        ],
                    ],
                ], 200);
            }

            return Http::response([
                'Status' => 0,
                'Answer' => [
                    [
                        'type' => 28,
                        'data' => '2001:4860:4860::8888',
                    ],
                ],
            ], 200);
        });

        $resultado = (new BgpDnsConsultaProtegida())
            ->consultar('dns.google');

        $this->assertSame([
            ['type' => 'A', 'ip' => '8.8.8.8'],
            [
                'type' => 'AAAA',
                'ipv6' => '2001:4860:4860::8888',
            ],
        ], $resultado);

        Http::assertSentCount(2);

        Http::assertSent(
            fn (Request $request): bool =>
                str_starts_with(
                    $request->url(),
                    'https://dns.google/resolve?'
                )
        );
    }

    public function test_aceita_dominio_sem_respostas_dns(): void
    {
        Http::fake([
            '*' => Http::response([
                'Status' => 0,
            ], 200),
        ]);

        $resultado = (new BgpDnsConsultaProtegida())
            ->consultar('example.com');

        $this->assertSame([], $resultado);

        Http::assertSentCount(2);
    }

    public function test_trata_falha_http(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        $this->expectException(RuntimeException::class);

        (new BgpDnsConsultaProtegida())
            ->consultar('example.com');
    }

    public function test_rejeita_resposta_dns_invalida(): void
    {
        Http::fake([
            '*' => Http::response([
                'Status' => 0,
                'Answer' => 'resposta invalida',
            ], 200),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new BgpDnsConsultaProtegida())
            ->consultar('example.com');
    }

    public function test_rejeita_redirecionamento(): void
    {
        Http::fake([
            '*' => Http::response(
                '',
                302,
                ['Location' => 'https://outro.example']
            ),
        ]);

        $this->expectException(RuntimeException::class);

        (new BgpDnsConsultaProtegida())
            ->consultar('example.com');
    }

    public function test_rejeita_dominio_invalido(): void
    {
        Http::fake();

        try {
            (new BgpDnsConsultaProtegida())
                ->consultar('http://example.com');

            $this->fail(
                'Deveria rejeitar a URL.'
            );
        } catch (InvalidArgumentException $erro) {
            Http::assertNothingSent();
        }
    }
}
