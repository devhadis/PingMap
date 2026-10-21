<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutFerramentasTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceroute_utiliza_layout_com_menu_lateral(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('class="tools-shell"', false)
            ->assertSee('class="tools-sidebar pm-navigation"', false)
            ->assertSee('◈ PingMap');
    }

    public function test_mtr_utiliza_layout_com_menu_lateral(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('class="tools-shell"', false)
            ->assertSee('class="tools-sidebar pm-navigation"', false)
            ->assertSee('◈ PingMap');
    }
}
