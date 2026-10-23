<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RdapConsultaIpService;
use RuntimeException;
use Tests\TestCase;

class RdapApiTest extends TestCase
{
    public function test_visitante_nao_pode_consultar_rdap(): void
    {
        $this->postJson('/api/v1/rdap/consultas', [
            'ip' => '8.8.8.8',
        ])->assertUnauthorized();
    }

    public function test_rejeita_ip_privado(): void
    {
        $this->actingAs(new User())
            ->postJson('/api/v1/rdap/consultas', [
                'ip' => '127.0.0.1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ip');
    }

    public function test_usuario_consulta_ipv4_publico(): void
    {
        $resultado = [
            'handle' => 'REDE-TESTE',
            'nome' => 'Rede de teste',
            'inicio' => '8.8.8.0',
            'fim' => '8.8.8.255',
            'versao' => 'IPv4',
            'pais' => 'US',
        ];

        $this->mock(RdapConsultaIpService::class)
            ->shouldReceive('consultar')
            ->once()
            ->with('8.8.8.8')
            ->andReturn($resultado);

        $this->actingAs(new User())
            ->postJson('/api/v1/rdap/consultas', [
                'ip' => '8.8.8.8',
            ])
            ->assertOk()
            ->assertExactJson([
                'resultado' => $resultado,
            ]);
    }

    public function test_usuario_consulta_ipv6_publico(): void
    {
        $ip = '2606:4700:4700::1111';

        $resultado = [
            'handle' => 'REDE-IPV6-TESTE',
            'nome' => 'Rede IPv6 de teste',
            'inicio' => '2606:4700:4700::',
            'fim' => '2606:4700:4700:ffff:ffff:ffff:ffff:ffff',
            'versao' => 'IPv6',
            'pais' => 'US',
        ];

        $this->mock(RdapConsultaIpService::class)
            ->shouldReceive('consultar')
            ->once()
            ->with($ip)
            ->andReturn($resultado);

        $this->actingAs(new User())
            ->postJson('/api/v1/rdap/consultas', [
                'ip' => $ip,
            ])
            ->assertOk()
            ->assertExactJson([
                'resultado' => $resultado,
            ]);
    }

    public function test_rejeita_ip_ausente(): void
    {
        $this->mock(RdapConsultaIpService::class)
            ->shouldNotReceive('consultar');

        $this->actingAs(new User())
            ->postJson('/api/v1/rdap/consultas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ip');
    }

    public function test_rejeita_ip_malformado(): void
    {
        $this->mock(RdapConsultaIpService::class)
            ->shouldNotReceive('consultar');

        $this->actingAs(new User())
            ->postJson('/api/v1/rdap/consultas', [
                'ip' => '8.8.8.8;comando',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ip');
    }

    public function test_falha_no_servico_retorna_503(): void
    {
        $this->mock(RdapConsultaIpService::class)
            ->shouldReceive('consultar')
            ->once()
            ->with('8.8.8.8')
            ->andThrow(
                new RuntimeException('Erro interno de teste')
            );

        $this->actingAs(new User())
            ->postJson('/api/v1/rdap/consultas', [
                'ip' => '8.8.8.8',
            ])
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Não foi possível concluir a consulta RDAP.',
            ]);
    }
}
