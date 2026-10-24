<?php

namespace App\Services;

class HttpTlsResolucaoSegura
{
    public function __construct(
        private ?BgpResolucaoDominioService $dns = null
    ) {
    }

    public function resolver(
        string $entrada,
        ?float $prazo = null
    ): array {
        // Primeiro: validar e normalizar o domínio.
        $dominio = (new HttpTlsAlvoValidador())
            ->validar($entrada);

        // Segundo: reutilizar a resolução DNS protegida.
        $resolvedor = $this->dns
            ?? new BgpResolucaoDominioService();

        $resultado = $resolvedor->resolver(
            $dominio,
            $prazo
        );

        // Terceiro: retornar os endereços aprovados.
        return [
            'dominio' => $dominio,
            'ips' => $resultado['ips'],
        ];
    }
}
