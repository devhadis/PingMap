<?php

namespace Tests\Feature;

use Tests\TestCase;

class RdapBgpIntegracaoInterfaceTest extends TestCase
{
    public function test_rdap_oferece_consulta_bgp_do_ip(): void
    {
        $arquivo = resource_path(
            'views/ferramentas/rdap.blade.php'
        );

        $conteudo = file_get_contents($arquivo);

        $this->assertNotFalse($conteudo);

        $this->assertStringContainsString(
            'id="rdap-bgp-consultar"',
            $conteudo
        );

        $this->assertStringContainsString(
            'id="rdap-bgp-resultado"',
            $conteudo
        );

        $this->assertStringContainsString(
            '/api/v1/bgp/rede/consultas',
            $conteudo
        );
    }
}
