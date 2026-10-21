<?php

namespace Tests\Unit;

use App\Services\DnsService;
use App\Services\EmailDnsService;
use PHPUnit\Framework\TestCase;

class EmailDnsDmarcValidacaoTest extends TestCase
{
    private function analisar(array $registros): array
    {
        $dns = new DnsService(
            resolvedor: fn () => $registros
        );

        return (new EmailDnsService($dns))
            ->analisarDmarc('gmail.com');
    }

    public function test_rejeita_politicas_duplicadas(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DMARC1; p=reject; p=none'],
        ]);

        $this->assertSame('invalido', $resultado['status']);
    }

    public function test_rejeita_versao_duplicada(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DMARC1; p=reject; v=DMARC1'],
        ]);

        $this->assertSame('invalido', $resultado['status']);
    }

    public function test_rejeita_registro_sem_politica(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DMARC1; rua=mailto:reports@gmail.com'],
        ]);

        $this->assertSame('invalido', $resultado['status']);
    }

    public function test_aceita_txt_fragmentado(): void
    {
        $resultado = $this->analisar([
            [
                'entries' => [
                    'v=DMARC1; ',
                    'p=quarantine',
                ],
            ],
        ]);

        $this->assertSame('presente', $resultado['status']);
        $this->assertSame('quarantine', $resultado['politica']);
    }

    public function test_identifica_multiplos_registros(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DMARC1; p=none'],
            ['txt' => 'v=DMARC1; p=reject'],
        ]);

        $this->assertSame('multiplo', $resultado['status']);
        $this->assertSame(2, $resultado['quantidade']);
    }
}
