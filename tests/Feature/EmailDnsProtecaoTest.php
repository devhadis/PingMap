<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmailDnsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EmailDnsProtecaoTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/email/analises';

    public function test_limita_a_dez_analises_por_minuto(): void
    {
        $this->actingAs(User::factory()->create());

        $this->mock(EmailDnsService::class)
            ->shouldReceive('analisarSpf')
            ->times(10)
            ->with('gmail.com')
            ->andReturn([
                'dominio' => 'gmail.com',
                'tipo' => 'SPF',
                'status' => 'ausente',
                'quantidade' => 0,
                'registros' => [],
                'politica' => null,
            ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson($this->url, [
                'dominio' => 'gmail.com',
                'tipo' => 'SPF',
            ])->assertOk();
        }

        $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'SPF',
        ])->assertStatus(429);
    }

    public function test_rota_utiliza_web_auth_e_throttle(): void
    {
        $rota = Route::getRoutes()->match(
            Request::create($this->url, 'POST')
        );

        $middlewares = $rota->gatherMiddleware();

        $this->assertContains('web', $middlewares);
        $this->assertContains('auth', $middlewares);
        $this->assertContains('throttle:10,1', $middlewares);
    }
}
