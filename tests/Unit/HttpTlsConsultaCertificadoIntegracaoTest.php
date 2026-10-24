<?php

namespace Tests\Unit;

use App\Services\BgpResolucaoDominioService;
use App\Services\HttpTlsConsultaService;
use App\Services\HttpTlsResolucaoSegura;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class HttpTlsConsultaCertificadoIntegracaoTest extends TestCase
{
    private function servico(
        array $extras = [],
        ?callable $verificarOpcoes = null
    ): HttpTlsConsultaService {
        // DNS simulado: nenhuma consulta externa.
        $bgp = new BgpResolucaoDominioService(
            static fn (
                string $dominio,
                int $tipos,
                ?float $prazo = null
            ): array => [
                ['type' => 'A', 'ip' => '8.8.8.8'],
            ]
        );

        $cliente = $this->createMock(
            ClientInterface::class
        );

        $cliente->expects($this->once())
            ->method('request')
            ->willReturnCallback(
                static function (
                    string $metodo,
                    $url,
                    array $opcoes
                ) use ($extras, $verificarOpcoes): Response {
                    if ($verificarOpcoes !== null) {
                        $verificarOpcoes($opcoes);
                    }

                    $resposta = new Response(200, [
                        'Content-Type' => 'text/html',
                    ]);

                    $estatisticas = array_merge([
                        'primary_ip' => '8.8.8.8',
                        'primary_port' => 443,
                        'ssl_verify_result' => 0,
                    ], $extras);

                    $opcoes['on_stats'](
                        new TransferStats(
                            new Request($metodo, $url),
                            $resposta,
                            0.2,
                            null,
                            $estatisticas
                        )
                    );

                    return $resposta;
                }
            );

        return new HttpTlsConsultaService(
            new HttpTlsResolucaoSegura($bgp),
            $cliente
        );
    }

    private function certificado(): array
    {
        return [
            'Subject' => 'CN = example.com',
            'Issuer' =>
                'CN = Cloudflare TLS Issuing ECC CA 3',
            'Start date' =>
                'Sep 26 22:49:11 2026 GMT',
            'Expire date' =>
                'Dec 25 22:56:35 2026 GMT',
            'Signature Algorithm' =>
                'ecdsa-with-SHA256',
            'X509v3 Subject Alternative Name' =>
                'DNS:example.com, DNS:*.example.com',
            'Cert' =>
                '-----BEGIN CERTIFICATE-----SEGREDO',
        ];
    }

    public function test_integra_certificado_na_consulta_https(): void
    {
        $servico = $this->servico(
            ['certinfo' => [$this->certificado()]],
            function (array $opcoes): void {
                // A captura do certificado deve estar ativa.
                $this->assertSame(
                    true,
                    $opcoes['curl'][CURLOPT_CERTINFO] ?? null
                );

                // As proteções existentes devem permanecer.
                $this->assertSame(
                    ['example.com:443:8.8.8.8'],
                    $opcoes['curl'][CURLOPT_RESOLVE]
                );

                $this->assertTrue($opcoes['verify']);

                $this->assertFalse(
                    $opcoes['allow_redirects']
                );

                $this->assertSame(
                    '',
                    $opcoes['proxy']
                );
            }
        );

        $resultado = $servico->consultar('example.com');

        $this->assertSame(
            200,
            $resultado['status_http']
        );

        $this->assertSame(
            '8.8.8.8',
            $resultado['ip_conectado']
        );

        $this->assertSame(
            'CN = Cloudflare TLS Issuing ECC CA 3',
            $resultado['certificado']['emissor']
        );

        $this->assertSame(
            ['example.com', '*.example.com'],
            $resultado['certificado']['dominios']
        );

        $this->assertSame(
            '2026-12-25T22:56:35+00:00',
            $resultado['certificado']['fim_validade']
        );

        $this->assertArrayNotHasKey(
            'Cert',
            $resultado['certificado']
        );

        $this->assertStringNotContainsString(
            'SEGREDO',
            json_encode($resultado)
        );
    }

    public function test_informa_certificado_indisponivel(): void
    {
        // O cURL não forneceu metadados do certificado.
        $resultado = $this->servico()
            ->consultar('example.com');

        $this->assertSame(
            200,
            $resultado['status_http']
        );

        $this->assertArrayHasKey(
            'certificado',
            $resultado
        );

        // Não inventar dados nem declarar certificado válido.
        $this->assertNull(
            $resultado['certificado']
        );
    }

    public function test_rejeita_certificado_malformado(): void
    {
        $servico = $this->servico([
            'certinfo' => [
                ['Subject' => 'CN = example.com'],
            ],
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        $servico->consultar('example.com');
    }
}
