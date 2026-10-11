<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_interface_dns(): void
    {
        $this->get('/ferramentas/dns')
            ->assertRedirect('/login');
    }

    public function test_usuario_visualiza_interface_dns(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/ferramentas/dns')
            ->assertOk()
            ->assertSee('DNS Lookup')
            ->assertSee('form-dns')
            ->assertSee('dns-historico')
            ->assertSee('Dashboard / Ping');
    }

    public function test_dashboard_ping_continua_acessivel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('form-ping');
    }
}
