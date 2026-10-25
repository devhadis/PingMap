<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MapaThrottleIntegracaoTest extends TestCase
{
    private function limitador(
        string $metodo,
        string $url
    ): string {
        $rota = Route::getRoutes()->match(
            Request::create($url, $metodo)
        );

        $middlewares = array_values(array_filter(
            $rota->gatherMiddleware(),
            fn ($item) => str_starts_with($item, 'throttle:')
        ));

        $this->assertCount(1, $middlewares);

        return $middlewares[0];
    }

    public function test_limites_nao_bloqueiam_operacoes_diferentes(): void
    {
        // Usuário simulado, sem gravar no banco.
        $usuario = new User();
        $usuario->id = random_int(100000000, 999999999);

        $this->actingAs($usuario);

        $rotas = [
            'mtr-geo' => [
                'GET',
                '/api/v1/mtr/consultas/1/geo',
            ],
            'mtr-criacao' => [
                'POST',
                '/api/v1/mtr/consultas',
            ],
            'traceroute-geo' => [
                'GET',
                '/api/v1/traceroute/consultas/1/geo',
            ],
            'traceroute-criacao' => [
                'POST',
                '/api/v1/traceroute/consultas',
            ],
        ];

        foreach ($rotas as $nome => [$metodo, $url]) {
            $middleware = $this->limitador($metodo, $url);

            // Endpoint de teste com o middleware real.
            Route::get(
                "/__teste/limite/{$nome}",
                fn () => response()->json(['ok' => true])
            )->middleware($middleware);
        }

        $geoMtr = '/__teste/limite/mtr-geo';

        // Consome todas as dez requisições do GeoIP MTR.
        for ($i = 0; $i < 10; $i++) {
            $this->getJson($geoMtr)->assertOk();
        }

        // A décima primeira deve ser bloqueada.
        $this->getJson($geoMtr)->assertStatus(429);

        // As outras operações devem continuar disponíveis.
        $this->getJson(
            '/__teste/limite/mtr-criacao'
        )->assertOk();

        $this->getJson(
            '/__teste/limite/traceroute-geo'
        )->assertOk();

        $this->getJson(
            '/__teste/limite/traceroute-criacao'
        )->assertOk();
    }
}
