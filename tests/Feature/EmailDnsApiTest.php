<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmailDnsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class EmailDnsApiTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/email/analises';

    public function test_visitante_nao_pode_analisar(): void
    {
        $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'SPF',
        ])->assertUnauthorized();
    }

    public function test_usuario_analisa_spf(): void
    {
        $this->actingAs(User::factory()->create());

        $this->mock(
            EmailDnsService::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('analisarSpf')
                    ->once()
                    ->with('gmail.com')
                    ->andReturn([
                        'dominio' => 'gmail.com',
                        'tipo' => 'SPF',
                        'status' => 'presente',
                        'politica' => 'fail',
                        'quantidade' => 1,
                        'registros' => ['v=spf1 -all'],
                    ]);
            }
        );

        $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'SPF',
        ])
            ->assertOk()
            ->assertJsonPath('resultado.status', 'presente')
            ->assertJsonPath('resultado.politica', 'fail');
    }

    public function test_dkim_exige_seletor(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'DKIM',
        ])->assertUnprocessable();
    }

    public function test_rejeita_tipo_nao_permitido(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'OUTRO',
        ])->assertUnprocessable();
    }

    public function test_usuario_analisa_dkim(): void
    {
        $this->actingAs(User::factory()->create());

        $this->mock(
            EmailDnsService::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('analisarDkim')
                    ->once()
                    ->with('gmail.com', 'google')
                    ->andReturn([
                        'dominio' => 'gmail.com',
                        'tipo' => 'DKIM',
                        'seletor' => 'google',
                        'status' => 'presente',
                        'quantidade' => 1,
                        'registros' => ['v=DKIM1; p=ABC123'],
                    ]);
            }
        );

        $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'DKIM',
            'seletor' => 'google',
        ])
            ->assertOk()
            ->assertJsonPath('resultado.seletor', 'google');
    }

    public function test_usuario_analisa_dmarc(): void
    {
        $this->actingAs(User::factory()->create());

        $this->mock(
            EmailDnsService::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('analisarDmarc')
                    ->once()
                    ->with('gmail.com')
                    ->andReturn([
                        'dominio' => 'gmail.com',
                        'tipo' => 'DMARC',
                        'status' => 'presente',
                        'politica' => 'reject',
                        'quantidade' => 1,
                        'registros' => ['v=DMARC1; p=reject'],
                    ]);
            }
        );

        $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'DMARC',
        ])
            ->assertOk()
            ->assertJsonPath('resultado.politica', 'reject');
    }
}
