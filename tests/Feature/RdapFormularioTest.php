<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RdapFormularioTest extends TestCase
{
    public function test_pagina_exibe_formulario_rdap(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/rdap')
            ->assertOk()
            ->assertSee('id="rdap-form"', false)
            ->assertSee('name="ip"', false)
            ->assertSee('Consultar RDAP')
            ->assertSee('id="rdap-resultado"', false);
    }

    public function test_formulario_possui_integracao_com_api(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/rdap')
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('api/v1/rdap/consultas', false)
            ->assertSee('name="_token"', false)
            ->assertSee('fetch(', false)
            ->assertSee('X-CSRF-TOKEN', false)
            ->assertSee('textContent', false);
    }
}
