<?php

namespace Tests\Unit;

use App\Services\TracerouteService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TracerouteIpv6Test extends TestCase
{
    public function test_aceita_ipv6_publico(): void
    {
        $servico = new TracerouteService();

        $this->assertSame(
            '2606:4700:4700::1111',
            $servico->validarDestino('2606:4700:4700::1111')
        );
    }

    public function test_monta_comando_ipv6(): void
    {
        $servico = new TracerouteService();

        $this->assertSame(
            [
                '/usr/bin/traceroute',
                '-6',
                '-n',
                '-m',
                '12',
                '-q',
                '1',
                '-w',
                '1',
                '2606:4700:4700::1111',
            ],
            $servico->montarComando('2606:4700:4700::1111')
        );
    }

    public function test_preserva_comando_ipv4(): void
    {
        $servico = new TracerouteService();

        $this->assertSame(
            [
                '/usr/bin/traceroute',
                '-4',
                '-n',
                '-m',
                '12',
                '-q',
                '1',
                '-w',
                '1',
                '1.1.1.1',
            ],
            $servico->montarComando('1.1.1.1')
        );
    }

    public function test_interpreta_saltos_ipv6(): void
    {
        $servico = new TracerouteService();

        $saida = "traceroute to 2606:4700:4700::1111\n"
            . " 1  2001:4860::1  2.345 ms\n"
            . " 2  2606:4700:4700::1111  15.678 ms\n";

        $this->assertSame([
            [
                'salto' => 1,
                'ip' => '2001:4860::1',
                'latencia_ms' => 2.345,
            ],
            [
                'salto' => 2,
                'ip' => '2606:4700:4700::1111',
                'latencia_ms' => 15.678,
            ],
        ], $servico->interpretarSaida($saida));
    }

    public function test_interpreta_ipv6_link_local_como_salto(): void
    {
        $servico = new TracerouteService();

        $resultado = $servico->interpretarSaida(
            " 1  fe80::1  1.250 ms\n"
        );

        $this->assertSame([
            [
                'salto' => 1,
                'ip' => 'fe80::1',
                'latencia_ms' => 1.25,
            ],
        ], $resultado);
    }

    public function test_preserva_salto_sem_resposta_ipv6(): void
    {
        $servico = new TracerouteService();

        $resultado = $servico->interpretarSaida(
            " 1  *\n"
            . " 2  2606:4700:4700::1111  20.000 ms\n"
        );

        $this->assertCount(2, $resultado);
        $this->assertNull($resultado[0]['ip']);
        $this->assertNull($resultado[0]['latencia_ms']);
        $this->assertSame(
            '2606:4700:4700::1111',
            $resultado[1]['ip']
        );
    }

    public function test_ignora_ip_malformado_na_saida(): void
    {
        $servico = new TracerouteService();

        $this->assertSame(
            [],
            $servico->interpretarSaida(
                " 1  endereco-invalido  1.250 ms\n"
            )
        );
    }

    #[DataProvider('destinosBloqueados')]
    public function test_bloqueia_destinos_ipv6_especiais(
        string $destino
    ): void {
        $servico = new TracerouteService();

        $this->expectException(InvalidArgumentException::class);

        $servico->montarComando($destino);
    }

    public static function destinosBloqueados(): array
    {
        return [
            'loopback' => ['::1'],
            'privado ULA' => ['fc00::1'],
            'link-local' => ['fe80::1'],
            'documentacao' => ['2001:db8::1'],
            'Teredo' => ['2001::1'],
            '6to4' => ['2002::1'],
            'IPv4 mapeado' => ['::ffff:127.0.0.1'],
            'multicast' => ['ff02::1'],
        ];
    }
}
