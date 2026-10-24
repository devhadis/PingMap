<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use UnexpectedValueException;

class HttpTlsCertificadoParser
{
    public function interpretar(
        array $certificados,
        DateTimeImmutable $agora
    ): array {
        // 1. Exigir uma cadeia de certificados válida.
        if (
            $certificados === [] ||
            count($certificados) > 16 ||
            ! is_array($certificados[0] ?? null)
        ) {
            throw new UnexpectedValueException(
                'Cadeia de certificados TLS inválida.'
            );
        }

        // O primeiro certificado pertence ao servidor.
        $principal = $certificados[0];

        // 2. Ler as informações principais.
        $assunto = $this->campo($principal, 'Subject');
        $emissor = $this->campo($principal, 'Issuer');

        $algoritmo = $this->campo(
            $principal,
            'Signature Algorithm'
        );

        // 3. Interpretar as datas.
        $inicio = $this->data($principal, 'Start date');
        $fim = $this->data($principal, 'Expire date');

        if ($inicio >= $fim) {
            throw new UnexpectedValueException(
                'Período de validade TLS inválido.'
            );
        }

        // 4. Identificar os domínios do certificado.
        $san = $principal[
            'X509v3 Subject Alternative Name'
        ] ?? '';

        if (! is_string($san) || strlen($san) > 8192) {
            throw new UnexpectedValueException(
                'Nomes alternativos TLS inválidos.'
            );
        }

        $dominios = [];

        foreach (explode(',', $san) as $entrada) {
            $entrada = trim($entrada);

            if (
                preg_match(
                    '/^DNS:\s*(.+)$/i',
                    $entrada,
                    $partes
                ) !== 1
            ) {
                continue;
            }

            $dominio = trim($partes[1]);

            if (
                $dominio === '' ||
                strlen($dominio) > 253 ||
                preg_match(
                    '/[\x00-\x1F\x7F]/',
                    $dominio
                )
            ) {
                continue;
            }

            $dominios[$dominio] = true;
        }

        // 5. Calcular os dias completos até a expiração.
        $segundosRestantes =
            $fim->getTimestamp() - $agora->getTimestamp();

        $expirado = $segundosRestantes <= 0;

        $diasRestantes = $expirado
            ? 0
            : intdiv($segundosRestantes, 86400);

        // 6. Retornar somente metadados necessários.
        // Nunca incluir o certificado PEM completo.
        return [
            'assunto' => $assunto,
            'emissor' => $emissor,
            'dominios' => array_keys($dominios),
            'inicio_validade' => $inicio->format('c'),
            'fim_validade' => $fim->format('c'),
            'algoritmo_assinatura' => $algoritmo,
            'certificados_na_cadeia' => count($certificados),
            'dias_restantes' => $diasRestantes,
            'expirado' => $expirado,
            'alerta_expiracao' => $diasRestantes <= 30,
        ];
    }

    private function campo(
        array $certificado,
        string $nome
    ): string {
        $valor = $certificado[$nome] ?? null;

        if (
            ! is_string($valor) ||
            trim($valor) === '' ||
            strlen($valor) > 2048 ||
            preg_match('/[\x00-\x1F\x7F]/', $valor)
        ) {
            throw new UnexpectedValueException(
                "Campo TLS inválido: {$nome}."
            );
        }

        return trim($valor);
    }

    private function data(
        array $certificado,
        string $nome
    ): DateTimeImmutable {
        $texto = $this->campo($certificado, $nome);

        $data = DateTimeImmutable::createFromFormat(
            '!M j H:i:s Y T',
            $texto
        );

        $erros = DateTimeImmutable::getLastErrors();

        if (
            $data === false ||
            (
                $erros !== false &&
                (
                    $erros['warning_count'] > 0 ||
                    $erros['error_count'] > 0
                )
            )
        ) {
            throw new UnexpectedValueException(
                "Data TLS inválida: {$nome}."
            );
        }

        return $data->setTimezone(
            new DateTimeZone('UTC')
        );
    }
}
