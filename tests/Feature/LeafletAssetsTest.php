<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeafletAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceroute_carrega_leaflet_local(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('/vendor/leaflet/leaflet.css')
            ->assertSee('/vendor/leaflet/leaflet.js');
    }

    public function test_mtr_carrega_leaflet_local(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('/vendor/leaflet/leaflet.css')
            ->assertSee('/vendor/leaflet/leaflet.js');
    }

    public function test_arquivos_leaflet_estao_no_servidor(): void
    {
        foreach ([
            'leaflet.css',
            'leaflet.js',
            'images/marker-icon.png',
            'images/marker-icon-2x.png',
            'images/marker-shadow.png',
        ] as $arquivo) {
            $this->assertFileExists(
                public_path('vendor/leaflet/' . $arquivo)
            );
        }
    }
}
