<?php

namespace Tests\Unit;

use App\Services\DnsService;
use App\Services\EmailDnsService;
use PHPUnit\Framework\TestCase;

class EmailDnsDmarcTest extends TestCase
{
    private function analisar(array $registros): array
    {
        $dns = new DnsService(
            resolvedor: function ($nome, $tipo) use ($registros) {
                $this->assertSame('_dmarc.gmail.com', $nome);
                $this->assertSame(DNS_TXT, $tipo);

                return $registros;
            }
        );

        return (new EmailDnsService($dns))
            ->analisarDmarc('gmail.com');
    }

    public function test_identifica_politica_reject(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DMARC1; p=reject'],
        ]);

        $this->assertSame('presente', $resultado['status']);
        $this->assertSame('reject', $resultado['politica']);
    }

    public function test_identifica_politica_quarantine(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DMARC1; p=quarantine'],
        ]);

        $this->assertSame('quarantine', $resultado['politica']);
    }

    public function test_identifica_politica_none(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DMARC1; p=none'],
        ]);

        $this->assertSame('none', $resultado['politica']);
    }

    public function test_identifica_ausencia_de_dmarc(): void
    {
        $resultado = $this->analisar([]);

        $this->assertSame('ausente', $resultado['status']);
        $this->assertSame([], $resultado['registros']);
    }

    public function test_rejeita_politica_invalida(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DMARC1; p=allow'],
        ]);

        $this->assertSame('invalido', $resultado['status']);
    }
}
