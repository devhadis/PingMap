<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaTraceroute;
use App\Models\TracerouteConsulta;
use App\Models\User;
use App\Services\TracerouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ExecutarConsultaTracerouteTest extends TestCase
{
    use RefreshDatabase;

    private function criarConsulta(
        string $status = 'pendente'
    ): TracerouteConsulta {
        $usuario = User::factory()->create();

        return $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => $status,
        ]);
    }

    public function test_job_salva_saltos_com_sucesso(): void
    {
        $consulta = $this->criarConsulta();

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

        $servico = Mockery::mock(TracerouteService::class);

        $servico->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn($saltos);

        (new ExecutarConsultaTraceroute($consulta->id))
            ->handle($servico);

        $consulta->refresh();

        $this->assertSame('concluido', $consulta->status);
        $this->assertSame($saltos, $consulta->saltos);
        $this->assertNull($consulta->erro);
    }

    public function test_job_registra_erro_do_servico(): void
    {
        $consulta = $this->criarConsulta();

        $servico = Mockery::mock(TracerouteService::class);

        $servico->shouldReceive('executar')
            ->once()
            ->andThrow(new RuntimeException(
                'Falha interna simulada'
            ));

        try {
            (new ExecutarConsultaTraceroute($consulta->id))
                ->handle($servico);

            $this->fail('O Job deveria propagar a exceção.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                'Falha interna simulada',
                $e->getMessage()
            );
        }

        $consulta->refresh();

        $this->assertSame('erro', $consulta->status);
        $this->assertSame(
            'Falha ao executar Traceroute.',
            $consulta->erro
        );
        $this->assertNull($consulta->saltos);
    }

    public function test_job_ignora_consulta_ja_processada(): void
    {
        $consulta = $this->criarConsulta('concluido');

        $servico = Mockery::mock(TracerouteService::class);
        $servico->shouldNotReceive('executar');

        (new ExecutarConsultaTraceroute($consulta->id))
            ->handle($servico);

        $this->assertSame(
            'concluido',
            $consulta->fresh()->status
        );
    }

    public function test_job_ignora_consulta_inexistente(): void
    {
        $servico = Mockery::mock(TracerouteService::class);
        $servico->shouldNotReceive('executar');

        (new ExecutarConsultaTraceroute(999999))
            ->handle($servico);

        $this->assertTrue(true);
    }

    public function test_job_possui_limites_de_execucao(): void
    {
        $job = new ExecutarConsultaTraceroute(1);

        $this->assertSame(1, $job->tries);
        $this->assertSame(30, $job->timeout);
    }
}
