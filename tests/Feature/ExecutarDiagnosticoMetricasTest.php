<?php

namespace Tests\Feature;

use App\Jobs\ExecutarDiagnostico;
use App\Models\Diagnostico;
use App\Models\User;
use App\Services\PingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ExecutarDiagnosticoMetricasTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_grava_metricas_avancadas(): void
    {
        $usuario = User::factory()->create();

        $diagnostico = Diagnostico::create([
            'user_id' => $usuario->id,
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $ping = Mockery::mock(PingService::class);

        $ping->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([
                'status' => 'sucesso',
                'perda_percentual' => 0.0,
                'latencia_media_ms' => 15.0,
                'latencia_minima_ms' => 10.0,
                'latencia_maxima_ms' => 20.0,
                'jitter_ms' => 7.5,
                'amostras' => 3,
            ]);

        (new ExecutarDiagnostico($diagnostico->id))
            ->handle($ping);

        $diagnostico->refresh();

        $this->assertSame('sucesso', $diagnostico->status);
        $this->assertSame($usuario->id, $diagnostico->user_id);
        $this->assertEquals(15.0, $diagnostico->latencia_media_ms);
        $this->assertEquals(10.0, $diagnostico->latencia_minima_ms);
        $this->assertEquals(20.0, $diagnostico->latencia_maxima_ms);
        $this->assertEquals(7.5, $diagnostico->jitter_ms);
        $this->assertEquals(3, $diagnostico->amostras);
    }
}
