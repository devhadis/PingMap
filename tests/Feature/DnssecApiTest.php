<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DnssecValidadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DnssecApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_nao_autenticado_nao_pode_analisar(): void
    {
        $this->postJson('/api/v1/dnssec/analises', [
            'dominio' => 'cloudflare.com',
        ])->assertUnauthorized();
    }

    public function test_dominio_obrigatorio(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/dnssec/analises', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dominio']);
    }

    public function test_dominio_interno_e_rejeitado(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/dnssec/analises', [
                'dominio' => 'servidor.local',
            ])
            ->assertUnprocessable();
    }

    public function test_usuario_autenticado_recebe_analise(): void
    {
        $this->mock(
            DnssecValidadorService::class,
            function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('executar')
                    ->once()
                    ->with('cloudflare.com')
                    ->andReturn('seguro');
            }
        );

        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/dnssec/analises', [
                'dominio' => 'cloudflare.com',
            ])
            ->assertOk()
            ->assertJsonPath(
                'resultado.dominio',
                'cloudflare.com'
            )
            ->assertJsonPath(
                'resultado.tipo',
                'A'
            )
            ->assertJsonPath(
                'resultado.estado',
                'seguro'
            );
    }

    public function test_erro_operacional_retorna_503(): void
    {
        $this->mock(
            DnssecValidadorService::class,
            function (Mockery\MockInterface $mock) {
                $mock->shouldReceive('executar')
                    ->once()
                    ->andThrow(
                        new \RuntimeException('Falha DNS interna')
                    );
            }
        );

        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/dnssec/analises', [
                'dominio' => 'cloudflare.com',
            ])
            ->assertStatus(503)
            ->assertDontSee('Falha DNS interna');
    }
}
