<?php

namespace Tests\Unit;

use App\Services\DnsService;
use App\Services\EmailDnsService;
use PHPUnit\Framework\TestCase;

class EmailDnsSpfTest extends TestCase
{
    private function criarServico(array $registros): EmailDnsService
    {
        $dns = new DnsService(
            resolvedor: function ($dominio, $tipo) use ($registros) {
                $this->assertSame('cloudflare.com', $dominio);
                $this->assertSame(DNS_TXT, $tipo);

                return $registros;
            }
        );

        return new EmailDnsService($dns);
    }

    public function test_identifica_spf_publicado(): void
    {
        $servico = $this->criarServico([
            ['type' => 'TXT', 'txt' => 'v=spf1 -all'],
        ]);

        $resultado = $servico->analisarSpf('cloudflare.com');

        $this->assertSame('presente', $resultado['status']);
        $this->assertSame(['v=spf1 -all'], $resultado['registros']);
    }

    public function test_identifica_ausencia_de_spf(): void
    {
        $servico = $this->criarServico([
            ['type' => 'TXT', 'txt' => 'verificacao=abc123'],
        ]);

        $resultado = $servico->analisarSpf('cloudflare.com');

        $this->assertSame('ausente', $resultado['status']);
        $this->assertSame([], $resultado['registros']);
    }

    public function test_detecta_multiplos_registros_spf(): void
    {
        $servico = $this->criarServico([
            ['type' => 'TXT', 'txt' => 'v=spf1 -all'],
            ['type' => 'TXT', 'txt' => 'v=spf1 ~all'],
        ]);

        $resultado = $servico->analisarSpf('cloudflare.com');

        $this->assertSame('multiplo', $resultado['status']);
        $this->assertCount(2, $resultado['registros']);
    }

    public function test_nao_confunde_versao_spf_invalida(): void
    {
        $servico = $this->criarServico([
            ['type' => 'TXT', 'txt' => 'v=spf10 -all'],
        ]);

        $resultado = $servico->analisarSpf('cloudflare.com');

        $this->assertSame('ausente', $resultado['status']);
    }
}
