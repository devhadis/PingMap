<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaDns;
use App\Models\DnsConsulta;
use App\Models\User;
use App\Services\DnsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ExecutarConsultaDnsTest extends TestCase
{
    use RefreshDatabase;

    private function criarConsulta(): DnsConsulta
    {
        $usuario = User::factory()->create();

        return DnsConsulta::create([
            'user_id' => $usuario->id,
            'dominio' => 'cloudflare.com',
            'tipo' => 'A',
            'status' => 'pendente',
        ]);
    }

    public function test_job_salva_registros_dns(): void
    {
        $consulta = $this->criarConsulta();

        $dns = new DnsService(
            resolvedor: fn () => [[
                'host' => 'cloudflare.com',
                'type' => 'A',
                'ip' => '1.1.1.1',
                'ttl' => 300,
            ]]
        );

        (new ExecutarConsultaDns($consulta->id))->handle($dns);

        $consulta->refresh();

        $this->assertSame('sucesso', $consulta->status);
        $this->assertSame(1, $consulta->quantidade);
        $this->assertSame(
            '1.1.1.1',
            $consulta->registros[0]['ip']
        );
        $this->assertNull($consulta->erro);
    }

    public function test_job_salva_consulta_sem_registros(): void
    {
        $consulta = $this->criarConsulta();

        $dns = new DnsService(
            resolvedor: fn () => []
        );

        (new ExecutarConsultaDns($consulta->id))->handle($dns);

        $consulta->refresh();

        $this->assertSame('sem_registros', $consulta->status);
        $this->assertSame(0, $consulta->quantidade);
        $this->assertSame([], $consulta->registros);
    }

    public function test_job_registra_erro_do_resolvedor(): void
    {
        $consulta = $this->criarConsulta();

        $dns = new DnsService(
            resolvedor: fn () => false
        );

        try {
            (new ExecutarConsultaDns($consulta->id))->handle($dns);
            $this->fail('Era esperada uma falha DNS.');
        } catch (RuntimeException $e) {
            $consulta->refresh();

            $this->assertSame('erro', $consulta->status);
            $this->assertSame(
                'Falha ao executar consulta DNS.',
                $consulta->erro
            );
        }
    }
}
