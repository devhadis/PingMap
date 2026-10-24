<?php

namespace Tests\Unit;

use App\Services\BgpAlvoValidador;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BgpMascaraCidrTest extends TestCase
{
    public static function redesValidas(): array
    {
        return [
            'ipv4 /25' => ['8.8.8.128/25'],
            'ipv4 /31' => ['8.8.8.2/31'],
            'ipv6 /33' => ['2606:4700:8000::/33'],
            'ipv6 /127' => ['2606:4700::2/127'],
        ];
    }

    public static function redesInvalidas(): array
    {
        return [
            'ipv4 /25' => ['8.8.8.129/25'],
            'ipv4 /31' => ['8.8.8.3/31'],
            'ipv6 /33' => ['2606:4700:4000::/33'],
            'ipv6 /127' => ['2606:4700::3/127'],
        ];
    }

    #[DataProvider('redesValidas')]
    public function test_aceita_rede_cidr_valida(
        string $rede
    ): void {
        $resultado = (new BgpAlvoValidador())
            ->normalizar($rede);

        $this->assertSame([
            'tipo' => 'cidr',
            'valor' => $rede,
        ], $resultado);
    }

    #[DataProvider('redesInvalidas')]
    public function test_rejeita_cidr_com_bits_de_host(
        string $rede
    ): void {
        $this->expectException(
            InvalidArgumentException::class
        );

        (new BgpAlvoValidador())
            ->normalizar($rede);
    }
}
