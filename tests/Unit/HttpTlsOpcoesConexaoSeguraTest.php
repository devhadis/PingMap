<?php

namespace Tests\Unit;

use App\Services\HttpTlsOpcoesConexaoSegura;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class HttpTlsOpcoesConexaoSeguraTest extends TestCase
{
    public function test_fixa_ipv4_e_ipv6_aprovados(): void
    {
        $servico = new HttpTlsOpcoesConexaoSegura();

        $opcoes = $servico->preparar('EXAMPLE.COM', [
            '8.8.8.8',
            '2001:4860:4860::8888',
        ]);

        $this->assertSame([
            'example.com:443:8.8.8.8,[2001:4860:4860::8888]',
        ], $opcoes['curl'][CURLOPT_RESOLVE]);
    }

    public function test_exige_configuracoes_seguras(): void
    {
        $servico = new HttpTlsOpcoesConexaoSegura();

        $opcoes = $servico->preparar(
            'example.com',
            ['8.8.8.8']
        );

        $this->assertTrue($opcoes['verify']);
        $this->assertFalse($opcoes['allow_redirects']);
        $this->assertSame('', $opcoes['proxy']);
        $this->assertFalse($opcoes['http_errors']);

        $this->assertSame(3, $opcoes['connect_timeout']);
        $this->assertSame(8, $opcoes['timeout']);

        // Essas opções devem ser gerenciadas pelo Guzzle.
        foreach ([
            CURLOPT_SSL_VERIFYHOST,
            CURLOPT_SSL_VERIFYPEER,
            CURLOPT_FOLLOWLOCATION,
            CURLOPT_PROXY,
        ] as $opcao) {
            $this->assertArrayNotHasKey(
                $opcao,
                $opcoes['curl']
            );
        }
    }

    public function test_rejeita_resposta_dns_mista(): void
    {
        $servico = new HttpTlsOpcoesConexaoSegura();

        $this->expectException(
            UnexpectedValueException::class
        );

        $servico->preparar('example.com', [
            '8.8.8.8',
            '127.0.0.1',
        ]);
    }

    public function test_rejeita_lista_vazia(): void
    {
        $servico = new HttpTlsOpcoesConexaoSegura();

        $this->expectException(
            UnexpectedValueException::class
        );

        $servico->preparar('example.com', []);
    }

    public function test_rejeita_dominio_invalido(): void
    {
        $servico = new HttpTlsOpcoesConexaoSegura();

        $this->expectException(
            InvalidArgumentException::class
        );

        $servico->preparar(
            'http://localhost',
            ['8.8.8.8']
        );
    }

    public function test_remove_ips_duplicados(): void
    {
        $servico = new HttpTlsOpcoesConexaoSegura();

        $opcoes = $servico->preparar('example.com', [
            '8.8.8.8',
            '8.8.8.8',
        ]);

        $this->assertSame([
            'example.com:443:8.8.8.8',
        ], $opcoes['curl'][CURLOPT_RESOLVE]);
    }

    public function test_utiliza_opcao_de_protocolos_do_guzzle(): void
    {
        $opcoes = (new HttpTlsOpcoesConexaoSegura())
            ->preparar('example.com', ['8.8.8.8']);

        $this->assertSame(
            ['https'],
            $opcoes['protocols']
        );

        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $this->assertArrayNotHasKey(
                CURLOPT_PROTOCOLS_STR,
                $opcoes['curl']
            );
        }

        if (defined('CURLOPT_PROTOCOLS')) {
            $this->assertArrayNotHasKey(
                CURLOPT_PROTOCOLS,
                $opcoes['curl']
            );
        }
    }

}
