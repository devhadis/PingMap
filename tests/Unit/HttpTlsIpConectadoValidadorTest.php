<?php

namespace Tests\Unit;

use App\Services\HttpTlsIpConectadoValidador;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class HttpTlsIpConectadoValidadorTest extends TestCase
{
    private function stats(string $ip): array
    {
        return [
            'primary_ip' => $ip,
            'primary_port' => 443,
            'ssl_verify_result' => 0,
        ];
    }

    public function test_aceita_ipv4_aprovado(): void
    {
        $ip = (new HttpTlsIpConectadoValidador())
            ->validar(
                ['8.8.8.8', '1.1.1.1'],
                $this->stats('1.1.1.1')
            );

        $this->assertSame('1.1.1.1', $ip);
    }

    public function test_aceita_ipv6_aprovado(): void
    {
        $ip = (new HttpTlsIpConectadoValidador())
            ->validar(
                ['2606:4700:4700::1111'],
                $this->stats(
                    '2606:4700:4700:0:0:0:0:1111'
                )
            );

        $this->assertSame(
            '2606:4700:4700:0:0:0:0:1111',
            $ip
        );
    }

    public function test_rejeita_ip_nao_aprovado(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new HttpTlsIpConectadoValidador())
            ->validar(
                ['8.8.8.8'],
                $this->stats('1.1.1.1')
            );
    }

    public function test_rejeita_ip_privado(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new HttpTlsIpConectadoValidador())
            ->validar(
                ['8.8.8.8'],
                $this->stats('127.0.0.1')
            );
    }

    public function test_rejeita_ip_ausente(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new HttpTlsIpConectadoValidador())
            ->validar(
                ['8.8.8.8'],
                []
            );
    }

    public function test_rejeita_porta_incorreta(): void
    {
        $stats = $this->stats('8.8.8.8');
        $stats['primary_port'] = 8080;

        $this->expectException(
            UnexpectedValueException::class
        );

        (new HttpTlsIpConectadoValidador())
            ->validar(['8.8.8.8'], $stats);
    }

    public function test_rejeita_falha_tls(): void
    {
        $stats = $this->stats('8.8.8.8');
        $stats['ssl_verify_result'] = 20;

        $this->expectException(
            UnexpectedValueException::class
        );

        (new HttpTlsIpConectadoValidador())
            ->validar(['8.8.8.8'], $stats);
    }
}
