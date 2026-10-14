<?php

namespace Tests\Feature;

use App\Models\MtrConsulta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtrConsultaPersistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_consulta_mtr(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $this->assertDatabaseHas('mtr_consultas', [
            'id' => $consulta->id,
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);
    }

    public function test_consulta_armazena_metricas_mtr_em_json(): void
    {
        $usuario = User::factory()->create();

        $saltos = [
            [
                'salto' => 1,
                'ip' => '192.168.1.1',
                'perda_pct' => 0.0,
                'enviados' => 3,
                'ultima_ms' => 1.5,
                'media_ms' => 1.4,
                'melhor_ms' => 1.2,
                'pior_ms' => 1.6,
            ],
            [
                'salto' => 2,
                'ip' => '1.1.1.1',
                'perda_pct' => 0.0,
                'enviados' => 3,
                'ultima_ms' => 38.0,
                'media_ms' => 37.5,
                'melhor_ms' => 35.0,
                'pior_ms' => 40.0,
            ],
        ];

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => $saltos,
        ]);

        $consulta->refresh();

        $this->assertSame('concluido', $consulta->status);

        $this->assertEquals(
            $saltos,
            $consulta->saltos
        );
    }

    public function test_consulta_pertence_ao_usuario(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $this->assertTrue(
            $consulta->usuario->is($usuario)
        );
    }

    public function test_historico_mtr_isolado_por_usuario(): void
    {
        $usuarioA = User::factory()->create();
        $usuarioB = User::factory()->create();

        $usuarioA->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $usuarioB->mtrConsultas()->create([
            'destino' => '8.8.8.8',
            'status' => 'pendente',
        ]);

        $consultas = $usuarioA->mtrConsultas()->get();

        $this->assertCount(1, $consultas);
        $this->assertSame(
            '1.1.1.1',
            $consultas->first()->destino
        );
    }
}
