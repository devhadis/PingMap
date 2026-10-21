<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstiloFerramentasTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceroute_carrega_css_compartilhado(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('css/pingmap-tools.css');
    }

    public function test_mtr_carrega_css_compartilhado(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('css/pingmap-tools.css');
    }

    public function test_email_carrega_css_compartilhado(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/email')
            ->assertOk()
            ->assertSee('css/pingmap-tools.css');
    }

    public function test_css_compartilhado_existe(): void
    {
        $this->assertFileExists(public_path('css/pingmap-tools.css'));
    }
}
