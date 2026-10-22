<?php

namespace Tests\Unit;

use App\Services\DnssecValidadorService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DnssecComandoTest extends TestCase
{
    public function test_monta_comando_seguro(): void
    {
        $servico = new DnssecValidadorService();

        $this->assertSame(
            [
                '/usr/bin/delv',
                '@1.1.1.1',
                'cloudflare.com',
                'A',
            ],
            $servico->montarComando('cloudflare.com')
        );
    }

    public function test_normaliza_dominio(): void
    {
        $servico = new DnssecValidadorService();

        $comando = $servico->montarComando('GOOGLE.COM.');

        $this->assertSame('google.com', $comando[2]);
    }

    public function test_rejeita_dominio_interno(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnssecValidadorService())
            ->montarComando('servidor.local');
    }

    public function test_rejeita_endereco_ip(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnssecValidadorService())
            ->montarComando('127.0.0.1');
    }

    public function test_rejeita_injecao_de_comando(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DnssecValidadorService())
            ->montarComando('google.com;id');
    }
}
