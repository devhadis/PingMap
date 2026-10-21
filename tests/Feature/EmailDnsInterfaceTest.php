<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailDnsInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_interface_email(): void
    {
        $this->get('/ferramentas/email')
            ->assertRedirect('/login');
    }

    public function test_usuario_acessa_interface_email(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/email')
            ->assertOk()
            ->assertSee('SPF')
            ->assertSee('DKIM')
            ->assertSee('DMARC');
    }

    public function test_interface_possui_formulario_e_token_csrf(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/email')
            ->assertOk()
            ->assertSee('name="dominio"', false)
            ->assertSee('name="tipo"', false)
            ->assertSee('name="seletor"', false)
            ->assertSee('csrf-token');
    }

    public function test_interface_utiliza_api_email(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/email')
            ->assertOk()
            ->assertSee('/api/v1/email/analises');
    }

    public function test_interface_possui_menu_compartilhado(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/email')
            ->assertOk()
            ->assertSee('PingMap')
            ->assertSee('Traceroute')
            ->assertSee('MTR');
    }
}
