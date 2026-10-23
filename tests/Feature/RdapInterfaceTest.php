<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RdapInterfaceTest extends TestCase
{
    public function test_visitante_nao_acessa_pagina_rdap(): void
    {
        $this->get('/ferramentas/rdap')
            ->assertRedirect('/login');
    }

    public function test_usuario_autenticado_acessa_pagina_rdap(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/rdap')
            ->assertOk()
            ->assertViewIs('ferramentas.rdap')
            ->assertSee('RDAP Lookup');
    }
}
