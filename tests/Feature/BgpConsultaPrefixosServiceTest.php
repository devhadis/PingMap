<?php

namespace Tests\Feature;

use App\Services\BgpConsultaPrefixosService;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;
use Tests\TestCase;

class BgpConsultaPrefixosServiceTest extends TestCase
{
    public function test_consulta_prefixos_ipv4_e_ipv6(): void
    {
        Http::fake([
            'stat.ripe.net/data/announced-prefixes/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => 'AS13335',
                        'prefixes' => [
                            ['prefix' => '203.0.113.0/24'],
                            ['prefix' => '2001:db8::/32'],
                        ],
                    ],
                ], 200),
        ]);

        $resultado = (new BgpConsultaPrefixosService())
            ->consultar(13335);

        $this->assertSame([
            'ipv4' => ['203.0.113.0/24'],
            'ipv6' => ['2001:db8::/32'],
        ], $resultado);

        Http::assertSent(fn ($request) =>
            $request->method() === 'GET' &&
            str_starts_with(
                $request->url(),
                'https://stat.ripe.net/data/announced-prefixes/data.json?'
            ) &&
            str_contains($request->url(), 'resource=AS13335')
        );

        Http::assertSentCount(1);
    }

    public function test_aceita_asn_sem_prefixos(): void
    {
        Http::fake([
            'stat.ripe.net/data/announced-prefixes/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => 'AS4230',
                        'prefixes' => [],
                    ],
                ], 200),
        ]);

        $resultado = (new BgpConsultaPrefixosService())
            ->consultar(4230);

        $this->assertSame([
            'ipv4' => [],
            'ipv6' => [],
        ], $resultado);
    }

    public function test_ignora_prefixos_invalidos_e_duplicados(): void
    {
        Http::fake([
            'stat.ripe.net/data/announced-prefixes/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => 'AS13335',
                        'prefixes' => [
                            ['prefix' => '203.0.113.0/24'],
                            ['prefix' => '203.0.113.0/24'],
                            ['prefix' => '203.0.113.0/33'],
                            ['prefix' => '2001:db8::/129'],
                            ['prefix' => 'texto-invalido'],
                        ],
                    ],
                ], 200),
        ]);

        $resultado = (new BgpConsultaPrefixosService())
            ->consultar(13335);

        $this->assertSame([
            'ipv4' => ['203.0.113.0/24'],
            'ipv6' => [],
        ], $resultado);
    }

    public function test_falha_http_retorna_erro(): void
    {
        Http::fake([
            'stat.ripe.net/data/announced-prefixes/data.json*' =>
                Http::response([], 503),
        ]);

        $this->expectException(RuntimeException::class);

        (new BgpConsultaPrefixosService())->consultar(13335);
    }

    public function test_rejeita_resposta_de_outro_asn(): void
    {
        Http::fake([
            'stat.ripe.net/data/announced-prefixes/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => 'AS9999',
                        'prefixes' => [],
                    ],
                ], 200),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new BgpConsultaPrefixosService())->consultar(13335);
    }

    public function test_rejeita_asn_fora_do_intervalo(): void
    {
        Http::fake();

        $this->expectException(
            InvalidArgumentException::class
        );

        try {
            (new BgpConsultaPrefixosService())->consultar(0);
        } finally {
            Http::assertNothingSent();
        }
    }
}
