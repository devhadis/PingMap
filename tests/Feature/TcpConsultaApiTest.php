<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaTcp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TcpConsultaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_cria_consulta_tcp(): void
    {
        $this->postJson('/api/v1/tcp/consultas', [
            'destino' => '1.1.1.1',
            'porta' => 443,
        ])->assertUnauthorized();
    }

    public function test_usuario_cria_consulta_tcp(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/tcp/consultas', [
                'destino' => '1.1.1.1',
                'porta' => 443,
            ])
            ->assertStatus(202)
            ->assertJsonPath('status', 'pendente');

        $this->assertDatabaseHas('tcp_consultas', [
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'porta' => 443,
            'status' => 'pendente',
        ]);

        Queue::assertPushed(
            ExecutarConsultaTcp::class,
            1
        );
    }

    public function test_api_rejeita_ip_privado(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/tcp/consultas', [
                'destino' => '192.168.1.1',
                'porta' => 443,
            ])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_api_rejeita_porta_nao_permitida(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/tcp/consultas', [
                'destino' => '1.1.1.1',
                'porta' => 22,
            ])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_usuario_nao_consulta_resultado_de_outro(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        $consulta = $usuarioA->tcpConsultas()->create([
            'destino' => '1.1.1.1',
            'porta' => 443,
            'status' => 'conectado',
            'tempo_ms' => 39.351,
        ]);

        $this->actingAs($usuarioB)
            ->getJson(
                '/api/v1/tcp/consultas/'.$consulta->id
            )
            ->assertNotFound();
    }

    public function test_historico_tcp_mostra_apenas_usuario(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        $usuarioA->tcpConsultas()->create([
            'destino' => '1.1.1.1',
            'porta' => 443,
            'status' => 'conectado',
        ]);

        $usuarioB->tcpConsultas()->create([
            'destino' => '8.8.8.8',
            'porta' => 80,
            'status' => 'recusado',
        ]);

        $this->actingAs($usuarioA)
            ->getJson('/api/v1/tcp/consultas')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'destino' => '1.1.1.1',
            ])
            ->assertJsonMissing([
                'destino' => '8.8.8.8',
            ]);
    }

    public function test_usuario_consulta_seu_proprio_resultado(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tcpConsultas()->create([
            'destino' => '1.1.1.1',
            'porta' => 443,
            'status' => 'conectado',
            'tempo_ms' => 39.351,
        ]);

        $this->actingAs($usuario)
            ->getJson('/api/v1/tcp/consultas/'.$consulta->id)
            ->assertOk()
            ->assertJsonPath('id', $consulta->id)
            ->assertJsonPath('destino', '1.1.1.1')
            ->assertJsonPath('status', 'conectado');
    }
}
