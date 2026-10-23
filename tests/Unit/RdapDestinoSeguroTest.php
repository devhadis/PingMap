<?php

namespace Tests\Unit;

use App\Services\RdapDestinoSeguro;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class RdapDestinoSeguroTest extends TestCase
{
    public function test_aceita_servidores_autorizados(): void
    {
        $urls = [
            'https://rdap.arin.net/registry/',
            'https://rdap.db.ripe.net/',
            'https://rdap.apnic.net/',
            'https://rdap.lacnic.net/rdap/',
            'https://rdap.afrinic.net/rdap/',
        ];

        $validador = new RdapDestinoSeguro();

        foreach ($urls as $url) {
            $this->assertSame(
                $url,
                $validador->validar($url)
            );
        }
    }

    public function test_rejeita_http(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapDestinoSeguro())->validar(
            'http://rdap.arin.net/'
        );
    }

    public function test_rejeita_dominio_falso(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapDestinoSeguro())->validar(
            'https://rdap.arin.net.evil.example/'
        );
    }

    public function test_rejeita_ip_local(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapDestinoSeguro())->validar(
            'https://127.0.0.1/'
        );
    }

    public function test_rejeita_credenciais_na_url(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapDestinoSeguro())->validar(
            'https://usuario@rdap.arin.net/'
        );
    }

    public function test_rejeita_porta_alternativa(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapDestinoSeguro())->validar(
            'https://rdap.arin.net:8080/'
        );
    }

    public function test_rejeita_query_string(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapDestinoSeguro())->validar(
            'https://rdap.arin.net/?destino=localhost'
        );
    }

    public function test_rejeita_fragmento(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new RdapDestinoSeguro())->validar(
            'https://rdap.arin.net/#interno'
        );
    }
}
