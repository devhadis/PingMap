<?php

namespace Tests\Unit;

use App\Services\BgpResolucaoDominioService;
use App\Services\HttpTlsConsultaService;
use App\Services\HttpTlsResolucaoSegura;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HttpTlsConsultaServiceTest extends TestCase
{
    private function dns(array $registros): HttpTlsResolucaoSegura
    {
        $bgp = new BgpResolucaoDominioService(
            static fn (
                string $dominio,
                int $tipos,
                ?float $prazo = null
            ): array => $registros
        );

        return new HttpTlsResolucaoSegura($bgp);
    }

    private function respostaComEstatisticas(Response $resposta): callable
    {
        return static function (
            string $metodo,
            string $url,
            array $opcoes
        ) use ($resposta): Response {
            $opcoes['on_stats'](
                new TransferStats(
                    new Request($metodo, $url),
                    $resposta,
                    0.2,
                    null,
                    [
                        'primary_ip' => '8.8.8.8',
                        'primary_port' => 443,
                        'ssl_verify_result' => 0,
                    ]
                )
            );

            return $resposta;
        };
    }

    public function test_consulta_https_com_ip_aprovado(): void
    {
        $cliente = $this->createMock(ClientInterface::class);

        $cliente->expects($this->once())
            ->method('request')
            ->with(
                'HEAD',
                'https://example.com/',
                $this->callback(
                    static function (array $opcoes): bool {
                        return
                            $opcoes['curl'][CURLOPT_RESOLVE] === [
                                'example.com:443:8.8.8.8',
                            ]
                            && $opcoes['verify'] === true
                            && $opcoes['allow_redirects'] === false
                            && $opcoes['http_errors'] === false;
                    }
                )
            )
            ->willReturnCallback(
                $this->respostaComEstatisticas(
                    new Response(200, [
                        'Content-Type' => 'text/html',
                    ])
                )
            );

        $servico = new HttpTlsConsultaService(
            $this->dns([
                ['type' => 'A', 'ip' => '8.8.8.8'],
            ]),
            $cliente
        );

        $resultado = $servico->consultar('EXAMPLE.COM');

        $this->assertSame(
            'example.com',
            $resultado['dominio']
        );

        $this->assertSame(200, $resultado['status_http']);

        $this->assertSame(
            ['8.8.8.8'],
            $resultado['ips']
        );

        $this->assertGreaterThanOrEqual(
            0,
            $resultado['tempo_ms']
        );
    }

    public function test_retorna_redirecionamento_sem_seguir(): void
    {
        $cliente = $this->createMock(ClientInterface::class);

        $cliente->expects($this->once())
            ->method('request')
            ->willReturnCallback(
                $this->respostaComEstatisticas(
                    new Response(301, [
                        'Location' => 'https://www.example.com/',
                    ])
                )
            );

        $servico = new HttpTlsConsultaService(
            $this->dns([
                ['type' => 'A', 'ip' => '8.8.8.8'],
            ]),
            $cliente
        );

        $resultado = $servico->consultar('example.com');

        $this->assertSame(301, $resultado['status_http']);

        $this->assertSame(
            'https://www.example.com/',
            $resultado['cabecalhos']['location']
        );
    }

    public function test_rejeita_ip_privado_antes_da_conexao(): void
    {
        $cliente = $this->createMock(ClientInterface::class);

        $cliente->expects($this->never())
            ->method('request');

        $servico = new HttpTlsConsultaService(
            $this->dns([
                ['type' => 'A', 'ip' => '127.0.0.1'],
            ]),
            $cliente
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $servico->consultar('example.com');
    }

    public function test_rejeita_dominio_invalido(): void
    {
        $cliente = $this->createMock(ClientInterface::class);

        $cliente->expects($this->never())
            ->method('request');

        $servico = new HttpTlsConsultaService(
            $this->dns([]),
            $cliente
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $servico->consultar('http://localhost');
    }

    public function test_nao_expoe_cookies(): void
    {
        $cliente = $this->createMock(ClientInterface::class);

        $cliente->method('request')
            ->willReturnCallback(
                $this->respostaComEstatisticas(
                    new Response(200, [
                        'Content-Type' => 'text/html',
                        'Set-Cookie' => 'sessao=segredo',
                        'Server' => 'nginx',
                    ])
                )
            );

        $servico = new HttpTlsConsultaService(
            $this->dns([
                ['type' => 'A', 'ip' => '8.8.8.8'],
            ]),
            $cliente
        );

        $resultado = $servico->consultar('example.com');

        $this->assertArrayNotHasKey(
            'set-cookie',
            $resultado['cabecalhos']
        );

        $this->assertSame(
            'text/html',
            $resultado['cabecalhos']['content-type']
        );
    }

    public function test_retorna_status_500(): void
    {
        $cliente = $this->createMock(ClientInterface::class);

        $cliente->method('request')
            ->willReturnCallback(
                $this->respostaComEstatisticas(new Response(500))
            );

        $servico = new HttpTlsConsultaService(
            $this->dns([
                ['type' => 'A', 'ip' => '8.8.8.8'],
            ]),
            $cliente
        );

        $resultado = $servico->consultar('example.com');

        $this->assertSame(500, $resultado['status_http']);
    }
}
