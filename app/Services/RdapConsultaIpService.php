<?php

namespace App\Services;

class RdapConsultaIpService
{
    public function consultar(string $ip): array
    {
        // 1. Validar o endereço IP público.
        $ip = (new EnderecoIpPublico())->validar($ip);

        // 2. Obter o catálogo oficial da IANA.
        $catalogo = (new RdapCatalogoIana())->obter($ip);

        // 3. Localizar o servidor responsável pelo IP.
        $servidor = (new RdapBootstrapIp())->localizar(
            $ip,
            $catalogo
        );

        // 4. Validar o destino antes de consultar.
        $servidor = (new RdapDestinoSeguro())->validar(
            $servidor
        );

        // 5. Consultar o servidor RDAP.
        $dados = (new RdapConsultaIpHttp())->consultar(
            $ip,
            $servidor
        );

        // 6. Interpretar a resposta e confirmar
        // que a rede contém o IP pesquisado.
        return (new RdapRespostaIpParser())
            ->interpretarParaIp($ip, $dados);
    }
}
