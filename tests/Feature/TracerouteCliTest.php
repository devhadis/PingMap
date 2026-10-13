<?php

namespace Tests\Feature;

use App\Services\TracerouteService;
use RuntimeException;
use Tests\TestCase;

class TracerouteCliTest extends TestCase
{
    public function test_cli_executa_traceroute_com_sucesso(): void
    {
        $servico = $this->mock(TracerouteService::class);

        $servico->shouldReceive('validarDestino')
            ->once()
            ->with('1.1.1.1')
            ->andReturn('1.1.1.1');

        $servico->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([
                [
                    'salto' => 1,
                    'ip' => '8.8.8.8',
                    'latencia_ms' => 10.5,
                ],
                [
                    'salto' => 2,
                    'ip' => '1.1.1.1',
                    'latencia_ms' => 25.5,
                ],
            ]);

        $this->artisan('pingmap:traceroute', [
            'destino' => '1.1.1.1',
        ])->assertExitCode(0);
    }

    public function test_cli_exibe_saltos_sem_resposta(): void
    {
        $servico = $this->mock(TracerouteService::class);

        $servico->shouldReceive('validarDestino')
            ->once()
            ->with('1.1.1.1')
            ->andReturn('1.1.1.1');

        $servico->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([
                [
                    'salto' => 1,
                    'ip' => null,
                    'latencia_ms' => null,
                ],
                [
                    'salto' => 2,
                    'ip' => '1.1.1.1',
                    'latencia_ms' => 30.0,
                ],
            ]);

        $this->artisan('pingmap:traceroute', [
            'destino' => '1.1.1.1',
        ])->assertExitCode(0);
    }

    public function test_cli_rejeita_ip_privado(): void
    {
        $this->artisan('pingmap:traceroute', [
            'destino' => '192.168.1.1',
        ])->assertExitCode(1);
    }

    public function test_cli_rejeita_injecao_de_comando(): void
    {
        $this->artisan('pingmap:traceroute', [
            'destino' => '1.1.1.1;whoami',
        ])->assertExitCode(1);
    }

    public function test_cli_trata_falha_do_servico(): void
    {
        $servico = $this->mock(TracerouteService::class);

        $servico->shouldReceive('validarDestino')
            ->once()
            ->with('1.1.1.1')
            ->andReturn('1.1.1.1');

        $servico->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andThrow(new RuntimeException('Falha simulada'));

        $this->artisan('pingmap:traceroute', [
            'destino' => '1.1.1.1',
        ])->assertExitCode(1);
    }

    public function test_cli_trata_resultado_sem_saltos(): void
    {
        $servico = $this->mock(TracerouteService::class);

        $servico->shouldReceive('validarDestino')
            ->once()
            ->with('1.1.1.1')
            ->andReturn('1.1.1.1');

        $servico->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([]);

        $this->artisan('pingmap:traceroute', [
            'destino' => '1.1.1.1',
        ])->assertExitCode(1);
    }
}
