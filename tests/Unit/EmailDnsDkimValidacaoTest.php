<?php

namespace Tests\Unit;

use App\Services\DnsService;
use App\Services\EmailDnsService;
use PHPUnit\Framework\TestCase;

class EmailDnsDkimValidacaoTest extends TestCase
{
    private function analisar(array $registros): array
    {
        $dns = new DnsService(
            resolvedor: fn () => $registros
        );

        return (new EmailDnsService($dns))
            ->analisarDkim('gmail.com', 'google');
    }

    public function test_aceita_txt_fragmentado(): void
    {
        $resultado = $this->analisar([
            [
                'entries' => [
                    'v=DKIM1; k=rsa; ',
                    'p=ABC123',
                ],
            ],
        ]);

        $this->assertSame('presente', $resultado['status']);
        $this->assertSame(1, $resultado['quantidade']);
    }

    public function test_detecta_registros_duplicados(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DKIM1; p=ABC123'],
            ['txt' => 'v=DKIM1; p=DEF456'],
        ]);

        $this->assertSame('multiplo', $resultado['status']);
        $this->assertSame(2, $resultado['quantidade']);
    }

    public function test_ignora_versao_incorreta(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DKIM2; p=ABC123'],
        ]);

        $this->assertSame('ausente', $resultado['status']);
    }

    public function test_detecta_registro_sem_chave(): void
    {
        $resultado = $this->analisar([
            ['txt' => 'v=DKIM1; k=rsa'],
        ]);

        $this->assertSame('invalido', $resultado['status']);
    }
}
