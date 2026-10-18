<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapaIntegracaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceroute_carrega_integracao_geoip(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('/js/mapa-interface.js', false)
            ->assertSee('mapaGeo.mostrarConsulta(consulta);', false);
    }

    public function test_mtr_carrega_integracao_geoip(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('/js/mapa-interface.js', false)
            ->assertSee('mapaGeo.mostrarConsulta(consulta);', false);
    }
}
