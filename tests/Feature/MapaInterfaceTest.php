<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapaInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceroute_exibe_area_do_mapa(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('id="trace-geo-map"', false)
            ->assertSee('Geolocalização aproximada');
    }

    public function test_mtr_exibe_area_do_mapa(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('id="mtr-geo-map"', false)
            ->assertSee('Geolocalização aproximada');
    }

    public function test_visitante_nao_acessa_mapa_traceroute(): void
    {
        $this->get('/ferramentas/traceroute')
            ->assertRedirect('/login');
    }

    public function test_visitante_nao_acessa_mapa_mtr(): void
    {
        $this->get('/ferramentas/mtr')
            ->assertRedirect('/login');
    }
}
