<?php

namespace Tests\Unit;

use App\Services\HttpTlsCertificadoParser;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class HttpTlsCertificadoParserTest extends TestCase
{
    private function certificados(): array
    {
        return [
            [
                'Subject' => 'CN = example.com',
                'Issuer' => 'CN = Cloudflare TLS Issuing ECC CA 3',
                'Start date' => 'Sep 26 22:49:11 2026 GMT',
                'Expire date' => 'Dec 25 22:56:35 2026 GMT',
                'Signature Algorithm' => 'ecdsa-with-SHA256',
                'X509v3 Subject Alternative Name' =>
                    'DNS:example.com, DNS:*.example.com',
                'Cert' => '-----BEGIN CERTIFICATE-----',
            ],
            [
                'Subject' => 'CN = Certificado Intermediario',
            ],
        ];
    }

    private function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            '2026-10-24T00:00:00+00:00'
        );
    }

    public function test_interpreta_certificado_principal(): void
    {
        $resultado = (new HttpTlsCertificadoParser())
            ->interpretar(
                $this->certificados(),
                $this->agora()
            );

        $this->assertSame(
            'CN = example.com',
            $resultado['assunto']
        );

        $this->assertSame(
            'CN = Cloudflare TLS Issuing ECC CA 3',
            $resultado['emissor']
        );

        $this->assertSame(
            ['example.com', '*.example.com'],
            $resultado['dominios']
        );

        $this->assertSame(
            '2026-09-26T22:49:11+00:00',
            $resultado['inicio_validade']
        );

        $this->assertSame(
            '2026-12-25T22:56:35+00:00',
            $resultado['fim_validade']
        );

        $this->assertSame(
            'ecdsa-with-SHA256',
            $resultado['algoritmo_assinatura']
        );

        $this->assertSame(
            2,
            $resultado['certificados_na_cadeia']
        );

        $this->assertSame(
            62,
            $resultado['dias_restantes']
        );

        $this->assertFalse(
            $resultado['expirado']
        );

        // Não expor o certificado PEM completo.
        $this->assertArrayNotHasKey(
            'Cert',
            $resultado
        );
    }

    public function test_alerta_expiracao_proxima(): void
    {
        $agora = new DateTimeImmutable(
            '2026-12-01T22:56:35+00:00'
        );

        $resultado = (new HttpTlsCertificadoParser())
            ->interpretar(
                $this->certificados(),
                $agora
            );

        $this->assertSame(
            24,
            $resultado['dias_restantes']
        );

        $this->assertTrue(
            $resultado['alerta_expiracao']
        );

        $this->assertFalse(
            $resultado['expirado']
        );
    }

    public function test_identifica_certificado_expirado(): void
    {
        $agora = new DateTimeImmutable(
            '2026-12-26T00:00:00+00:00'
        );

        $resultado = (new HttpTlsCertificadoParser())
            ->interpretar(
                $this->certificados(),
                $agora
            );

        $this->assertTrue(
            $resultado['expirado']
        );

        $this->assertSame(
            0,
            $resultado['dias_restantes']
        );

        $this->assertTrue(
            $resultado['alerta_expiracao']
        );
    }

    public function test_rejeita_cadeia_vazia(): void
    {
        $this->expectException(
            UnexpectedValueException::class
        );

        (new HttpTlsCertificadoParser())
            ->interpretar([], $this->agora());
    }

    public function test_rejeita_data_invalida(): void
    {
        $certificados = $this->certificados();

        $certificados[0]['Expire date'] =
            'data-invalida';

        $this->expectException(
            UnexpectedValueException::class
        );

        (new HttpTlsCertificadoParser())
            ->interpretar(
                $certificados,
                $this->agora()
            );
    }

    public function test_rejeita_certificado_sem_emissor(): void
    {
        $certificados = $this->certificados();

        unset($certificados[0]['Issuer']);

        $this->expectException(
            UnexpectedValueException::class
        );

        (new HttpTlsCertificadoParser())
            ->interpretar(
                $certificados,
                $this->agora()
            );
    }
}
