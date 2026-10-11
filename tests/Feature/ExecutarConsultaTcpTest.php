<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaTcp;
use App\Models\TcpConsulta;
use App\Models\User;
use App\Services\TcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ExecutarConsultaTcpTest extends TestCase
{
    use RefreshDatabase;

    private function criarConsulta(
        string $status = 'pendente'
    ): TcpConsulta {
        $usuario = User::factory()->create();

        return $usuario->tcpConsultas()->create([
            'destino' => '1.1.1.1',
            'porta' => 443,
            'status' => $status,
        ]);
    }

    public function test_job_salva_conexao_tcp_com_sucesso(): void
    {
        $consulta = $this->criarConsulta();

        $tcp = Mockery::mock(TcpService::class);

        $tcp->shouldReceive('verificar')
            ->once()
            ->with('1.1.1.1', 443)
            ->andReturn([
                'destino' => '1.1.1.1',
                'porta' => 443,
                'protocolo' => 'TCP',
                'status' => 'conectado',
                'tempo_ms' => 39.351,
            ]);

        (new ExecutarConsultaTcp($consulta->id))
            ->handle($tcp);

        $this->assertDatabaseHas('tcp_consultas', [
            'id' => $consulta->id,
            'status' => 'conectado',
            'tempo_ms' => 39.351,
            'erro' => null,
        ]);
    }

    public function test_job_salva_conexao_recusada(): void
    {
        $consulta = $this->criarConsulta();

        $tcp = Mockery::mock(TcpService::class);

        $tcp->shouldReceive('verificar')
            ->once()
            ->andReturn([
                'destino' => '1.1.1.1',
                'porta' => 443,
                'protocolo' => 'TCP',
                'status' => 'recusado',
                'tempo_ms' => 12.500,
            ]);

        (new ExecutarConsultaTcp($consulta->id))
            ->handle($tcp);

        $this->assertDatabaseHas('tcp_consultas', [
            'id' => $consulta->id,
            'status' => 'recusado',
            'tempo_ms' => 12.500,
        ]);
    }

    public function test_job_registra_erro_do_servico(): void
    {
        $consulta = $this->criarConsulta();

        $tcp = Mockery::mock(TcpService::class);

        $tcp->shouldReceive('verificar')
            ->once()
            ->andThrow(
                new RuntimeException('Erro interno simulado')
            );

        try {
            (new ExecutarConsultaTcp($consulta->id))
                ->handle($tcp);

            $this->fail(
                'O Job deveria propagar a exceção.'
            );
        } catch (RuntimeException $e) {
            $this->assertSame(
                'Erro interno simulado',
                $e->getMessage()
            );
        }

        $consulta->refresh();

        $this->assertSame('erro', $consulta->status);

        $this->assertSame(
            'Falha ao executar consulta TCP.',
            $consulta->erro
        );
    }

    public function test_job_ignora_consulta_ja_processada(): void
    {
        $consulta = $this->criarConsulta('conectado');

        $tcp = Mockery::mock(TcpService::class);

        $tcp->shouldNotReceive('verificar');

        (new ExecutarConsultaTcp($consulta->id))
            ->handle($tcp);

        $this->assertSame(
            'conectado',
            $consulta->fresh()->status
        );
    }

    public function test_job_ignora_consulta_inexistente(): void
    {
        $tcp = Mockery::mock(TcpService::class);

        $tcp->shouldNotReceive('verificar');

        (new ExecutarConsultaTcp(999999))
            ->handle($tcp);

        $this->assertTrue(true);
    }
}
