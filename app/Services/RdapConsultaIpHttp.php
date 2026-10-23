<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;
use UnexpectedValueException;

class RdapConsultaIpHttp
{
    private const LIMITE_BYTES = 2 * 1024 * 1024;

    private RdapConsultaDns $dns;

    public function __construct(?RdapConsultaDns $dns = null)
    {
        $this->dns = $dns ?? new RdapConsultaDns();
    }

    public function consultar(string $ip, string $servidor): array
    {
        $ip = (new EnderecoIpPublico())->validar($ip);

        $servidor = (new RdapDestinoSeguro())
            ->validar($servidor);

        $url = rtrim($servidor, '/')
            . '/ip/'
            . rawurlencode($ip);

        // Resolver e validar o DNS antes da requisição.
        $host = parse_url($servidor, PHP_URL_HOST);

        if (! is_string($host)) {
            throw new UnexpectedValueException(
                'Hostname RDAP inválido.'
            );
        }

        $enderecos = $this->dns->resolver($host);

        // Fixar os IPs aprovados nas opções do cURL.
        $opcoes = (new RdapOpcoesConexaoSegura())
            ->preparar($host, $enderecos);

        // Preservar o limite de transferência.
        $opcoes['progress'] = new RdapLimiteTransferencia();

        try {
            $resposta = Http::accept(
                'application/rdap+json'
            )
                ->setHandler(
                    (new RdapTransporteSeguro())->criar()
                )
                ->withOptions($opcoes)
                ->connectTimeout(3)
                ->timeout(10)
                ->get($url);

        } catch (\GuzzleHttp\Exception\ResponseException $e) {
            $causa = $e;

            while ($causa->getPrevious() !== null) {
                $causa = $causa->getPrevious();
            }

            if (
                $causa instanceof UnexpectedValueException
                && $causa->getMessage() ===
                    'Transferência RDAP excedeu o limite de 2 MB.'
            ) {
                throw new UnexpectedValueException(
                    'Transferência RDAP excedeu o limite de 2 MB.',
                    0,
                    $e
                );
            }

            throw $e;
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Falha de conexão com o servidor RDAP.',
                0,
                $e
            );
        }

        if (! $resposta->successful()) {
            throw new RuntimeException(
                'Servidor RDAP retornou um erro HTTP.'
            );
        }

        // Validar o Content-Length, quando informado.
        $tamanho = $resposta->header('Content-Length');

        if (array_key_exists(
            'content-length',
            array_change_key_case($resposta->headers(), CASE_LOWER)
        )) {
            $tamanho = trim((string) $tamanho);

            if (
                ! preg_match('/^[0-9]+$/', $tamanho) ||
                strlen($tamanho) > 10 ||
                (float) $tamanho > self::LIMITE_BYTES
            ) {
                throw new UnexpectedValueException(
                    'Content-Length RDAP inválido ou excessivo.'
                );
            }
        }

        // Validar o tipo de conteúdo, quando informado.
        $tipo = $resposta->header('Content-Type');

        if (array_key_exists(
            'content-type',
            array_change_key_case($resposta->headers(), CASE_LOWER)
        )) {
            $tipo = strtolower(
                trim(explode(';', (string) $tipo)[0])
            );

            if (
                ! in_array($tipo, [
                    'application/rdap+json',
                    'application/json',
                ], true)
            ) {
                throw new UnexpectedValueException(
                    'Tipo de conteúdo RDAP não permitido.'
                );
            }
        }

        $corpo = $resposta->body();

        // Validar o tamanho efetivamente recebido.
        if (strlen($corpo) > self::LIMITE_BYTES) {
            throw new UnexpectedValueException(
                'Resposta RDAP excede o tamanho permitido.'
            );
        }

        try {
            $dados = json_decode(
                $corpo,
                true,
                32,
                JSON_THROW_ON_ERROR
            );

        } catch (JsonException $e) {
            throw new UnexpectedValueException(
                'Resposta RDAP contém JSON inválido.',
                0,
                $e
            );
        }

        if (! is_array($dados) || array_is_list($dados)) {
            throw new UnexpectedValueException(
                'Estrutura da resposta RDAP inválida.'
            );
        }

        return $dados;
    }
}
