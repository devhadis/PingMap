<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HttpTlsRefinamentoVisualTest extends TestCase
{
    public function test_menu_possui_categoria_seguranca_web(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/http-tls')
            ->assertOk()
            ->assertSeeInOrder([
                'SEGURANÇA WEB',
                'HTTP/TLS Analyzer',
                'EM DESENVOLVIMENTO',
            ]);
    }

    public function test_pagina_possui_controle_de_dominios(): void
    {
        $this->actingAs(new User())
            ->get('/ferramentas/http-tls')
            ->assertOk()
            ->assertSee('id="http-tls-dominios-lista"', false)
            ->assertSee('id="http-tls-dominios-toggle"', false)
            ->assertSee('Mostrar todos os domínios');
    }
}
