<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaMtr;
use App\Models\MtrConsulta;
use App\Models\User;
use App\Services\MtrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ExecutarConsultaMtrTest extends TestCase
{
    use RefreshDatabase;

    private function criarConsulta(
        string $status = 'pendente'
    ): MtrConsulta {
        $usuario = User::factory()->create();

        return $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => $status,
        ]);
    }

    public function test_job_salva_metricas_com_sucesso(): void
    {
        $consulta = $this->criarConsulta();

        $saltos = [
            [
                'salto' => 1,
                'ip' => '1.1.1.1',
                'perda_pct' => 0.0,
                'enviados' => 3,
                'ultima_ms' => 38.5,
                'media_ms' => 37.5,
                'melhor_ms' => 35.5,
                'pior_ms' => 40.5,
            ],
        ];

        $servico = Mockery::mock(MtrService::class);

        $servico->shouldReceive('executar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn($saltos);

        (new ExecutarConsultaMtr($consulta->id))
            ->handle($servico);

        $consulta->refresh();

        $this->assertSame('concluido', $consulta->status);
        $this->assertEquals($saltos, $consulta->saltos);
        $this->assertNull($consulta->erro);
    }

    public function test_job_registra_erro_do_servico(): void
    {
        $consulta = $this->criarConsulta();

        $servico = Mockery::mock(MtrService::class);

        $servico->shouldReceive('executar')
            ->once()
            ->andThrow(new RuntimeException(
                'Falha interna simulada'
            ));

        try {
            (new ExecutarConsultaMtr($consulta->id))
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
            'Falha ao executar MTR.',
            $consulta->erro
        );
        $this->assertNull($consulta->saltos);
    }

    public function test_job_ignora_consulta_ja_processada(): void
    {
        $consulta = $this->criarConsulta('concluido');

        $servico = Mockery::mock(MtrService::class);
        $servico->shouldNotReceive('executar');

        (new ExecutarConsultaMtr($consulta->id))
            ->handle($servico);

        $this->assertSame(
            'concluido',
            $consulta->fresh()->status
        );
    }

    public function test_job_ignora_consulta_inexistente(): void
    {
        $servico = Mockery::mock(MtrService::class);
        $servico->shouldNotReceive('executar');

        (new ExecutarConsultaMtr(999999))
            ->handle($servico);

        $this->assertTrue(true);
    }

    public function test_job_reserva_consulta_atomicamente(): void
    {
        $consulta = $this->criarConsulta();

        $servico = Mockery::mock(MtrService::class);

        $servico->shouldReceive('executar')
            ->once()
            ->andReturn([]);

        DB::enableQueryLog();

        try {
            (new ExecutarConsultaMtr($consulta->id))
                ->handle($servico);

            $consultasSql = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $reservaAtomica = collect($consultasSql)
            ->contains(function (array $registro): bool {
                $sql = strtolower($registro['query']);

                return str_starts_with($sql, 'update')
                    && str_contains($sql, 'mtr_consultas')
                    && str_contains($sql, 'status')
                    && in_array(
                        'pendente',
                        $registro['bindings'],
                        true
                    );
            });

        $this->assertTrue(
            $reservaAtomica,
            'O Job deve reservar a consulta com UPDATE condicional.'
        );
    }

    public function test_job_possui_limites_de_execucao(): void
    {
        $job = new ExecutarConsultaMtr(1);

        $this->assertSame(1, $job->tries);
        $this->assertSame(40, $job->timeout);
    }
}
