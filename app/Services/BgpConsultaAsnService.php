<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class BgpConsultaAsnService
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
                    'https://stat.ripe.net/data/as-overview/data.json',
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
                'Serviço BGP indisponível.'
            );
        }

        BgpRespostaLimitada::verificar($resposta);

        $dados = $resposta->json();

        if (
            ! is_array($dados) ||
            ($dados['status'] ?? null) !== 'ok' ||
            ! isset($dados['data']) ||
            ! is_array($dados['data'])
        ) {
            throw new UnexpectedValueException(
                'Resposta BGP inválida.'
            );
        }

        $resultado = $dados['data'];

        if (
            ! in_array($resultado['resource'] ?? null, ['AS' . $asn, (string) $asn], true) ||
            ! array_key_exists('holder', $resultado) ||
            (
                $resultado['holder'] !== null &&
                ! is_string($resultado['holder'])
            ) ||
            ! is_bool($resultado['announced'] ?? null)
        ) {
            throw new UnexpectedValueException(
                'Dados BGP incompletos ou inválidos.'
            );
        }

        return [
            'asn' => $asn,
            'nome' => $resultado['holder'],
            'anunciado' => $resultado['announced'],
        ];
    }
}
