<?php

namespace Tests\Feature;

use App\Services\PingService;
use Tests\TestCase;

class PingMapCliTest extends TestCase
{
    public function test_cli_executa_ping_com_sucesso(): void
    {
        $this->mock(PingService::class)
            ->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([
                'destino' => '1.1.1.1',
                'status' => 'sucesso',
                'perda_percentual' => 0.0,
                'latencia_media_ms' => 12.5,
                'latencia_minima_ms' => 11.0,
                'latencia_maxima_ms' => 14.0,
                'jitter_ms' => 3.0,
                'amostras' => 3,
            ]);

        $this->artisan('pingmap:ping', [
            'destino' => '1.1.1.1',
        ])->assertExitCode(0);
    }

    public function test_cli_rejeita_ip_privado(): void
    {
        $this->artisan('pingmap:ping', [
            'destino' => '192.168.1.1',
        ])->assertExitCode(1);
    }

    public function test_cli_rejeita_entrada_invalida(): void
    {
        $this->artisan('pingmap:ping', [
            'destino' => '127.0.0.1;whoami',
        ])->assertExitCode(1);
    }

    public function test_cli_retorna_erro_se_ping_nao_responder(): void
    {
        $this->mock(PingService::class)
            ->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([
                'destino' => '1.1.1.1',
                'status' => 'sem_resposta',
                'perda_percentual' => 100.0,
                'latencia_media_ms' => null,
                'latencia_minima_ms' => null,
                'latencia_maxima_ms' => null,
                'jitter_ms' => null,
                'amostras' => 0,
            ]);

        $this->artisan('pingmap:ping', [
            'destino' => '1.1.1.1',
        ])->assertExitCode(1);
    }
}
