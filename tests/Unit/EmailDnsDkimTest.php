<?php

namespace Tests\Unit;

use App\Services\DnsService;
use App\Services\EmailDnsService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmailDnsDkimTest extends TestCase
{
    public function test_consulta_dkim_com_seletor(): void
    {
        $dns = new DnsService(
            resolvedor: function ($nome, $tipo) {
                $this->assertSame(
                    'google._domainkey.gmail.com',
                    $nome
                );
                $this->assertSame(DNS_TXT, $tipo);

                return [
                    [
                        'type' => 'TXT',
                        'txt' => 'v=DKIM1; k=rsa; p=ABC123',
                    ],
                ];
            }
        );

        $resultado = (new EmailDnsService($dns))
            ->analisarDkim('gmail.com', 'google');

        $this->assertSame('presente', $resultado['status']);
        $this->assertSame('google', $resultado['seletor']);
        $this->assertCount(1, $resultado['registros']);
    }

    public function test_identifica_dkim_ausente(): void
    {
        $dns = new DnsService(
            resolvedor: fn () => []
        );

        $resultado = (new EmailDnsService($dns))
            ->analisarDkim('gmail.com', 'google');

        $this->assertSame('ausente', $resultado['status']);
        $this->assertSame([], $resultado['registros']);
    }

    public function test_rejeita_seletor_malicioso(): void
    {
        $consultas = 0;

        $dns = new DnsService(
            resolvedor: function () use (&$consultas) {
                $consultas++;
                return [];
            }
        );

        $this->expectException(InvalidArgumentException::class);

        try {
            (new EmailDnsService($dns))
                ->analisarDkim('gmail.com', '../interno');
        } finally {
            $this->assertSame(0, $consultas);
        }
    }

    public function test_rejeita_dominio_interno(): void
    {
        $dns = new DnsService(
            resolvedor: fn () => []
        );

        $this->expectException(InvalidArgumentException::class);

        (new EmailDnsService($dns))
            ->analisarDkim('servidor.internal', 'google');
    }
}
