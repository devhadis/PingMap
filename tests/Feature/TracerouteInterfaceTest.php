<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TracerouteInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_traceroute(): void
    {
        $this->get('/ferramentas/traceroute')
            ->assertRedirect('/login');
    }

    public function test_usuario_autenticado_acessa_traceroute(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('Traceroute')
            ->assertSee('Executar Traceroute');
    }

    public function test_interface_utiliza_api_traceroute(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('/api/v1/traceroute/consultas');
    }

    public function test_interface_possui_token_csrf(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('name="csrf-token"', false);
    }

    public function test_interface_permite_atualizar_historico(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('id="trace-refresh-history"', false)
            ->assertSee('Atualizar histórico');
    }

    public function test_interface_nao_aguarda_polling_para_liberar_formulario(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('void acompanharConsulta(consulta.id);', false);
    }


    public function test_interface_exibe_grafico_de_latencia(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('id="trace-latency-chart"', false);
    }

    public function test_grafico_possui_titulo_acessivel(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('id="trace-latency-title"', false)
            ->assertSee('Latência por salto');
    }

    public function test_interface_possui_renderizacao_do_grafico(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('function renderizarGraficoLatencia(', false);
    }

}
