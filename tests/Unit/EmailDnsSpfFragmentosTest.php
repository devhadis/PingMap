<?php

namespace Tests\Unit;

use App\Services\DnsService;
use App\Services\EmailDnsService;
use PHPUnit\Framework\TestCase;

class EmailDnsSpfFragmentosTest extends TestCase
{
    private function analisar(array $registros): array
    {
        $dns = new DnsService(
            resolvedor: fn () => $registros
        );

        return (new EmailDnsService($dns))
            ->analisarSpf('cloudflare.com');
    }

    public function test_reune_fragmentos_do_mesmo_txt(): void
    {
        $resultado = $this->analisar([
            [
                'type' => 'TXT',
                'entries' => [
                    'v=spf1 include:_spf.google.com ',
                    '-all',
                ],
            ],
        ]);

        $this->assertSame('presente', $resultado['status']);
        $this->assertSame('fail', $resultado['politica']);
        $this->assertSame([
            'v=spf1 include:_spf.google.com -all',
        ], $resultado['registros']);
    }

    public function test_nao_junta_registros_txt_diferentes(): void
    {
        $resultado = $this->analisar([
            [
                'type' => 'TXT',
                'entries' => ['v=spf1 ', '-all'],
            ],
            [
                'type' => 'TXT',
                'entries' => ['v=spf1 ', '~all'],
            ],
        ]);

        $this->assertSame('multiplo', $resultado['status']);
        $this->assertSame(2, $resultado['quantidade']);
        $this->assertNull($resultado['politica']);
    }

    public function test_ignora_fragmentos_que_nao_sao_spf(): void
    {
        $resultado = $this->analisar([
            [
                'type' => 'TXT',
                'entries' => ['verificacao=', 'abc123'],
            ],
        ]);

        $this->assertSame('ausente', $resultado['status']);
        $this->assertSame([], $resultado['registros']);
    }
}
