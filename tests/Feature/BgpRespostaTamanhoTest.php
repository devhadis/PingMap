<?php

namespace Tests\Feature;

use App\Services\BgpConsultaAsnService;
use App\Services\BgpConsultaPrefixosService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class BgpRespostaTamanhoTest extends TestCase
{
    public function test_rejeita_resposta_asn_muito_grande(): void
    {
        Http::fake([
            'stat.ripe.net/data/as-overview/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => '3333',
                        'holder' => str_repeat('A', 2097152),
                        'announced' => true,
                    ],
                ]),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        app(BgpConsultaAsnService::class)
            ->consultar(3333);
    }

    public function test_rejeita_resposta_prefixos_muito_grande(): void
    {
        Http::fake([
            'stat.ripe.net/data/announced-prefixes/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => '3333',
                        'prefixes' => [
                            [
                                'prefix' => '193.0.18.0/23',
                                'extra' => str_repeat('A', 2097152),
                            ],
                        ],
                    ],
                ]),
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        app(BgpConsultaPrefixosService::class)
            ->consultar(3333);
    }
}
