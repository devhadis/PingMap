<?php

namespace App\Services;

use UnexpectedValueException;

class RdapLimiteTransferencia
{
    private const LIMITE_BYTES = 2 * 1024 * 1024;

    public function __invoke(
        float $totalDownload,
        float $bytesRecebidos,
        float $totalUpload,
        float $bytesEnviados
    ): void {
        if (
            $totalDownload > self::LIMITE_BYTES ||
            $bytesRecebidos > self::LIMITE_BYTES
        ) {
            throw new UnexpectedValueException(
                'Transferência RDAP excedeu o limite de 2 MB.'
            );
        }
    }
}
