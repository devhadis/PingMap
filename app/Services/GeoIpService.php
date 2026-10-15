<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoIpService
{
    public function __construct(
        private readonly TracerouteService $traceroute = new TracerouteService()
    ) {
    }

    public function validarIp(string $ip): string
    {
        return $this->traceroute->validarDestino($ip);
    }

    public function localizar(string $ip): ?array
    {
        // Valida antes de fazer qualquer requisição HTTP.
        $ip = $this->validarIp($ip);

        $chave = 'pingmap:geoip:v1:' . $ip;

        $armazenado = Cache::get($chave);

        if ($armazenado !== null) {
            $latitude = is_array($armazenado)
                ? ($armazenado['latitude'] ?? null)
                : null;

            $longitude = is_array($armazenado)
                ? ($armazenado['longitude'] ?? null)
                : null;

            $cacheValido = is_array($armazenado)
                && ($armazenado['ip'] ?? null) === $ip
                && is_numeric($latitude)
                && is_numeric($longitude)
                && is_finite((float) $latitude)
                && is_finite((float) $longitude)
                && (float) $latitude >= -90
                && (float) $latitude <= 90
                && (float) $longitude >= -180
                && (float) $longitude <= 180;

            if ($cacheValido) {
                return $armazenado;
            }

            // Descarta o cache inválido antes de consultar o provedor.
            Cache::forget($chave);
        }

        try {
            $resposta = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->withoutRedirecting()
                ->get('https://ipwho.is/' . $ip);
        } catch (ConnectionException $erro) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $dados = $resposta->json();

        if (! is_array($dados)
            || ($dados['success'] ?? false) !== true
            || ($dados['ip'] ?? null) !== $ip
        ) {
            return null;
        }

        $latitude = $dados['latitude'] ?? null;
        $longitude = $dados['longitude'] ?? null;

        if (! is_numeric($latitude)
            || ! is_numeric($longitude)
        ) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if (! is_finite($latitude)
            || ! is_finite($longitude)
            || $latitude < -90
            || $latitude > 90
            || $longitude < -180
            || $longitude > 180
        ) {
            return null;
        }

        $conexao = $dados['connection'] ?? [];

        if (! is_array($conexao)) {
            $conexao = [];
        }

        $localizacao = [
            'ip' => $ip,
            'pais' => $dados['country'] ?? null,
            'codigo_pais' => $dados['country_code'] ?? null,
            'cidade' => $dados['city'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'asn' => $conexao['asn'] ?? null,
            'organizacao' => $conexao['org'] ?? null,
        ];

        Cache::put($chave, $localizacao, now()->addDay());

        return $localizacao;
    }
}
