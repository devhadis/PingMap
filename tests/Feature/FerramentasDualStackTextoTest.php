<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FerramentasDualStackTextoTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceroute_informa_suporte_ipv4_e_ipv6(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/ferramentas/traceroute')
            ->assertOk()
            ->assertSee('IPv4 ou IPv6');
    }

    public function test_mtr_informa_suporte_ipv4_e_ipv6(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/ferramentas/mtr')
            ->assertOk()
            ->assertSee('IPv4 ou IPv6');
    }
}
