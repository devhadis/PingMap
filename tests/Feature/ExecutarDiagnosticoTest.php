<?php

namespace Tests\Feature;

use App\Jobs\ExecutarDiagnostico;
use App\Models\Diagnostico;
use App\Services\PingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutarDiagnosticoTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_salva_diagnostico_com_sucesso(): void
    {
        $diagnostico = Diagnostico::create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $saida = "3 packets transmitted, 3 received, 0% packet loss\n"
            . "rtt min/avg/max/mdev = 10.000/20.500/30.000/5.000 ms\n";

        $ping = new PingService(
            fn ($processo) => [
                'saida' => $saida,
                'codigo' => 0,
            ]
        );

        (new ExecutarDiagnostico($diagnostico->id))->handle($ping);

        $this->assertDatabaseHas('diagnosticos', [
            'id' => $diagnostico->id,
            'status' => 'sucesso',
            'perda_percentual' => 0,
            'latencia_media_ms' => 20.5,
        ]);
    }

    public function test_job_salva_diagnostico_sem_resposta(): void
    {
        $diagnostico = Diagnostico::create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $ping = new PingService(
            fn ($processo) => [
                'saida' => "3 packets transmitted, 0 received, 100% packet loss\n",
                'codigo' => 1,
            ]
        );

        (new ExecutarDiagnostico($diagnostico->id))->handle($ping);

        $this->assertDatabaseHas('diagnosticos', [
            'id' => $diagnostico->id,
            'status' => 'sem_resposta',
            'perda_percentual' => 100,
            'latencia_media_ms' => null,
        ]);
    }
}
