<?php

namespace Tests\Unit;

use App\Services\BgpAsnValidador;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BgpAsnValidadorTest extends TestCase
{
    public function test_aceita_asn_com_prefixo(): void
    {
        $validador = new BgpAsnValidador();

        $this->assertSame(
            13335,
            $validador->normalizar('AS13335')
        );
    }

    public function test_aceita_asn_sem_prefixo(): void
    {
        $validador = new BgpAsnValidador();

        $this->assertSame(
            13335,
            $validador->normalizar('13335')
        );
    }

    public function test_aceita_prefixo_minusculo(): void
    {
        $validador = new BgpAsnValidador();

        $this->assertSame(
            4230,
            $validador->normalizar('as4230')
        );
    }

    public function test_aceita_maior_asn_permitido(): void
    {
        $validador = new BgpAsnValidador();

        $this->assertSame(
            4294967294,
            $validador->normalizar('AS4294967294')
        );
    }

    public function test_rejeita_asn_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BgpAsnValidador())->normalizar('AS0');
    }

    public function test_rejeita_asn_acima_do_limite(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BgpAsnValidador())->normalizar('AS4294967295');
    }

    public function test_rejeita_entrada_com_comando(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new BgpAsnValidador())->normalizar('AS13335;comando');
    }
}
