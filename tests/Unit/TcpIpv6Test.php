<?php

namespace Tests\Unit;

use App\Services\TcpService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TcpIpv6Test extends TestCase
{
    public function test_ipv4_mantem_endpoint_original(): void
    {
        $this->verificarEndpoint(
            '1.1.1.1',
            443,
            'tcp://1.1.1.1:443'
        );
    }

    public function test_ipv6_utiliza_colchetes_no_endpoint(): void
    {
        $this->verificarEndpoint(
            '2606:4700:4700::1111',
            443,
            'tcp://[2606:4700:4700::1111]:443'
        );
    }

    public function test_ipv6_aceita_porta_80(): void
    {
        $this->verificarEndpoint(
            '2001:4860:4860::8888',
            80,
            'tcp://[2001:4860:4860::8888]:80'
        );
    }

    public function test_ipv6_valida_entrada_sem_conectar(): void
    {
        $tcp = new TcpService(
            function () {
                $this->fail('Não deveria abrir conexão.');
            }
        );

        $resultado = $tcp->validarEntrada(
            '2606:4700:4700::1111',
            443
        );

        $this->assertSame([
            'destino' => '2606:4700:4700::1111',
            'porta' => 443,
        ], $resultado);
    }

    public function test_ipv6_rejeita_porta_nao_permitida(): void
    {
        $tcp = new TcpService(
            function () {
                $this->fail('Não deveria abrir conexão.');
            }
        );

        $this->expectException(InvalidArgumentException::class);

        $tcp->verificar('2606:4700:4700::1111', 22);
    }

    #[DataProvider('ipsBloqueados')]
    public function test_ipv6_bloqueia_destinos_especiais(
        string $destino
    ): void {
        $executou = false;

        $tcp = new TcpService(
            function () use (&$executou) {
                $executou = true;

                return [
                    'conectado' => true,
                    'codigo' => 0,
                ];
            }
        );

        try {
            $tcp->verificar($destino, 443);

            $this->fail("Destino deveria ser bloqueado: {$destino}");
        } catch (InvalidArgumentException $e) {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->assertFalse($executou);
    }

    public static function ipsBloqueados(): array
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

    private function verificarEndpoint(
        string $destino,
        int $porta,
        string $esperado
    ): void {
        $conector = function (
            string $endpoint,
            float $timeout
        ) use ($esperado) {
            $this->assertSame($esperado, $endpoint);
            $this->assertSame(2.0, $timeout);

            return [
                'conectado' => true,
                'codigo' => 0,
            ];
        };

        $resultado = (new TcpService($conector))
            ->verificar($destino, $porta);

        $this->assertSame('conectado', $resultado['status']);
        $this->assertSame($destino, $resultado['destino']);
        $this->assertSame($porta, $resultado['porta']);
    }
}
