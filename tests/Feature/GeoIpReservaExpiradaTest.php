<?php

namespace Tests\Feature;

use App\Jobs\ExecutarGeoIpSaltos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class GeoIpReservaExpiradaTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserva_expirada_pode_ser_renovada_sem_aceitar_job_antigo(): void
    {
        config()->set('cache.default', 'array');
        Cache::flush();
        Queue::fake();

        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [],
        ]);

        $chave = ExecutarGeoIpSaltos::chave(
            'mtr',
            $consulta->id,
            $usuario->id
        );

        Cache::put($chave, [
            'status' => 'pendente',
            'tentativa' => 'tentativa-antiga',
        ], now()->subSecond());

        $this->assertNull(Cache::get($chave));

        $this->actingAs($usuario)
            ->getJson("/api/v1/mtr/consultas/{$consulta->id}/geo")
            ->assertStatus(202)
            ->assertJsonMissingPath('tentativa');

        Queue::assertPushed(ExecutarGeoIpSaltos::class, 1);

        $reservaNova = Cache::get($chave);

        $this->assertSame('pendente', $reservaNova['status']);
        $this->assertNotSame(
            'tentativa-antiga',
            $reservaNova['tentativa']
        );

        $jobAntigo = new ExecutarGeoIpSaltos(
            'mtr',
            $consulta->id,
            $usuario->id,
            'tentativa-antiga'
        );

        $jobAntigo->failed(new RuntimeException('Falha antiga'));

        $this->assertSame($reservaNova, Cache::get($chave));
    }
}
