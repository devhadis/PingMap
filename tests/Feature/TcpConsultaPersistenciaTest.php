<?php

namespace Tests\Feature;

use App\Models\TcpConsulta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TcpConsultaPersistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_consulta_tcp(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tcpConsultas()->create([
            'destino' => '1.1.1.1',
            'porta' => 443,
            'status' => 'pendente',
        ]);

        $this->assertDatabaseHas('tcp_consultas', [
            'id' => $consulta->id,
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'porta' => 443,
            'status' => 'pendente',
        ]);
    }

    public function test_consulta_tcp_pertence_ao_usuario(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tcpConsultas()->create([
            'destino' => '8.8.8.8',
            'porta' => 80,
            'status' => 'pendente',
        ]);

        $this->assertEquals(
            $usuario->id,
            $consulta->usuario->id
        );
    }

    public function test_historico_tcp_isolado_por_usuario(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        $usuarioA->tcpConsultas()->create([
            'destino' => '1.1.1.1',
            'porta' => 443,
            'status' => 'pendente',
        ]);

        $usuarioB->tcpConsultas()->create([
            'destino' => '8.8.8.8',
            'porta' => 80,
            'status' => 'pendente',
        ]);

        $this->assertSame(
            1,
            $usuarioA->tcpConsultas()->count()
        );

        $this->assertSame(
            '1.1.1.1',
            $usuarioA->tcpConsultas()->first()->destino
        );

        $this->assertSame(
            1,
            $usuarioB->tcpConsultas()->count()
        );
    }
}
