<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaTraceroute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TracerouteConsultaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_pode_criar_consulta(): void
    {
        $this->postJson('/api/v1/traceroute/consultas', [
            'destino' => '1.1.1.1',
        ])->assertUnauthorized();
    }

    public function test_visitante_nao_pode_listar_consultas(): void
    {
        $this->getJson('/api/v1/traceroute/consultas')
            ->assertUnauthorized();
    }

    public function test_visitante_nao_pode_ver_consulta(): void
    {
        $this->getJson('/api/v1/traceroute/consultas/1')
            ->assertUnauthorized();
    }

    public function test_usuario_cria_consulta_e_enfileira_job(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $resposta = $this->actingAs($usuario)
            ->postJson('/api/v1/traceroute/consultas', [
                'destino' => '1.1.1.1',
            ]);

        $resposta->assertStatus(202)
            ->assertJsonPath('destino', '1.1.1.1')
            ->assertJsonPath('status', 'pendente')
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('traceroute_consultas', [
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $id = $resposta->json('id');

        Queue::assertPushed(
            ExecutarConsultaTraceroute::class,
            fn ($job) => $job->consultaId === $id
        );
    }

    public function test_rejeita_destino_privado(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/traceroute/consultas', [
                'destino' => '192.168.1.1',
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('traceroute_consultas', 0);

        Queue::assertNothingPushed();
    }

    public function test_rejeita_destino_local(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/traceroute/consultas', [
                'destino' => '127.0.0.1',
            ])
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_rejeita_destino_invalido(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/traceroute/consultas', [
                'destino' => 'exemplo.com',
            ])
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_usuario_lista_apenas_suas_consultas(): void
    {
        $usuario = User::factory()->create();
        $outro = User::factory()->create();

        $minha = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
        ]);

        $outro->tracerouteConsultas()->create([
            'destino' => '8.8.8.8',
            'status' => 'concluido',
        ]);

        $resposta = $this->actingAs($usuario)
            ->getJson('/api/v1/traceroute/consultas');

        $resposta->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $minha->id)
            ->assertDontSee('8.8.8.8');
    }

    public function test_usuario_visualiza_sua_consulta_com_saltos(): void
    {
        $usuario = User::factory()->create();

        $saltos = [
            [
                'salto' => 1,
                'ip' => '1.1.1.1',
                'latencia_ms' => 12.5,
            ],
        ];

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => $saltos,
        ]);

        $this->actingAs($usuario)
            ->getJson(
                '/api/v1/traceroute/consultas/' . $consulta->id
            )
            ->assertOk()
            ->assertJsonPath('id', $consulta->id)
            ->assertJsonPath('status', 'concluido')
            ->assertJsonPath('saltos.0.ip', '1.1.1.1')
            ->assertJsonPath('saltos.0.latencia_ms', 12.5);
    }

    public function test_usuario_nao_visualiza_consulta_de_outro(): void
    {
        $usuario = User::factory()->create();
        $outro = User::factory()->create();

        $consulta = $outro->tracerouteConsultas()->create([
            'destino' => '8.8.8.8',
            'status' => 'concluido',
        ]);

        $this->actingAs($usuario)
            ->getJson(
                '/api/v1/traceroute/consultas/' . $consulta->id
            )
            ->assertNotFound();
    }
}
