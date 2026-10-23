<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RdapMenuTest extends TestCase
{
    public function test_menu_exibe_link_para_rdap(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/rdap')
            ->assertOk()
            ->assertSee('RDAP Lookup')
            ->assertSee('href="' . route('ferramentas.rdap') . '"', false);
    }

    public function test_menu_destaca_ferramenta_rdap_ativa(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/rdap')
            ->assertOk()
            ->assertSee('aria-current="page"', false);
    }
}
