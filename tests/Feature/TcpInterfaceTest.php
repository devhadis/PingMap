<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TcpInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_interface_tcp(): void
    {
        $this->get('/ferramentas/tcp')
            ->assertRedirect('/login');
    }

    public function test_usuario_visualiza_interface_tcp(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/tcp')
            ->assertOk()
            ->assertSee('TCP Port Check')
            ->assertSee('Verificar TCP')
            ->assertSee('api/v1/tcp/consultas');
    }

    public function test_interface_tcp_exibe_menu_compartilhado(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/tcp')
            ->assertOk()
            ->assertSee('DNS Lookup')
            ->assertSee('Dashboard');
    }
}
