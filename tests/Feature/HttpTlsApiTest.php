<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\HttpTlsConsultaService;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class HttpTlsApiTest extends TestCase
{
    private function resultado(): array
    {
        return [
            'dominio' => 'example.com',
            'ips' => ['8.8.8.8'],
            'ip_conectado' => '8.8.8.8',
            'status_http' => 200,
            'tempo_ms' => 120.5,
            'cabecalhos' => [
                'content-type' => 'text/html',
            ],
            'certificado' => [
                'assunto' => 'CN = example.com',
                'emissor' => 'CN = AC de teste',
                'dominios' => ['example.com'],
                'inicio_validade' =>
                    '2026-09-26T22:49:11+00:00',
                'fim_validade' =>
                    '2026-12-25T22:56:35+00:00',
                'algoritmo_assinatura' =>
                    'ecdsa-with-SHA256',
                'certificados_na_cadeia' => 2,
                'dias_restantes' => 62,
                'expirado' => false,
                'alerta_expiracao' => false,
            ],
        ];
    }

    public function test_visitante_nao_pode_consultar(): void
    {
        $this->postJson(
            '/api/v1/http-tls/consultas',
            ['dominio' => 'example.com']
        )->assertUnauthorized();
    }

    public function test_usuario_consulta_https(): void
    {
        $resultado = $this->resultado();

        $this->mock(HttpTlsConsultaService::class)
            ->shouldReceive('consultar')
            ->once()
            ->with('example.com')
            ->andReturn($resultado);

        $this->actingAs(new User())
            ->postJson('/api/v1/http-tls/consultas', [
                'dominio' => 'EXAMPLE.COM',
            ])
            ->assertOk()
            ->assertExactJson([
                'resultado' => $resultado,
            ]);
    }

    public function test_rejeita_dominio_ausente(): void
    {
        $this->mock(HttpTlsConsultaService::class)
            ->shouldNotReceive('consultar');

        $this->actingAs(new User())
            ->postJson(
                '/api/v1/http-tls/consultas',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dominio');
    }

    public function test_rejeita_dominio_invalido(): void
    {
        $this->mock(HttpTlsConsultaService::class)
            ->shouldNotReceive('consultar');

        $this->actingAs(new User())
            ->postJson('/api/v1/http-tls/consultas', [
                'dominio' => 'http://localhost',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dominio');
    }

    public function test_rejeita_ip_privado(): void
    {
        $this->mock(HttpTlsConsultaService::class)
            ->shouldNotReceive('consultar');

        $this->actingAs(new User())
            ->postJson('/api/v1/http-tls/consultas', [
                'dominio' => '127.0.0.1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dominio');
    }

    public function test_falha_dns_retorna_422(): void
    {
        $this->mock(HttpTlsConsultaService::class)
            ->shouldReceive('consultar')
            ->once()
            ->with('example.com')
            ->andThrow(
                new InvalidArgumentException(
                    'IP privado 127.0.0.1'
                )
            );

        $this->actingAs(new User())
            ->postJson('/api/v1/http-tls/consultas', [
                'dominio' => 'example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dominio')
            ->assertDontSee('127.0.0.1');
    }

    public function test_falha_operacional_retorna_503(): void
    {
        $this->mock(HttpTlsConsultaService::class)
            ->shouldReceive('consultar')
            ->once()
            ->with('example.com')
            ->andThrow(
                new RuntimeException(
                    'Detalhes internos confidenciais'
                )
            );

        $this->actingAs(new User())
            ->postJson('/api/v1/http-tls/consultas', [
                'dominio' => 'example.com',
            ])
            ->assertStatus(503)
            ->assertExactJson([
                'message' =>
                    'Não foi possível concluir a consulta HTTP/TLS.',
            ]);
    }
}
