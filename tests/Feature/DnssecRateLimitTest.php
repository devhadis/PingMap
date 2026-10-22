<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DnssecValidadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnssecRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_bloqueia_apos_dez_requisicoes(): void
    {
        $this->mock(DnssecValidadorService::class)
            ->shouldReceive('executar')
            ->times(10)
            ->with('cloudflare.com')
            ->andReturn('seguro');

        $this->actingAs(User::factory()->create());

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson('/api/v1/dnssec/analises', [
                'dominio' => 'cloudflare.com',
            ])->assertOk();
        }

        $this->postJson('/api/v1/dnssec/analises', [
            'dominio' => 'cloudflare.com',
        ])->assertStatus(429);
    }
}
