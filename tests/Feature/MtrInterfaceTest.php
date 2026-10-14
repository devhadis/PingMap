<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtrInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_interface_mtr(): void
    {
        $this->get('/ferramentas/mtr')
            ->assertRedirect('/login');
    }

    public function test_usuario_autenticado_acessa_interface_mtr(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('MTR')
            ->assertSee('Executar MTR');
    }

    public function test_interface_utiliza_api_mtr(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('/api/v1/mtr/consultas');
    }

    public function test_interface_possui_token_csrf(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('name="csrf-token"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_interface_possui_formulario_mtr(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('id="mtr-form"', false)
            ->assertSee('id="mtr-destino"', false)
            ->assertSee('id="mtr-submit"', false)
            ->assertSee('id="mtr-status"', false);
    }

    public function test_interface_exibe_indicadores_mtr(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('id="mtr-destino-alcancado"', false)
            ->assertSee('id="mtr-perda-destino"', false)
            ->assertSee('id="mtr-latencia-media"', false)
            ->assertSee('id="mtr-total-saltos"', false);
    }

    public function test_interface_exibe_grafico_de_latencia(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('id="mtr-latency-chart"', false)
            ->assertSee('id="mtr-latency-title"', false)
            ->assertSee('Latência média por salto');
    }

    public function test_interface_exibe_tabela_de_metricas_mtr(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('id="mtr-hops"', false)
            ->assertSee('Sondas enviadas')
            ->assertSee('Sem resposta (%)')
            ->assertSee('Média (ms)')
            ->assertSee('Melhor (ms)')
            ->assertSee('Pior (ms)');
    }

    public function test_interface_possui_historico_e_atualizacao(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('id="mtr-history"', false)
            ->assertSee('id="mtr-refresh-history"', false)
            ->assertSee('Atualizar histórico');
    }
}
