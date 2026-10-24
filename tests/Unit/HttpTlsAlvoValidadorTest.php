<?php

namespace Tests\Unit;

use App\Services\HttpTlsAlvoValidador;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HttpTlsAlvoValidadorTest extends TestCase
{
    public function test_normaliza_dominio(): void
    {
        $validador = new HttpTlsAlvoValidador();

        $resultado = $validador->validar(
            '  WWW.Example.COM  '
        );

        $this->assertSame(
            'www.example.com',
            $resultado
        );
    }

    public function test_aceita_dominio_publico(): void
    {
        $validador = new HttpTlsAlvoValidador();

        $this->assertSame(
            'example.com',
            $validador->validar('example.com')
        );
    }

    public function test_rejeita_entradas_perigosas(): void
    {
        $validador = new HttpTlsAlvoValidador();

        $entradas = [
            'localhost',
            'servidor.local',
            '127.0.0.1',
            '192.168.1.1',
            '169.254.169.254',
            'http://example.com',
            'https://example.com',
            'example.com:8080',
            'example.com/admin',
            'usuario@example.com',
        ];

        foreach ($entradas as $entrada) {
            try {
                $validador->validar($entrada);

                $this->fail(
                    "Entrada deveria ser rejeitada: $entrada"
                );
            } catch (InvalidArgumentException $erro) {
                $this->assertNotEmpty(
                    $erro->getMessage()
                );
            }
        }
    }
}
