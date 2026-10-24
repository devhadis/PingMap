<?php

namespace Tests\Feature;

use App\Services\BgpConsultaAsnService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use UnexpectedValueException;
use Tests\TestCase;

class BgpConsultaAsnServiceTest extends TestCase
{
    public function test_consulta_asn_com_https(): void
    {
        Http::fake([
            'stat.ripe.net/data/as-overview/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => 'AS13335',
                        'holder' => 'CLOUDFLARENET',
                        'announced' => true,
                    ],
                ], 200),
        ]);

        $resultado = (new BgpConsultaAsnService())
            ->consultar(13335);

        $this->assertSame([
            'asn' => 13335,
            'nome' => 'CLOUDFLARENET',
            'anunciado' => true,
        ], $resultado);

        Http::assertSent(fn ($request) =>
            $request->method() === 'GET' &&
            str_starts_with(
                $request->url(),
                'https://stat.ripe.net/data/as-overview/data.json?'
            ) &&
            str_contains(
                $request->url(),
                'resource=AS13335'
            )
        );

        Http::assertSentCount(1);
    }

    public function test_aceita_asn_sem_anuncios(): void
    {
        Http::fake([
            'stat.ripe.net/data/as-overview/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => 'AS4230',
                        'holder' => null,
                        'announced' => false,
                    ],
                ], 200),
        ]);

        $resultado = (new BgpConsultaAsnService())
            ->consultar(4230);

        $this->assertSame([
            'asn' => 4230,
            'nome' => null,
            'anunciado' => false,
        ], $resultado);
    }

    public function test_falha_http_gera_erro(): void
    {
        Http::fake([
            'stat.ripe.net/data/as-overview/data.json*' =>
                Http::response([], 503),
        ]);

        $this->expectException(RuntimeException::class);

        (new BgpConsultaAsnService())->consultar(13335);
    }

    public function test_rejeita_resposta_incompleta(): void
    {
        Http::fake([
            'stat.ripe.net/data/as-overview/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => 'AS13335',
                    ],
                ], 200),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new BgpConsultaAsnService())->consultar(13335);
    }
}
