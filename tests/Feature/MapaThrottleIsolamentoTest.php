<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MapaThrottleIsolamentoTest extends TestCase
{
    public function test_mtr_e_traceroute_possuem_limites_independentes(): void
    {
        $casos = [
            [
                'POST',
                '/api/v1/mtr/consultas',
                'throttle:10,1,mtr-criacao',
            ],
            [
                'GET',
                '/api/v1/mtr/consultas/1/geo',
                'throttle:10,1,mtr-geo',
            ],
            [
                'POST',
                '/api/v1/traceroute/consultas',
                'throttle:10,1,traceroute-criacao',
            ],
            [
                'GET',
                '/api/v1/traceroute/consultas/1/geo',
                'throttle:10,1,traceroute-geo',
            ],
        ];

        foreach ($casos as [$metodo, $url, $middleware]) {
            $requisicao = Request::create($url, $metodo);

            $rota = Route::getRoutes()->match($requisicao);

            $this->assertContains(
                $middleware,
                $rota->gatherMiddleware(),
                "Limite incorreto na rota {$metodo} {$url}"
            );
        }
    }
}
