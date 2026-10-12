<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsrfLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_tcp_disponibiliza_token_csrf(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/tcp')
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_pagina_dns_disponibiliza_token_csrf(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/ferramentas/dns')
            ->assertOk()
            ->assertSee('name="_token"', false);
    }
}
