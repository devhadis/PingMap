<?php

namespace Tests\Unit;

use App\Services\DnsService;
use App\Services\EmailDnsService;
use PHPUnit\Framework\TestCase;

class EmailDnsDmarcSintaxeTest extends TestCase
{
    private function analisar(string $txt): array
    {
        $dns = new DnsService(
            resolvedor: fn () => [['txt' => $txt]]
        );

        return (new EmailDnsService($dns))
            ->analisarDmarc('gmail.com');
    }

    public function test_rejeita_politica_fora_da_segunda_posicao(): void
    {
        $resultado = $this->analisar(
            'v=DMARC1; rua=mailto:relatorios@gmail.com; p=reject'
        );

        $this->assertSame('invalido', $resultado['status']);
    }

    public function test_rejeita_campo_sem_igual(): void
    {
        $resultado = $this->analisar(
            'v=DMARC1; p=reject; campo_invalido'
        );

        $this->assertSame('invalido', $resultado['status']);
    }

    public function test_rejeita_politica_duplicada_com_maiuscula(): void
    {
        $resultado = $this->analisar(
            'v=DMARC1; p=reject; P=none'
        );

        $this->assertSame('invalido', $resultado['status']);
    }

    public function test_aceita_registro_valido_com_rua(): void
    {
        $resultado = $this->analisar(
            'v=DMARC1; p=reject; rua=mailto:relatorios@gmail.com'
        );

        $this->assertSame('presente', $resultado['status']);
        $this->assertSame('reject', $resultado['politica']);
    }
}
