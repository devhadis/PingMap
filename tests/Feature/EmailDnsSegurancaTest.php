<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmailDnsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class EmailDnsSegurancaTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/email/analises';

    public function test_rejeita_dominio_interno(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson($this->url, [
            'dominio' => 'servidor.internal',
            'tipo' => 'SPF',
        ])->assertUnprocessable();
    }

    public function test_rejeita_dominio_com_comando(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson($this->url, [
            'dominio' => 'gmail.com;id',
            'tipo' => 'SPF',
        ])->assertUnprocessable();
    }

    public function test_rejeita_seletor_dkim_malicioso(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'DKIM',
            'seletor' => '../interno',
        ])->assertUnprocessable();
    }

    public function test_falha_dns_retorna_erro_generico(): void
    {
        $this->actingAs(User::factory()->create());

        $this->mock(
            EmailDnsService::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('analisarSpf')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Falha interna: caminho privado do servidor'
                        )
                    );
            }
        );

        $resposta = $this->postJson($this->url, [
            'dominio' => 'gmail.com',
            'tipo' => 'SPF',
        ]);

        $resposta->assertStatus(503);

        $this->assertStringNotContainsString(
            'caminho privado do servidor',
            $resposta->getContent()
        );
    }
}
