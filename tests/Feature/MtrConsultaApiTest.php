<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaMtr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MtrConsultaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_pode_criar_consulta(): void
    {
        $this->postJson('/api/v1/mtr/consultas', [
            'destino' => '1.1.1.1',
        ])->assertUnauthorized();
    }

    public function test_visitante_nao_pode_listar_consultas(): void
    {
        $this->getJson('/api/v1/mtr/consultas')
            ->assertUnauthorized();
    }

    public function test_visitante_nao_pode_ver_consulta(): void
    {
        $this->getJson('/api/v1/mtr/consultas/1')
            ->assertUnauthorized();
    }

    public function test_usuario_cria_consulta_e_enfileira_job(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $resposta = $this->actingAs($usuario)
            ->postJson('/api/v1/mtr/consultas', [
                'destino' => '1.1.1.1',
            ]);

        $resposta->assertAccepted()
            ->assertJsonPath('destino', '1.1.1.1')
            ->assertJsonPath('status', 'pendente')
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('mtr_consultas', [
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $id = $resposta->json('id');

        Queue::assertPushed(
            ExecutarConsultaMtr::class,
            fn ($job) => $job->consultaId === $id
        );
    }

    public function test_rejeita_destino_privado(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/mtr/consultas', [
                'destino' => '192.168.1.1',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('mtr_consultas', 0);
        Queue::assertNothingPushed();
    }

    public function test_rejeita_destino_local(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/mtr/consultas', [
                'destino' => '127.0.0.1',
            ])
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_rejeita_injecao_de_comando(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/mtr/consultas', [
                'destino' => '1.1.1.1;whoami',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('mtr_consultas', 0);
        Queue::assertNothingPushed();
    }

    public function test_usuario_lista_apenas_suas_consultas(): void
    {
        $usuario = User::factory()->create();
        $outro = User::factory()->create();

        $minha = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
        ]);

        $outro->mtrConsultas()->create([
            'destino' => '8.8.8.8',
            'status' => 'concluido',
        ]);

        $this->actingAs($usuario)
            ->getJson('/api/v1/mtr/consultas')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $minha->id)
            ->assertDontSee('8.8.8.8');
    }

    public function test_usuario_visualiza_sua_consulta(): void
    {
        $usuario = User::factory()->create();

        $saltos = [
            [
                'salto' => 1,
                'ip' => '1.1.1.1',
                'perda_pct' => 0.0,
                'enviados' => 3,
                'ultima_ms' => 38.5,
                'media_ms' => 37.5,
                'melhor_ms' => 35.5,
                'pior_ms' => 40.5,
            ],
        ];

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => $saltos,
        ]);

        $this->actingAs($usuario)
            ->getJson('/api/v1/mtr/consultas/' . $consulta->id)
            ->assertOk()
            ->assertJsonPath('id', $consulta->id)
            ->assertJsonPath('status', 'concluido')
            ->assertJsonPath('saltos.0.ip', '1.1.1.1')
            ->assertJsonPath('saltos.0.media_ms', 37.5);
    }

    public function test_usuario_nao_visualiza_consulta_de_outro(): void
    {
        $usuario = User::factory()->create();
        $outro = User::factory()->create();

        $consulta = $outro->mtrConsultas()->create([
            'destino' => '8.8.8.8',
            'status' => 'concluido',
        ]);

        $this->actingAs($usuario)
            ->getJson('/api/v1/mtr/consultas/' . $consulta->id)
            ->assertNotFound();
    }

    public function test_rejeita_destino_ausente(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/mtr/consultas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['destino']);

        $this->assertDatabaseCount('mtr_consultas', 0);
        Queue::assertNothingPushed();
    }

    public function test_historico_retorna_no_maximo_vinte(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < 25; $i++) {
            $usuario->mtrConsultas()->create([
                'destino' => '1.1.1.1',
                'status' => 'concluido',
            ]);
        }

        $this->actingAs($usuario)
            ->getJson('/api/v1/mtr/consultas')
            ->assertOk()
            ->assertJsonCount(20);
    }

    public function test_limita_criacao_a_dez_por_minuto(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/mtr/consultas', [
                'destino' => '1.1.1.1',
            ])->assertAccepted();
        }

        $this->postJson('/api/v1/mtr/consultas', [
            'destino' => '1.1.1.1',
        ])->assertStatus(429);

        $this->assertDatabaseCount('mtr_consultas', 10);

        Queue::assertPushed(
            ExecutarConsultaMtr::class,
            10
        );
    }
}
