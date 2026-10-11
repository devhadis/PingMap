<?php

namespace Tests\Feature;

use App\Services\TcpService;
use Tests\TestCase;

class TcpCliTest extends TestCase
{
    public function test_cli_tcp_conectado(): void
    {
        $this->mock(TcpService::class)
            ->shouldReceive('verificar')
            ->once()
            ->with('1.1.1.1', 443)
            ->andReturn([
                'destino' => '1.1.1.1',
                'porta' => 443,
                'protocolo' => 'TCP',
                'status' => 'conectado',
                'tempo_ms' => 20.5,
            ]);

        $this->artisan('pingmap:tcp', [
            'destino' => '1.1.1.1',
            'porta' => '443',
        ])->assertExitCode(0);
    }

    public function test_cli_tcp_recusado(): void
    {
        $this->mock(TcpService::class)
            ->shouldReceive('verificar')
            ->once()
            ->with('1.1.1.1', 443)
            ->andReturn([
                'destino' => '1.1.1.1',
                'porta' => 443,
                'protocolo' => 'TCP',
                'status' => 'recusado',
                'tempo_ms' => 10.0,
            ]);

        $this->artisan('pingmap:tcp', [
            'destino' => '1.1.1.1',
            'porta' => '443',
        ])->assertExitCode(1);
    }

    public function test_cli_tcp_rejeita_ip_privado(): void
    {
        $this->artisan('pingmap:tcp', [
            'destino' => '192.168.1.1',
            'porta' => '443',
        ])->assertExitCode(1);
    }

    public function test_cli_tcp_rejeita_porta_proibida(): void
    {
        $this->artisan('pingmap:tcp', [
            'destino' => '1.1.1.1',
            'porta' => '22',
        ])->assertExitCode(1);
    }

    public function test_cli_tcp_rejeita_porta_nao_numerica(): void
    {
        $this->artisan('pingmap:tcp', [
            'destino' => '1.1.1.1',
            'porta' => '443abc',
        ])->assertExitCode(1);
    }
}
