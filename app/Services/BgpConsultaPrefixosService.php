<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class BgpConsultaPrefixosService
{
    public function consultar(int $asn): array
    {
        if ($asn < 1 || $asn > 4294967294) {
            throw new InvalidArgumentException('ASN inválido.');
        }

        try {
            $resposta = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->withoutRedirecting()
                ->withOptions(BgpRespostaLimitada::opcoes())
                ->get(
                    'https://stat.ripe.net/data/announced-prefixes/data.json',
                    ['resource' => 'AS' . $asn]
                );
        } catch (ConnectionException $erro) {
            throw new RuntimeException(
                'Não foi possível conectar ao serviço BGP.',
                0,
                $erro
            );
        }

        if (! $resposta->successful()) {
            throw new RuntimeException(
                'Serviço de prefixos BGP indisponível.'
            );
        }

        BgpRespostaLimitada::verificar($resposta);

        $dados = $resposta->json();

        if (
            ! is_array($dados) ||
            ($dados['status'] ?? null) !== 'ok' ||
            ! is_array($dados['data'] ?? null) ||
            ! in_array($dados['data']['resource'] ?? null, ['AS' . $asn, (string) $asn], true) ||
            ! is_array($dados['data']['prefixes'] ?? null)
        ) {
            throw new UnexpectedValueException(
                'Resposta de prefixos BGP inválida.'
            );
        }

        $resultado = [
            'ipv4' => [],
            'ipv6' => [],
        ];

        foreach ($dados['data']['prefixes'] as $item) {
            if (
                ! is_array($item) ||
                ! is_string($item['prefix'] ?? null)
            ) {
                continue;
            }

            $prefixo = $item['prefix'];

            if (strlen($prefixo) > 64) {
                continue;
            }

            $partes = explode('/', $prefixo);

            if (count($partes) !== 2) {
                continue;
            }

            [$ip, $mascara] = $partes;

            if (
                ! preg_match(
                    '/\A(?:0|[1-9][0-9]{0,2})\z/',
                    $mascara
                )
            ) {
                continue;
            }

            if (
                filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV4
                ) !== false
            ) {
                $tipo = 'ipv4';
                $limite = 32;
            } elseif (
                filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV6
                ) !== false
            ) {
                $tipo = 'ipv6';
                $limite = 128;
            } else {
                continue;
            }

            if ((int) $mascara > $limite) {
                continue;
            }

            // A chave elimina prefixos duplicados.
            $resultado[$tipo][$prefixo] = $prefixo;
        }

        return [
            'ipv4' => array_values($resultado['ipv4']),
            'ipv6' => array_values($resultado['ipv6']),
        ];
    }
}
