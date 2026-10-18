<?php

namespace Tests\Unit;

use App\Services\MtrService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MtrIpv6Test extends TestCase
{
    public function test_aceita_ipv6_publico(): void
    {
        $servico = new MtrService();

        $this->assertSame(
            '2606:4700:4700::1111',
            $servico->validarDestino('2606:4700:4700::1111')
        );
    }

    public function test_monta_comando_ipv6(): void
    {
        $servico = new MtrService();

        $this->assertSame(
            [
                '/usr/bin/mtr',
                '-6',
                '--report',
                '--json',
                '--no-dns',
                '--report-cycles',
                '3',
                '--max-ttl',
                '12',
                '--',
                '2606:4700:4700::1111',
            ],
            $servico->montarComando('2606:4700:4700::1111')
        );
    }

    public function test_monta_comando_ipv4_com_familia_explicita(): void
    {
        $servico = new MtrService();

        $this->assertContains(
            '-4',
            $servico->montarComando('1.1.1.1')
        );
    }

    public function test_interpreta_saltos_ipv6(): void
    {
        $json = json_encode([
            'report' => [
                'hubs' => [
                    [
                        'count' => 1,
                        'host' => '2001:4860::1',
                        'Loss%' => 0.0,
                        'Snt' => 3,
                        'Last' => 2.5,
                        'Avg' => 2.0,
                        'Best' => 1.5,
                        'Wrst' => 3.0,
                    ],
                    [
                        'count' => 2,
                        'host' => '2606:4700:4700::1111',
                        'Loss%' => 0.0,
                        'Snt' => 3,
                        'Last' => 15.0,
                        'Avg' => 14.5,
                        'Best' => 13.0,
                        'Wrst' => 16.0,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $resultado = (new MtrService())->interpretarResultado($json);

        $this->assertCount(2, $resultado);
        $this->assertSame('2001:4860::1', $resultado[0]['ip']);
        $this->assertSame(2.0, $resultado[0]['media_ms']);
        $this->assertSame(
            '2606:4700:4700::1111',
            $resultado[1]['ip']
        );
        $this->assertSame(14.5, $resultado[1]['media_ms']);
    }

    public function test_preserva_salto_ipv6_link_local(): void
    {
        $json = json_encode([
            'report' => [
                'hubs' => [
                    [
                        'count' => 1,
                        'host' => 'fe80::1',
                        'Loss%' => 0.0,
                        'Snt' => 3,
                        'Last' => 1.2,
                        'Avg' => 1.1,
                        'Best' => 1.0,
                        'Wrst' => 1.3,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $resultado = (new MtrService())->interpretarResultado($json);

        $this->assertSame('fe80::1', $resultado[0]['ip']);
        $this->assertSame(1.1, $resultado[0]['media_ms']);
    }

    public function test_preserva_salto_sem_resposta(): void
    {
        $json = json_encode([
            'report' => [
                'hubs' => [
                    [
                        'count' => 1,
                        'host' => '???',
                        'Loss%' => 100.0,
                        'Snt' => 3,
                        'Last' => 0,
                        'Avg' => 0,
                        'Best' => 0,
                        'Wrst' => 0,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $resultado = (new MtrService())->interpretarResultado($json);

        $this->assertNull($resultado[0]['ip']);
        $this->assertNull($resultado[0]['media_ms']);
        $this->assertSame(100.0, $resultado[0]['perda_pct']);
    }

    public function test_rejeita_host_invalido_no_json(): void
    {
        $json = json_encode([
            'report' => [
                'hubs' => [
                    [
                        'count' => 1,
                        'host' => 'endereco-invalido',
                        'Loss%' => 100.0,
                        'Snt' => 3,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $resultado = (new MtrService())->interpretarResultado($json);

        $this->assertNull($resultado[0]['ip']);
        $this->assertNull($resultado[0]['media_ms']);
    }

    #[DataProvider('destinosBloqueados')]
    public function test_bloqueia_ipv6_especial(string $destino): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MtrService())->montarComando($destino);
    }

    public static function destinosBloqueados(): array
    {
        return [
            'loopback' => ['::1'],
            'privado' => ['fc00::1'],
            'link-local' => ['fe80::1'],
            'documentacao' => ['2001:db8::1'],
            'Teredo' => ['2001::1'],
            '6to4' => ['2002::1'],
            'IPv4 mapeado' => ['::ffff:127.0.0.1'],
            'multicast' => ['ff02::1'],
        ];
    }
}
