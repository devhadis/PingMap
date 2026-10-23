<?php

namespace Tests\Unit;

use App\Services\RdapRespostaIpParser;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class RdapRespostaIpParserTest extends TestCase
{
    public function test_interpreta_rede_ipv4(): void
    {
        $dados = [
            'objectClassName' => 'ip network',
            'handle' => 'NET-EXEMPLO',
            'name' => 'Rede Exemplo',
            'startAddress' => '1.1.1.0',
            'endAddress' => '1.1.1.255',
            'ipVersion' => 'v4',
            'country' => 'AU',
        ];

        $resultado = (new RdapRespostaIpParser())
            ->interpretar($dados);

        $this->assertSame('NET-EXEMPLO', $resultado['handle']);
        $this->assertSame('Rede Exemplo', $resultado['nome']);
        $this->assertSame('1.1.1.0', $resultado['inicio']);
        $this->assertSame('1.1.1.255', $resultado['fim']);
        $this->assertSame('IPv4', $resultado['versao']);
        $this->assertSame('AU', $resultado['pais']);
    }

    public function test_interpreta_rede_ipv6(): void
    {
        $dados = [
            'objectClassName' => 'ip network',
            'startAddress' => '2606:4700::',
            'endAddress' => '2606:4700:ffff:ffff:ffff:ffff:ffff:ffff',
            'ipVersion' => 'v6',
        ];

        $resultado = (new RdapRespostaIpParser())
            ->interpretar($dados);

        $this->assertSame('IPv6', $resultado['versao']);
        $this->assertSame('2606:4700::', $resultado['inicio']);
        $this->assertNull($resultado['nome']);
        $this->assertNull($resultado['pais']);
    }

    public function test_rejeita_resposta_vazia(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new RdapRespostaIpParser())->interpretar([]);
    }

    public function test_rejeita_objeto_que_nao_e_rede_ip(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new RdapRespostaIpParser())->interpretar([
            'objectClassName' => 'domain',
            'startAddress' => '1.1.1.0',
            'endAddress' => '1.1.1.255',
            'ipVersion' => 'v4',
        ]);
    }

    public function test_rejeita_endereco_invalido(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new RdapRespostaIpParser())->interpretar([
            'objectClassName' => 'ip network',
            'startAddress' => 'invalido',
            'endAddress' => '1.1.1.255',
            'ipVersion' => 'v4',
        ]);
    }

    public function test_rejeita_versoes_incompativeis(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new RdapRespostaIpParser())->interpretar([
            'objectClassName' => 'ip network',
            'startAddress' => '1.1.1.0',
            'endAddress' => '1.1.1.255',
            'ipVersion' => 'v6',
        ]);
    }

    public function test_rejeita_intervalo_invertido(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new RdapRespostaIpParser())->interpretar([
            'objectClassName' => 'ip network',
            'startAddress' => '1.1.1.255',
            'endAddress' => '1.1.1.0',
            'ipVersion' => 'v4',
        ]);
    }
}
