<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class BgpConsultaRedeService
{
    private const PRAZO_DOMINIO_SEGUNDOS = 12;

    private Closure $relogio;

    public function __construct(
        private readonly ?BgpResolucaoDominioService $resolvedorDns = null,
        ?callable $relogio = null
    ) {
        $this->relogio = $relogio !== null
            ? Closure::fromCallable($relogio)
            : static fn (): float => hrtime(true) / 1_000_000_000;
    }

    public function consultar(string $entrada): array
    {
        return $this->consultarInternamente($entrada, null);
    }

    private function consultarInternamente(
        string $entrada,
        ?float $prazo
    ): array {
        $alvo = (new BgpAlvoValidador())
            ->normalizar($entrada);

        if ($alvo['tipo'] === 'dominio') {
            $prazo = ($this->relogio)()
                + self::PRAZO_DOMINIO_SEGUNDOS;

            $resolvedor = $this->resolvedorDns
                ?? new BgpResolucaoDominioService();

            $resolucao = $resolvedor->resolver($alvo['valor'], $prazo);

            // Não inicia HTTP se o DNS consumiu o prazo.
            $this->segundosRestantes($prazo);

            $resultados = [];

            foreach ($resolucao['ips'] as $ip) {
                // Todos os IPs compartilham o mesmo prazo.
                $resultados[] = $this->consultarInternamente(
                    $ip,
                    $prazo
                );
            }

            $this->segundosRestantes($prazo);

            return [
                'tipo' => 'dominio',
                'consulta' => $resolucao['dominio'],
                'resultados' => $resultados,
            ];
        }

        $tipo = $alvo['tipo'];
        $valor = $alvo['valor'];

        $endpoint = $tipo === 'ip'
            ? 'network-info'
            : 'prefix-overview';

        $tempoLimite = 8;
        $tempoConexao = 3;

        if ($prazo !== null) {
            $restante = $this->segundosRestantes($prazo);

            $tempoLimite = min(8, $restante);
            $tempoConexao = min(3, $tempoLimite);
        }

        try {
            $resposta = Http::acceptJson()
                ->connectTimeout($tempoConexao)
                ->timeout($tempoLimite)
                ->withoutRedirecting()
                ->withOptions(
                    BgpRespostaLimitada::opcoes()
                )
                ->get(
                    'https://stat.ripe.net/data/' .
                    $endpoint . '/data.json',
                    [
                        'resource' => $valor,
                        'sourceapp' => 'pingmap',
                    ]
                );
        } catch (ConnectionException $erro) {
            if (
                $prazo !== null &&
                ($this->relogio)() >= $prazo
            ) {
                throw new RuntimeException(
                    'Tempo limite da consulta BGP por domínio excedido.',
                    0,
                    $erro
                );
            }

            throw new RuntimeException(
                'Não foi possível conectar à RIPEstat.',
                0,
                $erro
            );
        }

        // Verifica se a resposta chegou dentro do prazo.
        if ($prazo !== null) {
            $this->segundosRestantes($prazo);
        }

        if (! $resposta->successful()) {
            throw new RuntimeException(
                'Serviço BGP indisponível.'
            );
        }

        BgpRespostaLimitada::verificar($resposta);

        $json = $resposta->json();

        if (
            ! is_array($json) ||
            ($json['status'] ?? null) !== 'ok' ||
            ! is_array($json['data'] ?? null)
        ) {
            throw new UnexpectedValueException(
                'Resposta BGP inválida.'
            );
        }

        if ($tipo === 'ip') {
            return $this->resultadoIp(
                $valor,
                $json['data']
            );
        }

        return $this->resultadoCidr(
            $valor,
            $json['data']
        );
    }

    private function segundosRestantes(float $prazo): int
    {
        $restante = $prazo - ($this->relogio)();

        // Evita iniciar requisições com timeout de zero.
        if ($restante < 1.0) {
            throw new RuntimeException(
                'Tempo limite da consulta BGP por domínio excedido.'
            );
        }

        return (int) floor($restante);
    }

    private function resultadoIp(
        string $ip,
        array $dados
    ): array {
        if (
            ! array_key_exists('prefix', $dados) ||
            ! is_array($dados['asns'] ?? null)
        ) {
            throw new UnexpectedValueException(
                'Resposta network-info inválida.'
            );
        }

        $prefixo = $dados['prefix'];

        if (
            $prefixo !== null &&
            ! is_string($prefixo)
        ) {
            throw new UnexpectedValueException(
                'Prefixo BGP inválido.'
            );
        }

        $asns = $this->extrairAsns(
            $dados['asns'],
            false
        );

        if ($prefixo === null) {
            if ($asns !== []) {
                throw new UnexpectedValueException(
                    'Resposta BGP inconsistente.'
                );
            }
        } else {
            if (! $this->cidrContemIp($prefixo, $ip)) {
                throw new UnexpectedValueException(
                    'Prefixo não corresponde ao IP consultado.'
                );
            }

            if ($asns === []) {
                throw new UnexpectedValueException(
                    'Prefixo sem ASN de origem.'
                );
            }
        }

        return [
            'tipo' => 'ip',
            'consulta' => $ip,
            'prefixo' => $prefixo,
            'anunciado' => $prefixo !== null,
            'asns' => $asns,
            'coberto_por_prefixo_maior' => false,
        ];
    }

    private function resultadoCidr(
        string $cidr,
        array $dados
    ): array {
        if (
            ! is_string($dados['resource'] ?? null) ||
            ! is_bool($dados['announced'] ?? null) ||
            ! is_array($dados['asns'] ?? null) ||
            ! is_bool($dados['is_less_specific'] ?? null)
        ) {
            throw new UnexpectedValueException(
                'Resposta prefix-overview inválida.'
            );
        }

        $prefixo = $dados['resource'];
        $anunciado = $dados['announced'];
        $coberto = $dados['is_less_specific'];

        // Impede aceitar dados de outra rede.
        if (! $this->cidrContemRede($prefixo, $cidr)) {
            throw new UnexpectedValueException(
                'Prefixo não corresponde à rede consultada.'
            );
        }

        if (
            $prefixo !== $cidr &&
            ! $coberto
        ) {
            throw new UnexpectedValueException(
                'Resposta BGP inconsistente.'
            );
        }

        $asns = $this->extrairAsns(
            $dados['asns'],
            true
        );

        if (
            ($anunciado && $asns === []) ||
            (! $anunciado && $asns !== [])
        ) {
            throw new UnexpectedValueException(
                'Estado do anúncio BGP inconsistente.'
            );
        }

        return [
            'tipo' => 'cidr',
            'consulta' => $cidr,
            'prefixo' => $prefixo,
            'anunciado' => $anunciado,
            'asns' => $asns,
            'coberto_por_prefixo_maior' => $coberto,
        ];
    }

    private function extrairAsns(
        array $itens,
        bool $objetos
    ): array {
        $resultado = [];

        foreach ($itens as $item) {
            if ($objetos) {
                if (! is_array($item)) {
                    throw new UnexpectedValueException(
                        'ASN BGP inválido.'
                    );
                }

                $item = $item['asn'] ?? null;
            }

            if (
                ! is_int($item) &&
                ! (
                    is_string($item) &&
                    preg_match('/\A[0-9]{1,10}\z/', $item)
                )
            ) {
                throw new UnexpectedValueException(
                    'ASN BGP inválido.'
                );
            }

            $asn = (int) $item;

            if ($asn < 1 || $asn > 4294967294) {
                throw new UnexpectedValueException(
                    'ASN fora do intervalo permitido.'
                );
            }

            // Remove ASNs duplicados.
            $resultado[$asn] = $asn;
        }

        return array_values($resultado);
    }

    private function cidrContemRede(
        string $redeMaior,
        string $redeConsultada
    ): bool {
        $partes = explode('/', $redeConsultada);

        if (count($partes) !== 2) {
            return false;
        }

        [$ip, $mascaraConsultada] = $partes;

        $partesMaior = explode('/', $redeMaior);

        if (count($partesMaior) !== 2) {
            throw new UnexpectedValueException(
                'Prefixo BGP inválido.'
            );
        }

        $mascaraMaior = $partesMaior[1];

        if (
            ! preg_match(
                '/\A(?:0|[1-9][0-9]{0,2})\z/',
                $mascaraMaior
            )
        ) {
            throw new UnexpectedValueException(
                'Máscara BGP inválida.'
            );
        }

        if ((int) $mascaraMaior > (int) $mascaraConsultada) {
            return false;
        }

        return $this->cidrContemIp(
            $redeMaior,
            $ip
        );
    }

    private function cidrContemIp(
        string $cidr,
        string $ip
    ): bool {
        $partes = explode('/', $cidr);

        if (count($partes) !== 2) {
            throw new UnexpectedValueException(
                'Prefixo BGP inválido.'
            );
        }

        [$rede, $mascara] = $partes;

        if (
            ! preg_match(
                '/\A(?:0|[1-9][0-9]{0,2})\z/',
                $mascara
            )
        ) {
            throw new UnexpectedValueException(
                'Máscara BGP inválida.'
            );
        }

        $redeBinaria = inet_pton($rede);
        $ipBinario = inet_pton($ip);

        if (
            $redeBinaria === false ||
            $ipBinario === false ||
            strlen($redeBinaria) !== strlen($ipBinario)
        ) {
            throw new UnexpectedValueException(
                'Família de IP incompatível.'
            );
        }

        $bits = (int) $mascara;
        $limite = strlen($redeBinaria) * 8;

        if ($bits > $limite) {
            throw new UnexpectedValueException(
                'Máscara BGP fora do intervalo.'
            );
        }

        $bytes = intdiv($bits, 8);
        $restante = $bits % 8;

        if (
            substr($redeBinaria, 0, $bytes) !==
            substr($ipBinario, 0, $bytes)
        ) {
            return false;
        }

        if ($restante === 0) {
            return true;
        }

        $mascaraByte = (0xFF << (8 - $restante)) & 0xFF;

        return (
            ord($redeBinaria[$bytes]) & $mascaraByte
        ) === (
            ord($ipBinario[$bytes]) & $mascaraByte
        );
    }
}
