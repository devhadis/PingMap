<?php

namespace Tests\Unit;

use App\Services\RdapRespostaIpParser;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class RdapRespostaIpCorrespondenciaTest extends TestCase
{
    private function redeIpv4(): array
    {
        return [
            'objectClassName' => 'ip network',
            'startAddress' => '1.1.1.0',
            'endAddress' => '1.1.1.255',
            'ipVersion' => 'v4',
        ];
    }

    private function redeIpv6(): array
    {
        return [
            'objectClassName' => 'ip network',
            'startAddress' => '2606:4700::',
            'endAddress' =>
                '2606:4700:ffff:ffff:ffff:ffff:ffff:ffff',
            'ipVersion' => 'v6',
        ];
    }

    public function test_aceita_ipv4_dentro_da_rede(): void
    {
        $resultado = (new RdapRespostaIpParser())
            ->interpretarParaIp(
                '1.1.1.1',
                $this->redeIpv4()
            );

        $this->assertSame('IPv4', $resultado['versao']);
        $this->assertSame('1.1.1.0', $resultado['inicio']);
    }

    public function test_rejeita_ipv4_fora_da_rede(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapRespostaIpParser())->interpretarParaIp(
            '8.8.8.8',
            $this->redeIpv4()
        );
    }

    public function test_aceita_ipv6_dentro_da_rede(): void
    {
        $resultado = (new RdapRespostaIpParser())
            ->interpretarParaIp(
                '2606:4700:4700::1111',
                $this->redeIpv6()
            );

        $this->assertSame('IPv6', $resultado['versao']);
    }

    public function test_rejeita_ipv6_fora_da_rede(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapRespostaIpParser())->interpretarParaIp(
            '2001:4860:4860::8888',
            $this->redeIpv6()
        );
    }
}
