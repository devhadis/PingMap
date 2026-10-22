<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnssecInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_dnssec(): void
    {
        $this->get('/ferramentas/dnssec')
            ->assertRedirect('/login');
    }

    public function test_usuario_acessa_pagina_dnssec(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/dnssec')
            ->assertOk()
            ->assertSee('DNSSEC Analyzer')
            ->assertSee('PingMap');
    }

    public function test_pagina_possui_formulario(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/dnssec')
            ->assertOk()
            ->assertSee('id="dnssec-form"', false)
            ->assertSee('name="dominio"', false)
            ->assertSee('csrf-token');
    }

    public function test_pagina_utiliza_api_dnssec(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/dnssec')
            ->assertOk()
            ->assertSee('/api/v1/dnssec/analises')
            ->assertSee('/js/dnssec-analise.js');
    }

    public function test_pagina_exibe_estados_dnssec(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/dnssec')
            ->assertOk()
            ->assertSee('Seguro')
            ->assertSee('Inseguro')
            ->assertSee('Indeterminado');
    }

    public function test_pagina_utiliza_menu_compartilhado(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/dnssec')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Traceroute')
            ->assertSee('MTR')
            ->assertSee('SPF / DKIM / DMARC');
    }
}
