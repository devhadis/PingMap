<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class BgpRespostaLimitada
{
    public const LIMITE_BYTES = 2097152;

    public static function opcoes(): array
    {
        return [
            // Rejeita um tamanho excessivo anunciado.
            'on_headers' => static function (
                ResponseInterface $resposta
            ): void {
                $tamanho = $resposta->getHeaderLine(
                    'Content-Length'
                );

                if (
                    $tamanho !== '' &&
                    ctype_digit($tamanho) &&
                    (int) $tamanho > self::LIMITE_BYTES
                ) {
                    throw new UnexpectedValueException(
                        'Resposta BGP acima do limite permitido.'
                    );
                }
            },

            // Interrompe transferências excessivas.
            'progress' => static function (
                $totalDownload,
                $baixado,
                $totalUpload,
                $enviado
            ): void {
                if ($baixado > self::LIMITE_BYTES) {
                    throw new UnexpectedValueException(
                        'Resposta BGP acima do limite permitido.'
                    );
                }
            },
        ];
    }

    public static function verificar(Response $resposta): void
    {
        $tamanho = $resposta
            ->toPsrResponse()
            ->getBody()
            ->getSize();

        if (
            $tamanho === null ||
            $tamanho > self::LIMITE_BYTES
        ) {
            throw new UnexpectedValueException(
                'Resposta BGP acima do limite permitido.'
            );
        }
    }
}
