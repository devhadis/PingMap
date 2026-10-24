<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\TransferStats;
use RuntimeException;
use UnexpectedValueException;

class HttpTlsConsultaService
{
    private HttpTlsResolucaoSegura $dns;
    private ClientInterface $cliente;

    public function __construct(
        ?HttpTlsResolucaoSegura $dns = null,
        ?ClientInterface $cliente = null
    ) {
        $this->dns = $dns ?? new HttpTlsResolucaoSegura();

        // Utilizar cURL para aplicar as restrições de conexão.
        $this->cliente = $cliente ?? new Client([
            'handler' => (new RdapTransporteSeguro())->criar(),
        ]);
    }

    public function consultar(string $entrada): array
    {
        // 1. Validar o domínio e resolver IPs públicos.
        $dns = $this->dns->resolver($entrada);

        $dominio = $dns['dominio'];
        $ips = $dns['ips'];

        // 2. Preparar a conexão HTTPS segura.
        $opcoes = (new HttpTlsOpcoesConexaoSegura())
            ->preparar($dominio, $ips);

        // Exigir suporte à captura do certificado TLS.
        if (! defined('CURLOPT_CERTINFO')) {
            throw new RuntimeException(
                'Captura de certificados TLS indisponível.'
            );
        }

        // Capturar o certificado na mesma conexão HTTPS.
        $opcoes['curl'][CURLOPT_CERTINFO] = true;

        // Capturar as estatísticas reais da conexão cURL.
        $estatisticas = [];

        $opcoes['on_stats'] = static function (
            TransferStats $transferencia
        ) use (&$estatisticas): void {
            $estatisticas = $transferencia->getHandlerStats();
        };

        // 3. Executar a requisição HTTPS.
        $inicio = hrtime(true);

        $resposta = $this->cliente->request(
            'HEAD',
            'https://' . $dominio . '/',
            $opcoes
        );

        // Confirmar o IP conectado e a verificação TLS.
        // Não aceitar metadados antes dessa validação.
        $ipConectado = (new HttpTlsIpConectadoValidador())
            ->validar($ips, $estatisticas);

        $tempoMs = round(
            (hrtime(true) - $inicio) / 1000000,
            2
        );

        // 4. Interpretar os metadados do certificado.
        $certificado = null;

        if (array_key_exists('certinfo', $estatisticas)) {
            $cadeia = $estatisticas['certinfo'];

            if (! is_array($cadeia)) {
                throw new UnexpectedValueException(
                    'Metadados do certificado TLS inválidos.'
                );
            }

            // Uma cadeia vazia significa dados indisponíveis.
            if ($cadeia !== []) {
                $certificado = (new HttpTlsCertificadoParser())
                    ->interpretar(
                        $cadeia,
                        new DateTimeImmutable(
                            'now',
                            new DateTimeZone('UTC')
                        )
                    );
            }
        }

        // 5. Selecionar somente cabeçalhos úteis.
        $permitidos = [
            'content-type',
            'server',
            'location',
            'strict-transport-security',
            'content-security-policy',
            'x-frame-options',
            'x-content-type-options',
            'referrer-policy',
            'cache-control',
        ];

        $cabecalhos = [];

        foreach ($permitidos as $nome) {
            if (! $resposta->hasHeader($nome)) {
                continue;
            }

            $valor = $resposta->getHeaderLine($nome);

            // Remover caracteres de controle.
            $valor = preg_replace(
                '/[\x00-\x1F\x7F]/',
                '',
                $valor
            ) ?? '';

            // Limitar o tamanho apresentado.
            $cabecalhos[$nome] = substr($valor, 0, 512);
        }

        // 6. Retornar o diagnóstico completo.
        return [
            'dominio' => $dominio,
            'ips' => $ips,
            'ip_conectado' => $ipConectado,
            'status_http' => $resposta->getStatusCode(),
            'tempo_ms' => $tempoMs,
            'cabecalhos' => $cabecalhos,
            'certificado' => $certificado,
        ];
    }
}
