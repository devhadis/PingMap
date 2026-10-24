<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BgpConsultaAsnService;
use App\Services\BgpConsultaPrefixosService;
use RuntimeException;
use Tests\TestCase;

class BgpApiTest extends TestCase
{
    public function test_exige_autenticacao(): void
    {
        $this->postJson('/api/v1/bgp/consultas', [
            'asn' => 'AS13335',
        ])->assertUnauthorized();
    }

    public function test_rejeita_asn_invalido(): void
    {
        $this->actingAs(new User())
            ->postJson('/api/v1/bgp/consultas', [
                'asn' => 'AS0',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('asn');
    }

    public function test_exige_campo_asn(): void
    {
        $this->actingAs(new User())
            ->postJson('/api/v1/bgp/consultas', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('asn');
    }

    public function test_retorna_dados_e_prefixos(): void
    {
        $this->mock(BgpConsultaAsnService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                ->once()
                ->with(13335)
                ->andReturn([
                    'asn' => 13335,
                    'nome' => 'CLOUDFLARENET',
                    'anunciado' => true,
                ]);
        });

        $this->mock(BgpConsultaPrefixosService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                ->once()
                ->with(13335)
                ->andReturn([
                    'ipv4' => ['203.0.113.0/24'],
                    'ipv6' => ['2001:db8::/32'],
                ]);
        });

        $this->actingAs(new User())
            ->postJson('/api/v1/bgp/consultas', [
                'asn' => 'AS13335',
            ])
            ->assertOk()
            ->assertJsonPath('resultado.asn', 13335)
            ->assertJsonPath('resultado.nome', 'CLOUDFLARENET')
            ->assertJsonPath('resultado.anunciado', true)
            ->assertJsonPath(
                'resultado.prefixos.ipv4.0',
                '203.0.113.0/24'
            )
            ->assertJsonPath(
                'resultado.prefixos.ipv6.0',
                '2001:db8::/32'
            );
    }

    public function test_falha_externa_retorna_503(): void
    {
        $this->mock(BgpConsultaAsnService::class, function ($mock) {
            $mock->shouldReceive('consultar')
                ->once()
                ->with(13335)
                ->andThrow(new RuntimeException('Falha externa.'));
        });

        $this->mock(BgpConsultaPrefixosService::class, function ($mock) {
            $mock->shouldNotReceive('consultar');
        });

        $this->actingAs(new User())
            ->postJson('/api/v1/bgp/consultas', [
                'asn' => 'AS13335',
            ])
            ->assertStatus(503)
            ->assertJsonPath(
                'message',
                'Não foi possível concluir a consulta BGP.'
            );
    }
}
