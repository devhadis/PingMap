<?php

namespace Tests\Feature;

use App\Jobs\ExecutarGeoIpSaltos;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class GeoIpJobSemTentativaTest extends TestCase
{
    public function test_job_sem_tentativa_nao_cria_erro_sem_reserva(): void
    {
        $job = new ExecutarGeoIpSaltos('mtr', 99991, 99992);
        $chave = ExecutarGeoIpSaltos::chave('mtr', 99991, 99992);

        Cache::forget($chave);

        $job->failed(new RuntimeException('Falha simulada'));

        $this->assertNull(Cache::get($chave));
    }

    public function test_job_sem_tentativa_nao_altera_reserva_legada(): void
    {
        $job = new ExecutarGeoIpSaltos('mtr', 99993, 99994);
        $chave = ExecutarGeoIpSaltos::chave('mtr', 99993, 99994);

        Cache::put($chave, [
            'status' => 'pendente',
        ], now()->addMinutes(10));

        $job->failed(new RuntimeException('Falha simulada'));

        $this->assertSame(
            ['status' => 'pendente'],
            Cache::get($chave)
        );
    }
}
