<?php

namespace Tests\Feature;

use App\Jobs\ExecutarGeoIpSaltos;
use App\Models\User;
use App\Services\GeoIpSaltosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ExecutarGeoIpSaltosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
    }

    public function test_job_enriquece_traceroute_sem_alterar_original(): void
    {
        $usuario = User::factory()->create();

        $originais = [
            ['salto' => 1, 'ip' => '1.1.1.1', 'latencia_ms' => 12],
        ];

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => $originais,
        ]);

        $enriquecidos = [
            [
                'salto' => 1,
                'ip' => '1.1.1.1',
                'latencia_ms' => 12,
                'geo' => [
                    'ip' => '1.1.1.1',
                    'latitude' => -27.47,
                    'longitude' => 153.03,
                ],
            ],
        ];

        $servico = Mockery::mock(GeoIpSaltosService::class);
        $servico->shouldReceive('enriquecer')
            ->once()
            ->with($originais)
            ->andReturn($enriquecidos);

        $tentativa = $this->reservarTentativaGeoIp(
            'traceroute',
            $consulta->id,
            $usuario->id
        );

        $job = new ExecutarGeoIpSaltos(
            'traceroute',
            $consulta->id,
            $usuario->id,
            $tentativa
        );

        $job->handle($servico);

        $resultado = Cache::get(
            ExecutarGeoIpSaltos::chave(
                'traceroute',
                $consulta->id,
                $usuario->id
            )
        );

        $this->assertSame('concluido', $resultado['status']);
        $this->assertSame($enriquecidos, $resultado['saltos']);
        $this->assertSame(
            $originais,
            $consulta->fresh()->saltos
        );
    }

    public function test_job_enriquece_mtr(): void
    {
        $usuario = User::factory()->create();

        $originais = [
            ['salto' => 1, 'ip' => '1.1.1.1'],
        ];

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => $originais,
        ]);

        $servico = Mockery::mock(GeoIpSaltosService::class);
        $servico->shouldReceive('enriquecer')
            ->once()
            ->with($originais)
            ->andReturn([
                [
                    'salto' => 1,
                    'ip' => '1.1.1.1',
                    'geo' => null,
                ],
            ]);

        $tentativa = $this->reservarTentativaGeoIp(
            'mtr',
            $consulta->id,
            $usuario->id
        );

        $job = new ExecutarGeoIpSaltos(
            'mtr',
            $consulta->id,
            $usuario->id,
            $tentativa
        );

        $job->handle($servico);

        $resultado = Cache::get(
            ExecutarGeoIpSaltos::chave(
                'mtr',
                $consulta->id,
                $usuario->id
            )
        );

        $this->assertSame('concluido', $resultado['status']);
        $this->assertCount(1, $resultado['saltos']);
    }

    public function test_job_rejeita_consulta_de_outro_usuario(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();

        $consulta = $dono->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [],
        ]);

        $servico = Mockery::mock(GeoIpSaltosService::class);
        $servico->shouldNotReceive('enriquecer');

        $job = new ExecutarGeoIpSaltos(
            'traceroute',
            $consulta->id,
            $outro->id
        );

        $job->handle($servico);

        $this->assertNull(Cache::get(
            ExecutarGeoIpSaltos::chave(
                'traceroute',
                $consulta->id,
                $outro->id
            )
        ));
    }

    public function test_job_registra_erro_generico(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $servico = Mockery::mock(GeoIpSaltosService::class);
        $servico->shouldReceive('enriquecer')
            ->once()
            ->andThrow(new RuntimeException('Erro interno sensivel'));

        $tentativa = $this->reservarTentativaGeoIp(
            'traceroute',
            $consulta->id,
            $usuario->id
        );

        $job = new ExecutarGeoIpSaltos(
            'traceroute',
            $consulta->id,
            $usuario->id,
            $tentativa
        );

        try {
            $job->handle($servico);
            $this->fail('Era esperada uma exceção.');
        } catch (RuntimeException $erro) {
            $this->assertSame(
                'Erro interno sensivel',
                $erro->getMessage()
            );
        }

        $resultado = Cache::get(
            ExecutarGeoIpSaltos::chave(
                'traceroute',
                $consulta->id,
                $usuario->id
            )
        );

        $this->assertSame('erro', $resultado['status']);
        $this->assertStringNotContainsString(
            'Erro interno sensivel',
            $resultado['message']
        );
    }

    public function test_job_registra_falha_definitiva(): void
    {
        $usuario = User::factory()->create();

        $tentativa = $this->reservarTentativaGeoIp(
            'traceroute',
            123,
            $usuario->id
        );

        $job = new ExecutarGeoIpSaltos(
            'traceroute',
            123,
            $usuario->id,
            $tentativa
        );

        $job->failed(new RuntimeException('Segredo interno'));

        $resultado = Cache::get(
            ExecutarGeoIpSaltos::chave(
                'traceroute',
                123,
                $usuario->id
            )
        );

        $this->assertSame('erro', $resultado['status']);
        $this->assertStringNotContainsString(
            'Segredo interno',
            $resultado['message']
        );
    }

    public function test_falha_atrasada_nao_apaga_resultado_concluido(): void
    {
        $usuario = User::factory()->create();

        $chave = ExecutarGeoIpSaltos::chave(
            'mtr',
            456,
            $usuario->id
        );

        $concluido = [
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1', 'geo' => null],
            ],
        ];

        Cache::put($chave, $concluido, now()->addHours(6));

        $job = new ExecutarGeoIpSaltos(
            'mtr',
            456,
            $usuario->id
        );

        $job->failed(new RuntimeException('Falha atrasada'));

        $this->assertSame($concluido, Cache::get($chave));
    }


    public function test_job_antigo_nao_sobrescreve_reserva_nova(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $chave = ExecutarGeoIpSaltos::chave(
            'mtr',
            $consulta->id,
            $usuario->id
        );

        $reservaNova = [
            'status' => 'pendente',
            'tentativa' => 'tentativa-nova',
        ];

        Cache::put($chave, $reservaNova, now()->addMinutes(10));

        $this->mock(
            \App\Services\GeoIpSaltosService::class,
            function ($mock) {
                $mock->shouldReceive('enriquecer')
                    ->zeroOrMoreTimes()
                    ->andReturn([]);
            }
        );

        $jobAntigo = new ExecutarGeoIpSaltos(
            'mtr',
            $consulta->id,
            $usuario->id,
            'tentativa-antiga'
        );

        $jobAntigo->handle(
            app(\App\Services\GeoIpSaltosService::class)
        );

        $this->assertSame($reservaNova, Cache::get($chave));
    }

    public function test_falha_antiga_nao_altera_reserva_nova(): void
    {
        $usuario = User::factory()->create();

        $chave = ExecutarGeoIpSaltos::chave(
            'traceroute',
            987,
            $usuario->id
        );

        $reservaNova = [
            'status' => 'pendente',
            'tentativa' => 'tentativa-nova',
        ];

        Cache::put($chave, $reservaNova, now()->addMinutes(10));

        $jobAntigo = new ExecutarGeoIpSaltos(
            'traceroute',
            987,
            $usuario->id,
            'tentativa-antiga'
        );

        $jobAntigo->failed(
            new RuntimeException('Falha da tentativa antiga')
        );

        $this->assertSame($reservaNova, Cache::get($chave));
    }


    private function reservarTentativaGeoIp(
        string $tipo,
        int $consultaId,
        int $usuarioId
    ): string {
        $tentativa = (string) \Illuminate\Support\Str::uuid();

        Cache::put(
            ExecutarGeoIpSaltos::chave(
                $tipo,
                $consultaId,
                $usuarioId
            ),
            [
                'status' => 'pendente',
                'tentativa' => $tentativa,
            ],
            now()->addMinutes(10)
        );

        return $tentativa;
    }

}
