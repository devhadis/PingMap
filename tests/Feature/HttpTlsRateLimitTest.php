<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\HttpTlsConsultaService;
use Tests\TestCase;

class HttpTlsRateLimitTest extends TestCase
{
    private function usuarioTeste(): User
    {
        $usuario = new User();

        // ID único para não reutilizar limites de testes anteriores.
        $usuario->id = random_int(100000000, 900000000);

        return $usuario;
    }

    private function prepararServico(int $consultas): void
    {
        // Simular a consulta sem acessar a internet.
        $this->mock(HttpTlsConsultaService::class)
            ->shouldReceive('consultar')
            ->times($consultas)
            ->with('example.com')
            ->andReturn([
                'dominio' => 'example.com',
                'status_http' => 200,
                'certificado' => null,
            ]);
    }

    private function consultar()
    {
        return $this->postJson(
            '/api/v1/http-tls/consultas',
            ['dominio' => 'example.com']
        );
    }

    public function test_bloqueia_apos_dez_consultas(): void
    {
        $this->prepararServico(10);

        $this->actingAs($this->usuarioTeste());

        // As primeiras 10 consultas devem funcionar.
        for ($i = 1; $i <= 10; $i++) {
            $this->consultar()->assertOk();
        }

        // A 11ª deve ser bloqueada.
        $this->consultar()->assertStatus(429);
    }

    public function test_limite_e_independente_por_usuario(): void
    {
        $this->prepararServico(11);

        $usuarioA = $this->usuarioTeste();

        $usuarioB = new User();
        $usuarioB->id = $usuarioA->id + 1;

        // Usuário A consome suas 10 consultas.
        $this->actingAs($usuarioA);

        for ($i = 1; $i <= 10; $i++) {
            $this->consultar()->assertOk();
        }

        // Usuário A é bloqueado.
        $this->consultar()->assertStatus(429);

        // Usuário B continua autorizado.
        $this->actingAs($usuarioB);

        $this->consultar()->assertOk();
    }
}
