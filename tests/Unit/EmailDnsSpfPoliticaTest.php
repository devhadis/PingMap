<?php

namespace Tests\Unit;

use App\Services\DnsService;
use App\Services\EmailDnsService;
use PHPUnit\Framework\TestCase;

class EmailDnsSpfPoliticaTest extends TestCase
{
    private function analisar(string $spf): array
    {
        $dns = new DnsService(
            resolvedor: fn () => [
                ['type' => 'TXT', 'txt' => $spf],
            ]
        );

        return (new EmailDnsService($dns))
            ->analisarSpf('cloudflare.com');
    }

    public function test_identifica_all_restritivo(): void
    {
        $resultado = $this->analisar('v=spf1 -all');

        $this->assertSame(
            'fail',
            $resultado['politica']
        );
    }

    public function test_identifica_softfail(): void
    {
        $resultado = $this->analisar(
            'v=spf1 include:_spf.google.com ~all'
        );

        $this->assertSame(
            'softfail',
            $resultado['politica']
        );
    }

    public function test_identifica_all_permissivo(): void
    {
        $resultado = $this->analisar('v=spf1 +all');

        $this->assertSame(
            'pass',
            $resultado['politica']
        );
    }

    public function test_identifica_ausencia_de_all(): void
    {
        $resultado = $this->analisar(
            'v=spf1 ip4:8.8.8.8'
        );

        $this->assertSame(
            'nao_definida',
            $resultado['politica']
        );
    }

    public function test_nao_confunde_palavra_com_mecanismo_all(): void
    {
        $resultado = $this->analisar(
            'v=spf1 include:all.example.com -all'
        );

        $this->assertSame(
            'fail',
            $resultado['politica']
        );
    }
}
