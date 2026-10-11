<?php

namespace Tests\Unit;

use App\Services\DnsService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DnsServiceTest extends TestCase
{
    public function test_consulta_registro_a(): void
    {
        $servico = new DnsService(
            resolvedor: function ($dominio, $tipo) {
                $this->assertSame('cloudflare.com', $dominio);
                $this->assertSame(DNS_A, $tipo);

                return [[
                    'host' => 'cloudflare.com',
                    'type' => 'A',
                    'ip' => '1.1.1.1',
                    'ttl' => 300,
                ]];
            }
        );

        $resultado = $servico->consultar('CLOUDFLARE.COM', 'a');

        $this->assertSame('sucesso', $resultado['status']);
        $this->assertSame(1, $resultado['quantidade']);
        $this->assertSame(
            '1.1.1.1',
            $resultado['registros'][0]['ip']
        );
    }

    public function test_consulta_sem_registros(): void
    {
        $servico = new DnsService(
            resolvedor: fn () => []
        );

        $resultado = $servico->consultar('cloudflare.com', 'MX');

        $this->assertSame('sem_registros', $resultado['status']);
        $this->assertSame(0, $resultado['quantidade']);
        $this->assertSame([], $resultado['registros']);
    }

    public function test_rejeita_tipo_nao_permitido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnsService())->consultar('cloudflare.com', 'ANY');
    }

    public function test_rejeita_entrada_com_comando(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnsService())->consultar(
            'cloudflare.com;whoami',
            'A'
        );
    }

    public function test_rejeita_dominio_interno(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnsService())->consultar(
            'servidor.internal',
            'A'
        );
    }

    public function test_converte_ipv4_publico_para_ptr(): void
    {
        $servico = new DnsService(
            resolvedor: function ($dominio, $tipo) {
                $this->assertSame(
                    '1.1.1.1.in-addr.arpa',
                    $dominio
                );
                $this->assertSame(DNS_PTR, $tipo);

                return [[
                    'type' => 'PTR',
                    'target' => 'one.one.one.one',
                ]];
            }
        );

        $resultado = $servico->consultar('1.1.1.1', 'PTR');

        $this->assertSame('sucesso', $resultado['status']);
        $this->assertSame(1, $resultado['quantidade']);
    }

    public function test_trata_falha_do_resolvedor(): void
    {
        $servico = new DnsService(
            resolvedor: fn () => false
        );

        $this->expectException(RuntimeException::class);

        $servico->consultar('cloudflare.com', 'A');
    }

    public function test_ptr_rejeita_zona_reversa_informada_diretamente(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnsService())->consultar(
            '1.0.0.127.in-addr.arpa',
            'PTR'
        );
    }

    public function test_ptr_rejeita_ip_privado(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnsService())->validarEntrada('192.168.1.1', 'PTR');
    }

    public function test_validacao_nao_executa_consulta_dns(): void
    {
        $servico = new DnsService(
            resolvedor: fn () => $this->fail(
                'O resolvedor não deveria ser executado.'
            )
        );

        $entrada = $servico->validarEntrada('CLOUDFLARE.COM', 'a');

        $this->assertSame('cloudflare.com', $entrada['dominio']);
        $this->assertSame('A', $entrada['tipo']);
    }

}
