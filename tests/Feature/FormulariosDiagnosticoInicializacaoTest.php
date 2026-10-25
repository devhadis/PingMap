<?php

namespace Tests\Feature;

use Tests\TestCase;

class FormulariosDiagnosticoInicializacaoTest extends TestCase
{
    public function test_botoes_comecam_desabilitados(): void
    {
        $formularios = [
            'mtr' => 'mtr-submit',
            'traceroute' => 'trace-submit',
        ];

        foreach ($formularios as $pagina => $id) {
            $arquivo = resource_path(
                "views/ferramentas/{$pagina}.blade.php"
            );

            $conteudo = file_get_contents($arquivo);

            $this->assertNotFalse($conteudo);

            $encontrado = preg_match(
                '/<button\b[^>]*\bid="' .
                    preg_quote($id, '/') .
                    '"[^>]*>/i',
                $conteudo,
                $resultado
            );

            $this->assertSame(1, $encontrado);

            $this->assertMatchesRegularExpression(
                '/\sdisabled(?:\s|=|>)/i',
                $resultado[0],
                "O botão {$id} deve começar desabilitado."
            );
        }
    }

    public function test_javascript_habilita_botoes_depois_do_submit(): void
    {
        foreach (['mtr', 'traceroute'] as $pagina) {
            $conteudo = file_get_contents(
                resource_path("views/ferramentas/{$pagina}.blade.php")
            );

            $evento = strpos(
                $conteudo,
                "form.addEventListener('submit'"
            );

            $habilitacao = strpos(
                $conteudo,
                "\n    submit.disabled = false;\n"
            );

            $this->assertNotFalse($evento);
            $this->assertNotFalse($habilitacao);

            $this->assertGreaterThan(
                $evento,
                $habilitacao,
                "O botão {$pagina} deve ser habilitado após o evento submit."
            );
        }
    }

}
