<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BgpConsultaAsnService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class BgpProtecaoTest extends TestCase
{
    public function test_limita_dez_consultas_por_minuto(): void
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

            'stat.ripe.net/data/announced-prefixes/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => '3333',
                        'prefixes' => [],
                    ],
                ]),
        ]);

        $usuario = User::factory()->make();

        $this->actingAs($usuario);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(
                '/api/v1/bgp/consultas',
                ['asn' => 'AS3333']
            )->assertOk();
        }

        $this->postJson(
            '/api/v1/bgp/consultas',
            ['asn' => 'AS3333']
        )->assertStatus(429);
    }

    public function test_rejeita_dados_de_outro_asn(): void
    {
        Http::fake([
            'stat.ripe.net/data/as-overview/data.json*' =>
                Http::response([
                    'status' => 'ok',
                    'data' => [
                        'resource' => '9999',
                        'holder' => 'Outra organização',
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
}
