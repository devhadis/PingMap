<?php

namespace Tests\Feature;

use Tests\TestCase;

class FormulariosDiagnosticoMetodoTest extends TestCase
{
    public function test_formularios_usam_post_e_acao_sem_query_string(): void
    {
        $formularios = [
            'mtr' => 'mtr-form',
            'traceroute' => 'trace-form',
        ];

        foreach ($formularios as $pagina => $id) {
            $arquivo = resource_path(
                "views/ferramentas/{$pagina}.blade.php"
            );

            $conteudo = file_get_contents($arquivo);

            $this->assertNotFalse($conteudo);

            $encontrado = preg_match(
                '/<form\b[^>]*\bid="' .
                    preg_quote($id, '/') .
                    '"(?:"[^"]*"|[^">])*>/i',
                $conteudo,
                $resultado
            );

            $this->assertSame(1, $encontrado);

            $formulario = $resultado[0];

            $this->assertStringContainsString(
                'method="post"',
                $formulario,
                "O formulário {$pagina} precisa usar POST."
            );

            $this->assertStringContainsString(
                'action="{{ url()->current() }}"',
                $formulario,
                "O formulário {$pagina} precisa de uma ação sem query string."
            );
        }
    }
}
