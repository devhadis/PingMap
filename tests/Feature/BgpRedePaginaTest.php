<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class BgpRedePaginaTest extends TestCase
{
    public function test_pagina_exibe_formulario_de_rede_sem_remover_asn(): void
    {
        $usuario = User::factory()->make();

        $this->actingAs($usuario)
            ->get('/ferramentas/bgp')
            ->assertOk()

            // Formulário ASN original deve continuar.
            ->assertSee('id="bgp-form"', false)
            ->assertSee('id="bgp-asn"', false)

            // Novo formulário de rede.
            ->assertSee('id="bgp-rede-form"', false)
            ->assertSee('id="bgp-rede-alvo"', false)
            ->assertSee('name="alvo"', false)
            ->assertSee('id="bgp-rede-enviar"', false)

            // Endpoint correto.
            ->assertSee(
                'action="' .
                url('/api/v1/bgp/rede/consultas') .
                '"',
                false
            );
    }

    public function test_pagina_possui_resultados_e_integracao_js(): void
    {
        $usuario = User::factory()->make();

        $this->actingAs($usuario)
            ->get('/ferramentas/bgp')
            ->assertOk()

            // Mensagens de carregamento e erro.
            ->assertSee(
                'id="bgp-rede-mensagem"',
                false
            )

            // Área independente de resultados.
            ->assertSee(
                'id="bgp-rede-resultados"',
                false
            )
            ->assertSee(
                'id="bgp-rede-resumo"',
                false
            )
            ->assertSee(
                'id="bgp-rede-lista"',
                false
            )

            // Integração JavaScript própria.
            ->assertSee(
                'data-bgp-rede-integration',
                false
            )
            ->assertSee(
                'JSON.stringify({ alvo })',
                false
            );
    }
}
