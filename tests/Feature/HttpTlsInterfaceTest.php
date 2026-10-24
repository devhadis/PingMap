<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HttpTlsInterfaceTest extends TestCase
{
    public function test_visitante_nao_acessa_pagina(): void
    {
        $this->get('/ferramentas/http-tls')
            ->assertRedirect('/login');
    }

    public function test_usuario_acessa_pagina_http_tls(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/http-tls')
            ->assertOk()
            ->assertViewIs('ferramentas.http_tls')
            ->assertSee('HTTP/TLS');
    }

    public function test_pagina_possui_formulario_e_resultados(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/http-tls')
            ->assertOk()
            ->assertSee('id="http-tls-form"', false)
            ->assertSee('name="dominio"', false)
            ->assertSee('id="http-tls-resultado"', false)
            ->assertSee('id="http-tls-erro"', false)
            ->assertSee('/api/v1/http-tls/consultas');
    }

    public function test_menu_possui_link_http_tls(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/rdap')
            ->assertOk()
            ->assertSee(
                'href="/ferramentas/http-tls"',
                false
            );
    }
}
