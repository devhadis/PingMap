<?php

namespace Tests\Feature;

use App\Services\BgpConsultaAsnService;
use App\Services\BgpConsultaPrefixosService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BgpRipeStatFormatoRealTest extends TestCase
{
    public function test_aceita_resource_numerico_no_as_overview(): void
    {
        Http::fake([
            'stat.ripe.net/data/as-overview/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => '3333',
                        'holder' => 'RIPE NCC',
                        'announced' => true,
                    ],
                ]),
        ]);

        $resultado = (new BgpConsultaAsnService())
            ->consultar(3333);

        $this->assertSame(3333, $resultado['asn']);
        $this->assertSame('RIPE NCC', $resultado['nome']);
        $this->assertTrue($resultado['anunciado']);
    }

    public function test_aceita_resource_numerico_nos_prefixos(): void
    {
        Http::fake([
            'stat.ripe.net/data/announced-prefixes/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => '3333',
                        'prefixes' => [
                            ['prefix' => '2a13:27c0::/44'],
                            ['prefix' => '193.0.18.0/23'],
                        ],
                    ],
                ]),
        ]);

        $resultado = (new BgpConsultaPrefixosService())
            ->consultar(3333);

        $this->assertSame(
            ['193.0.18.0/23'],
            $resultado['ipv4']
        );

        $this->assertSame(
            ['2a13:27c0::/44'],
            $resultado['ipv6']
        );
    }
}
