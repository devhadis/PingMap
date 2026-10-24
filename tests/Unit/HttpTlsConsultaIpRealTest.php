<?php

namespace Tests\Unit;

use App\Services\HttpTlsConsultaService;
use App\Services\HttpTlsResolucaoSegura;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class HttpTlsConsultaIpRealTest extends TestCase
{
    private function criarServico(
        ?array $estatisticas
    ): HttpTlsConsultaService {
        // DNS simulado: nenhuma consulta externa.
        $dns = $this->createMock(
            HttpTlsResolucaoSegura::class
        );

        $dns->method('resolver')->willReturn([
            'dominio' => 'example.com',
            'ips' => ['8.8.8.8'],
        ]);

        // Cliente HTTP simulado.
        $cliente = $this->createMock(
            ClientInterface::class
        );

        $cliente->method('request')
            ->willReturnCallback(
                function (
                    $metodo,
                    $url,
                    $opcoes
                ) use ($estatisticas) {
                    $this->assertSame(
                        'HEAD',
                        $metodo
                    );

                    $this->assertSame(
                        'https://example.com/',
                        $url
                    );

                    // O serviço deve registrar o callback.
                    $this->assertArrayHasKey(
                        'on_stats',
                        $opcoes
                    );

                    $this->assertIsCallable(
                        $opcoes['on_stats']
                    );

                    $resposta = new Response(200);

                    // Simular os dados reais do cURL.
                    if ($estatisticas !== null) {
                        $opcoes['on_stats'](
                            new TransferStats(
                                new Request($metodo, $url),
                                $resposta,
                                0.2,
                                null,
                                $estatisticas
                            )
                        );
                    }

                    return $resposta;
                }
            );

        return new HttpTlsConsultaService(
            $dns,
            $cliente
        );
    }

    public function test_retorna_ip_real_aprovado(): void
    {
        $servico = $this->criarServico([
            'primary_ip' => '8.8.8.8',
            'primary_port' => 443,
            'ssl_verify_result' => 0,
        ]);

        $resultado = $servico->consultar(
            'example.com'
        );

        $this->assertSame(
            '8.8.8.8',
            $resultado['ip_conectado']
        );

        $this->assertSame(
            200,
            $resultado['status_http']
        );
    }

    public function test_rejeita_ip_real_nao_aprovado(): void
    {
        $servico = $this->criarServico([
            'primary_ip' => '1.1.1.1',
            'primary_port' => 443,
            'ssl_verify_result' => 0,
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        $servico->consultar('example.com');
    }

    public function test_rejeita_estatisticas_ausentes(): void
    {
        $servico = $this->criarServico(null);

        $this->expectException(
            UnexpectedValueException::class
        );

        $servico->consultar('example.com');
    }

    public function test_rejeita_porta_incorreta(): void
    {
        $servico = $this->criarServico([
            'primary_ip' => '8.8.8.8',
            'primary_port' => 8080,
            'ssl_verify_result' => 0,
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        $servico->consultar('example.com');
    }

    public function test_rejeita_falha_tls(): void
    {
        $servico = $this->criarServico([
            'primary_ip' => '8.8.8.8',
            'primary_port' => 443,
            'ssl_verify_result' => 20,
        ]);

        $this->expectException(
            UnexpectedValueException::class
        );

        $servico->consultar('example.com');
    }
}
