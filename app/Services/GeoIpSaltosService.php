<?php

namespace App\Services;

use InvalidArgumentException;

class GeoIpSaltosService
{
    public function __construct(
        private readonly GeoIpService $geoIp
    ) {
    }

    public function enriquecer(array $saltos): array
    {
        $localizacoes = [];
        $resultado = [];

        foreach ($saltos as $salto) {
            $ip = $salto['ip'] ?? null;

            // Preserva todas as métricas originais.
            $salto['geo'] = null;

            if (! is_string($ip)) {
                $resultado[] = $salto;
                continue;
            }

            // Nesta etapa, somente IPv4 público.
            // A validação dual-stack será implementada depois.
            try {
                (new TracerouteService())->validarDestino($ip);
            } catch (InvalidArgumentException $erro) {
                $resultado[] = $salto;
                continue;
            }

            // Evita consultar o mesmo IP mais de uma vez.
            if (! array_key_exists($ip, $localizacoes)) {
                $localizacoes[$ip] = $this->geoIp->localizar($ip);
            }

            $salto['geo'] = $localizacoes[$ip];

            $resultado[] = $salto;
        }

        return $resultado;
    }
}
