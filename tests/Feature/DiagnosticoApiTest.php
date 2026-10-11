<?php

namespace Tests\Feature;

use App\Jobs\ExecutarDiagnostico;
use App\Models\Diagnostico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DiagnosticoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_cria_diagnostico(): void
    {
        $this->postJson('/api/v1/diagnosticos', [
            'destino' => '1.1.1.1',
        ])->assertUnauthorized();
    }

    public function test_visitante_nao_consulta_diagnostico(): void
    {
        $usuario = User::factory()->create();

        $diagnostico = Diagnostico::create([
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $this->getJson(
            "/api/v1/diagnosticos/{$diagnostico->id}"
        )->assertUnauthorized();
    }

    public function test_rejeita_ip_privado(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/diagnosticos', [
                'destino' => '192.168.1.1',
            ])->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_cria_diagnostico_do_usuario(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $resposta = $this->actingAs($usuario)
            ->postJson('/api/v1/diagnosticos', [
                'destino' => '1.1.1.1',
            ])->assertAccepted();

        $id = $resposta->json('id');

        $this->assertDatabaseHas('diagnosticos', [
            'id' => $id,
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        Queue::assertPushed(
            ExecutarDiagnostico::class,
            fn ($job) => $job->diagnosticoId === $id
        );
    }

    public function test_usuario_consulta_proprio_diagnostico(): void
    {
        $usuario = User::factory()->create();

        $diagnostico = Diagnostico::create([
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'sucesso',
        ]);

        $this->actingAs($usuario)
            ->getJson("/api/v1/diagnosticos/{$diagnostico->id}")
            ->assertOk()
            ->assertJsonPath('id', $diagnostico->id);
    }

    public function test_usuario_nao_consulta_dados_de_outro(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        $diagnostico = Diagnostico::create([
            'user_id' => $usuarioA->id,
            'destino' => '1.1.1.1',
            'status' => 'sucesso',
        ]);

        $this->actingAs($usuarioB)
            ->getJson("/api/v1/diagnosticos/{$diagnostico->id}")
            ->assertNotFound();
    }

    public function test_token_antigo_nao_autoriza_acesso(): void
    {
        $this->withToken('token-antigo')
            ->postJson('/api/v1/diagnosticos', [
                'destino' => '1.1.1.1',
            ])->assertUnauthorized();
    }

    public function test_diagnostico_antigo_sem_dono_fica_privado(): void
    {
        $usuario = User::factory()->create();

        $diagnostico = Diagnostico::create([
            'destino' => '1.1.1.1',
            'status' => 'sucesso',
        ]);

        $this->actingAs($usuario)
            ->getJson("/api/v1/diagnosticos/{$diagnostico->id}")
            ->assertNotFound();
    }
}
