<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BgpConsultaRedeService;
use RuntimeException;
use Tests\TestCase;

class BgpRedeApiTest extends TestCase
{
    private const URL = '/api/v1/bgp/rede/consultas';

    public function test_exige_autenticacao(): void
    {
        $this->postJson(self::URL, [
            'alvo' => '8.8.8.8',
        ])->assertUnauthorized();
    }

    public function test_exige_campo_alvo(): void
    {
        $this->actingAs(new User())
            ->postJson(self::URL, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('alvo');
    }

    public function test_rejeita_entradas_perigosas(): void
    {
        $this->mock(BgpConsultaRedeService::class, function ($mock) {
            $mock->shouldNotReceive('consultar');
        });

        $this->actingAs(new User());

        foreach ([
            '192.168.1.1',
            '8.0.0.0/6',
            '8.8.8.8;whoami',
        ] as $alvo) {
            $this->postJson(self::URL, [
                'alvo' => $alvo,
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('alvo');
        }
    }

    public function test_consulta_ipv4(): void
    {
        $this->mock(BgpConsultaRedeService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                ->once()
                ->with('8.8.8.8')
                ->andReturn([
                    'tipo' => 'ip',
                    'consulta' => '8.8.8.8',
                    'prefixo' => '8.8.8.0/24',
                    'anunciado' => true,
                    'asns' => [15169],
                    'coberto_por_prefixo_maior' => false,
                ]);
        });

        $this->actingAs(new User())
            ->postJson(self::URL, [
                'alvo' => '8.8.8.8',
            ])
            ->assertOk()
            ->assertJsonPath('resultado.tipo', 'ip')
            ->assertJsonPath('resultado.prefixo', '8.8.8.0/24')
            ->assertJsonPath('resultado.asns.0', 15169);
    }

    public function test_consulta_ipv6(): void
    {
        $ip = '2606:4700:4700::1111';

        $this->mock(BgpConsultaRedeService::class, function ($mock) use ($ip) {
            $mock->shouldReceive('consultar')
                ->once()
                ->with($ip)
                ->andReturn([
                    'tipo' => 'ip',
                    'consulta' => $ip,
                    'prefixo' => '2606:4700:4700::/48',
                    'anunciado' => true,
                    'asns' => [13335],
                    'coberto_por_prefixo_maior' => false,
                ]);
        });

        $this->actingAs(new User())
            ->postJson(self::URL, [
                'alvo' => $ip,
            ])
            ->assertOk()
            ->assertJsonPath('resultado.consulta', $ip)
            ->assertJsonPath('resultado.asns.0', 13335);
    }

    public function test_consulta_cidr(): void
    {
        $this->mock(BgpConsultaRedeService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                ->once()
                ->with('8.8.8.0/24')
                ->andReturn([
                    'tipo' => 'cidr',
                    'consulta' => '8.8.8.0/24',
                    'prefixo' => '8.8.8.0/24',
                    'anunciado' => true,
                    'asns' => [15169],
                    'coberto_por_prefixo_maior' => false,
                ]);
        });

        $this->actingAs(new User())
            ->postJson(self::URL, [
                'alvo' => '8.8.8.0/24',
            ])
            ->assertOk()
            ->assertJsonPath('resultado.tipo', 'cidr')
            ->assertJsonPath('resultado.prefixo', '8.8.8.0/24');
    }

    public function test_consulta_dominio(): void
    {
        $this->mock(BgpConsultaRedeService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                ->once()
                ->with('dns.google')
                ->andReturn([
                    'tipo' => 'dominio',
                    'consulta' => 'dns.google',
                    'resultados' => [
                        [
                            'tipo' => 'ip',
                            'consulta' => '8.8.8.8',
                            'prefixo' => '8.8.8.0/24',
                            'anunciado' => true,
                            'asns' => [15169],
                            'coberto_por_prefixo_maior' => false,
                        ],
                    ],
                ]);
        });

        $this->actingAs(new User())
            ->postJson(self::URL, [
                'alvo' => 'DNS.GOOGLE',
            ])
            ->assertOk()
            ->assertJsonPath('resultado.tipo', 'dominio')
            ->assertJsonPath('resultado.consulta', 'dns.google')
            ->assertJsonPath(
                'resultado.resultados.0.asns.0',
                15169
            );
    }

    public function test_falha_externa_retorna_503(): void
    {
        $this->mock(BgpConsultaRedeService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                ->once()
                ->with('8.8.8.8')
                ->andThrow(
                    new RuntimeException('Falha na RIPEstat.')
                );
        });

        $this->actingAs(new User())
            ->postJson(self::URL, [
                'alvo' => '8.8.8.8',
            ])
            ->assertStatus(503)
            ->assertJsonPath(
                'message',
                'Não foi possível concluir a consulta BGP por rede.'
            );
    }

    public function test_limita_dez_consultas_por_minuto(): void
    {
        $this->mock(BgpConsultaRedeService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                ->times(10)
                ->with('8.8.8.8')
                ->andReturn([
                    'tipo' => 'ip',
                    'consulta' => '8.8.8.8',
                    'prefixo' => '8.8.8.0/24',
                    'anunciado' => true,
                    'asns' => [15169],
                    'coberto_por_prefixo_maior' => false,
                ]);
        });

        $this->actingAs(new User());

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(self::URL, [
                'alvo' => '8.8.8.8',
            ])->assertOk();
        }

        $this->postJson(self::URL, [
            'alvo' => '8.8.8.8',
        ])->assertStatus(429);
    }
}
