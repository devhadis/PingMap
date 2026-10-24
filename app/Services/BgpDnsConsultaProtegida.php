<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class BgpDnsConsultaProtegida
{
    private const URL_DNS = 'https://dns.google/resolve';

    private const LIMITE_RESPOSTA_BYTES = 65536;

    private Closure $relogio;

    public function __construct(?callable $relogio = null)
    {
        $this->relogio = $relogio !== null
            ? Closure::fromCallable($relogio)
            : static fn (): float => hrtime(true) / 1_000_000_000;
    }

    public function consultar(
        string $entrada,
        ?float $prazo = null
    ): array {
        // Valida antes de realizar qualquer requisição.
        $alvo = (new BgpAlvoValidador())
            ->normalizar($entrada);

        if ($alvo['tipo'] !== 'dominio') {
            throw new InvalidArgumentException(
                'Informe um domínio válido.'
            );
        }

        $dominio = $alvo['valor'];

        $registros = [];

        // Consulta registros IPv4 e IPv6.
        foreach (['A' => 1, 'AAAA' => 28] as $tipo => $codigo) {

            // No máximo duas tentativas por tipo de registro.
            for ($tentativa = 1; $tentativa <= 2; $tentativa++) {
                $tempoLimite = 4;
                $tempoConexao = 2;

                // Nunca ultrapassar o prazo compartilhado.
                if ($prazo !== null) {
                    $restante = $this->segundosRestantes($prazo);

                    $tempoLimite = min(4, $restante);
                    $tempoConexao = min(2, $tempoLimite);
                }

                try {
                    $resposta = Http::acceptJson()
                        ->withHeaders([
                            'Accept' => 'application/dns-json',
                        ])
                        ->connectTimeout($tempoConexao)
                        ->timeout($tempoLimite)
                        ->withoutRedirecting()
                        ->withOptions(
                            BgpRespostaLimitada::opcoes()
                        )
                        ->get(self::URL_DNS, [
                            'name' => $dominio,
                            'type' => $tipo,
                        ]);

                    // Sucesso: não repetir a requisição.
                    break;

                } catch (ConnectionException $erro) {

                    // O prazo total tem prioridade sobre o retry.
                    if (
                        $prazo !== null &&
                        ($this->relogio)() >= $prazo
                    ) {
                        throw new RuntimeException(
                            'Tempo limite da consulta DNS excedido.',
                            0,
                            $erro
                        );
                    }

                    // Segunda falha: encerrar a consulta.
                    if ($tentativa === 2) {
                        throw new RuntimeException(
                            'Falha na conexão com o serviço DNS.',
                            0,
                            $erro
                        );
                    }

                    // Não iniciar outra tentativa sem tempo útil.
                    if ($prazo !== null) {
                        $this->segundosRestantes($prazo);
                    }
                }
            }

            // Verificar novamente após receber a resposta.
            if ($prazo !== null) {
                $this->segundosRestantes($prazo);
            }

            if (! $resposta->successful()) {
                throw new RuntimeException(
                    'Serviço DNS indisponível.'
                );
            }

            // Proteção geral de transferência existente.
            BgpRespostaLimitada::verificar($resposta);

            // Limite mais restrito para o conteúdo DNS.
            if (
                strlen($resposta->body()) >
                self::LIMITE_RESPOSTA_BYTES
            ) {
                throw new UnexpectedValueException(
                    'Resposta DNS muito grande.'
                );
            }

            $dados = $resposta->json();

            if (
                ! is_array($dados) ||
                ! is_int($dados['Status'] ?? null)
            ) {
                throw new UnexpectedValueException(
                    'Resposta DNS inválida.'
                );
            }

            // Código DNS 3: domínio inexistente.
            if ($dados['Status'] === 3) {
                continue;
            }

            if ($dados['Status'] !== 0) {
                throw new RuntimeException(
                    'O servidor DNS não conseguiu responder.'
                );
            }

            $respostasDns = $dados['Answer'] ?? [];

            if (
                ! is_array($respostasDns) ||
                count($respostasDns) > 128
            ) {
                throw new UnexpectedValueException(
                    'Registros DNS inválidos.'
                );
            }

            foreach ($respostasDns as $registro) {
                if (! is_array($registro)) {
                    throw new UnexpectedValueException(
                        'Registro DNS inválido.'
                    );
                }

                // Ignora CNAME e outros tipos de registros.
                if (($registro['type'] ?? null) !== $codigo) {
                    continue;
                }

                $ip = $registro['data'] ?? null;

                $familia = $tipo === 'A'
                    ? FILTER_FLAG_IPV4
                    : FILTER_FLAG_IPV6;

                if (
                    ! is_string($ip) ||
                    filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        $familia
                    ) === false
                ) {
                    throw new UnexpectedValueException(
                        'Endereço IP inválido na resposta DNS.'
                    );
                }

                if ($tipo === 'A') {
                    $registros[] = [
                        'type' => 'A',
                        'ip' => $ip,
                    ];
                } else {
                    $registros[] = [
                        'type' => 'AAAA',
                        'ipv6' => $ip,
                    ];
                }
            }
        }

        if ($prazo !== null) {
            $this->segundosRestantes($prazo);
        }

        return $registros;
    }

    private function segundosRestantes(float $prazo): int
    {
        $restante = $prazo - ($this->relogio)();

        if ($restante < 1.0) {
            throw new RuntimeException(
                'Tempo limite da consulta DNS excedido.'
            );
        }

        return (int) floor($restante);
    }
}
