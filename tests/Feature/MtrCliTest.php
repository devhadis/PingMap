<?php

namespace Tests\Feature;

use App\Services\MtrService;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class MtrCliTest extends TestCase
{
    public function test_exibe_resultado_mtr(): void
    {
        $servico = $this->mock(MtrService::class);

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
                    'ip' => '192.168.1.1',
                    'perda_pct' => 0.0,
                    'enviados' => 3,
                    'ultima_ms' => 2.0,
                    'media_ms' => 1.9,
                    'melhor_ms' => 1.7,
                    'pior_ms' => 2.1,
                ],
                [
                    'salto' => 2,
                    'ip' => '1.1.1.1',
                    'perda_pct' => 0.0,
                    'enviados' => 3,
                    'ultima_ms' => 40.0,
                    'media_ms' => 38.5,
                    'melhor_ms' => 36.0,
                    'pior_ms' => 40.0,
                ],
            ]);

        $this->artisan('pingmap:mtr', ['destino' => '1.1.1.1'])
            ->expectsOutput('MTR para 1.1.1.1')
            ->expectsOutput('Destino alcançado.')
            ->assertExitCode(0);
    }

    public function test_informa_destino_nao_confirmado(): void
    {
        $servico = $this->mock(MtrService::class);

        $servico->shouldReceive('validarDestino')
            ->andReturn('1.1.1.1');

        $servico->shouldReceive('executar')
            ->andReturn([
                [
                    'salto' => 1,
                    'ip' => null,
                    'perda_pct' => 100.0,
                    'enviados' => 3,
                    'ultima_ms' => null,
                    'media_ms' => null,
                    'melhor_ms' => null,
                    'pior_ms' => null,
                ],
            ]);

        $this->artisan('pingmap:mtr', ['destino' => '1.1.1.1'])
            ->expectsOutput('Destino não confirmado.')
            ->assertExitCode(0);
    }

    public function test_rejeita_destino_invalido(): void
    {
        $servico = $this->mock(MtrService::class);

        $servico->shouldReceive('validarDestino')
            ->andThrow(new InvalidArgumentException());

        $servico->shouldNotReceive('executar');

        $this->artisan('pingmap:mtr', ['destino' => '127.0.0.1'])
            ->expectsOutput('Não foi possível executar o MTR.')
            ->assertExitCode(1);
    }

    public function test_rejeita_resultado_vazio(): void
    {
        $servico = $this->mock(MtrService::class);

        $servico->shouldReceive('validarDestino')
            ->andReturn('1.1.1.1');

        $servico->shouldReceive('executar')
            ->andReturn([]);

        $this->artisan('pingmap:mtr', ['destino' => '1.1.1.1'])
            ->expectsOutput('Nenhum salto foi retornado.')
            ->assertExitCode(1);
    }

    public function test_trata_falha_de_execucao(): void
    {
        $servico = $this->mock(MtrService::class);

        $servico->shouldReceive('validarDestino')
            ->andReturn('1.1.1.1');

        $servico->shouldReceive('executar')
            ->andThrow(new RuntimeException('Erro interno'));

        $this->artisan('pingmap:mtr', ['destino' => '1.1.1.1'])
            ->expectsOutput('Não foi possível executar o MTR.')
            ->assertExitCode(1);
    }

    public function test_cli_executa_mtr_ipv6(): void
    {
        $destino = '2606:4700:4700::1111';

        $servico = $this->mock(\App\Services\MtrService::class);

        $servico->shouldReceive('validarDestino')
            ->once()
            ->with($destino)
            ->andReturn($destino);

        $servico->shouldReceive('executar')
            ->once()
            ->with($destino)
            ->andReturn([
                [
                    'salto' => 1,
                    'ip' => $destino,
                    'enviados' => 3,
                    'perda_pct' => 0.0,
                    'media_ms' => 12.5,
                    'melhor_ms' => 11.0,
                    'pior_ms' => 14.0,
                ],
            ]);

        $this->artisan('pingmap:mtr', [
            'destino' => $destino,
        ])->assertExitCode(0);
    }
}
