<?php

namespace Tests\Feature;

use App\Models\Diagnostico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_usuario_ve_seu_dashboard(): void
    {
        $usuario = User::factory()->create([
            'name' => 'Usuario PingMap',
        ]);

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Usuario PingMap')
            ->assertSee('Executar Ping');
    }

    public function test_historico_mostra_apenas_dados_do_usuario(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        Diagnostico::create([
            'user_id' => $usuarioA->id,
            'destino' => '1.1.1.1',
            'status' => 'sucesso',
        ]);

        Diagnostico::create([
            'user_id' => $usuarioB->id,
            'destino' => '8.8.8.8',
            'status' => 'sucesso',
        ]);

        $this->actingAs($usuarioA)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('1.1.1.1')
            ->assertDontSee('8.8.8.8');
    }
}
