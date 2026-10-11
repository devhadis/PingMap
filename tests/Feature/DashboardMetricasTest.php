<?php

namespace Tests\Feature;

use App\Models\Diagnostico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricasTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_retorna_metricas_avancadas(): void
    {
        $usuario = User::factory()->create();

        $diagnostico = Diagnostico::create([
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'sucesso',
            'latencia_media_ms' => 40.1,
            'latencia_minima_ms' => 39.1,
            'latencia_maxima_ms' => 41.1,
            'jitter_ms' => 2.0,
            'amostras' => 2,
            'perda_percentual' => 33.33,
        ]);

        $this->actingAs($usuario)
            ->getJson('/api/v1/diagnosticos/' . $diagnostico->id)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'destino',
                'status',
                'latencia_media_ms',
                'latencia_minima_ms',
                'latencia_maxima_ms',
                'jitter_ms',
                'amostras',
                'perda_percentual',
            ])
            ->assertJsonPath('amostras', 2);
    }

    public function test_dashboard_prepara_metricas_apenas_do_usuario(): void
    {
        $usuario = User::factory()->create();
        $outro = User::factory()->create();

        Diagnostico::create([
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'sucesso',
            'latencia_media_ms' => 40.1,
            'latencia_minima_ms' => 39.1,
            'latencia_maxima_ms' => 41.1,
            'jitter_ms' => 2.0,
            'amostras' => 2,
        ]);

        Diagnostico::create([
            'user_id' => $outro->id,
            'destino' => '8.8.8.8',
            'status' => 'sucesso',
            'latencia_media_ms' => 99.0,
            'amostras' => 3,
        ]);

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('ultimoComResposta', function ($item) {
                return $item !== null
                    && $item->destino === '1.1.1.1';
            })
            ->assertViewHas('serieLatencia', function ($serie) {
                return count($serie) === 1
                    && $serie[0]['destino'] === '1.1.1.1'
                    && $serie[0]['latencia'] === 40.1;
            })
            ->assertDontSee('8.8.8.8');
    }
}
