<?php

namespace Tests\Feature;

use App\Models\TracerouteConsulta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TracerouteConsultaPersistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_consulta_traceroute(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $this->assertDatabaseHas('traceroute_consultas', [
            'id' => $consulta->id,
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);
    }

    public function test_consulta_armazena_saltos_em_json(): void
    {
        $usuario = User::factory()->create();

        $saltos = [
            [
                'salto' => 1,
                'ip' => '192.168.1.1',
                'latencia_ms' => 1.25,
            ],
            [
                'salto' => 2,
                'ip' => '1.1.1.1',
                'latencia_ms' => 12.5,
            ],
        ];

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => $saltos,
        ]);

        $consulta->refresh();

        $this->assertSame('concluido', $consulta->status);
        $this->assertSame($saltos, $consulta->saltos);
    }

    public function test_consulta_pertence_ao_usuario(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $this->assertTrue(
            $consulta->usuario->is($usuario)
        );
    }

    public function test_historico_traceroute_isolado_por_usuario(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        $usuarioA->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $usuarioB->tracerouteConsultas()->create([
            'destino' => '8.8.8.8',
            'status' => 'pendente',
        ]);

        $consultas = $usuarioA->tracerouteConsultas()->get();

        $this->assertCount(1, $consultas);
        $this->assertSame(
            '1.1.1.1',
            $consultas->first()->destino
        );
    }
}
