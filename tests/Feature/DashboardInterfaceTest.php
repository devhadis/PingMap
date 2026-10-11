<?php

namespace Tests\Feature;

use App\Models\Diagnostico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exibe_componentes_profissionais(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Network Intelligence')
            ->assertSee('metrica-media')
            ->assertSee('metrica-minima')
            ->assertSee('metrica-maxima')
            ->assertSee('metrica-jitter')
            ->assertSee('grafico-latencia')
            ->assertSee('historico-diagnosticos')
            ->assertSee('Executar Ping');
    }

    public function test_dashboard_exibe_metricas_reais_do_usuario(): void
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
            'latencia_media_ms' => 999.0,
            'amostras' => 3,
        ]);

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('39,10 ms')
            ->assertSee('41,10 ms')
            ->assertSee('2,00 ms')
            ->assertDontSee('8.8.8.8')
            ->assertDontSee('999.0');
    }
}
