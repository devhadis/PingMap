<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class BgpPaginaTest extends TestCase
{
    public function test_visitante_precisa_fazer_login(): void
    {
        $this->get('/ferramentas/bgp')
            ->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_visualiza_pagina_bgp(): void
    {
        $usuario = User::factory()->make();

        $this->actingAs($usuario)
            ->get('/ferramentas/bgp')
            ->assertOk()
            ->assertSee('BGP Lookup')
            ->assertSee('id="bgp-form"', false)
            ->assertSee('id="bgp-asn"', false)
            ->assertSee('id="bgp-resultados"', false)
            ->assertSee('/api/v1/bgp/consultas', false);
    }

    public function test_menu_lateral_possui_link_bgp(): void
    {
        $usuario = User::factory()->make();

        $this->actingAs($usuario)
            ->get('/ferramentas/rdap')
            ->assertOk()
            ->assertSee(
                'href="' . url('/ferramentas/bgp') . '"',
                false
            );
    }
}
