<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;
use UnexpectedValueException;

class RdapCatalogoIana
{
    private const URL_IPV4 =
        'https://data.iana.org/rdap/ipv4.json';

    private const URL_IPV6 =
        'https://data.iana.org/rdap/ipv6.json';

    private const LIMITE_BYTES = 2 * 1024 * 1024;

    public function obter(string $ip): array
    {
        $ip = (new EnderecoIpPublico())->validar($ip);

        $ipv6 = strlen(inet_pton($ip)) === 16;
        $versao = $ipv6 ? 'ipv6' : 'ipv4';
        $url = $ipv6 ? self::URL_IPV6 : self::URL_IPV4;

        return Cache::remember(
            'rdap:iana:catalogo:' . $versao,
            now()->addDay(),
            fn () => $this->baixar($url, $ipv6)
        );
    }

    private function baixar(string $url, bool $ipv6): array
    {
        try {
            $resposta = Http::acceptJson()
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(3)
                ->timeout(10)
                ->get($url);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Falha de conexão com o catálogo IANA.',
                0,
                $e
            );
        }

        if (! $resposta->successful()) {
            throw new RuntimeException(
                'Não foi possível obter o catálogo IANA.'
            );
        }

        $corpo = $resposta->body();

        if (strlen($corpo) > self::LIMITE_BYTES) {
            throw new UnexpectedValueException(
                'Catálogo IANA excede o tamanho permitido.'
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
                'JSON inválido no catálogo IANA.',
                0,
                $e
            );
        }

        if (
            ! is_array($dados) ||
            ($dados['version'] ?? null) !== '1.0' ||
            ! isset($dados['services']) ||
            ! is_array($dados['services']) ||
            $dados['services'] === []
        ) {
            throw new UnexpectedValueException(
                'Estrutura inválida do catálogo IANA.'
            );
        }

        $encontrouServidorSeguro = false;

        foreach ($dados['services'] as $indice => $servico) {
            if (
                ! is_array($servico) ||
                count($servico) !== 2 ||
                ! isset($servico[0], $servico[1]) ||
                ! is_array($servico[0]) ||
                ! is_array($servico[1]) ||
                $servico[0] === [] ||
                $servico[1] === []
            ) {
                throw new UnexpectedValueException(
                    'Serviço inválido no catálogo IANA.'
                );
            }

            foreach ($servico[0] as $cidr) {
                $this->validarCidr($cidr, $ipv6);
            }

            $servidoresSeguros = [];

            foreach ($servico[1] as $servidor) {
                try {
                    $this->validarServidor($servidor);
                    $servidoresSeguros[] = $servidor;
                } catch (UnexpectedValueException) {
                    // Nunca utilizar URLs RDAP inseguras.
                }
            }

            $dados['services'][$indice][1] = $servidoresSeguros;

            if ($servidoresSeguros !== []) {
                $encontrouServidorSeguro = true;
            }
        }

        if (! $encontrouServidorSeguro) {
            throw new UnexpectedValueException(
                'Catálogo IANA sem servidores RDAP HTTPS válidos.'
            );
        }

        return $dados;
    }

    private function validarCidr(mixed $cidr, bool $ipv6): void
    {
        if (! is_string($cidr)) {
            throw new UnexpectedValueException(
                'Prefixo RDAP inválido.'
            );
        }

        $partes = explode('/', $cidr);

        if (
            count($partes) !== 2 ||
            ! ctype_digit($partes[1])
        ) {
            throw new UnexpectedValueException(
                'Formato CIDR inválido.'
            );
        }

        $rede = inet_pton($partes[0]);

        if ($rede === false) {
            throw new UnexpectedValueException(
                'Endereço de rede inválido.'
            );
        }

        $tamanhoEsperado = $ipv6 ? 16 : 4;
        $limite = $ipv6 ? 128 : 32;
        $prefixo = (int) $partes[1];

        if (
            strlen($rede) !== $tamanhoEsperado ||
            $prefixo > $limite
        ) {
            throw new UnexpectedValueException(
                'Prefixo incompatível com o catálogo.'
            );
        }
    }

    private function validarServidor(mixed $url): void
    {
        if (
            ! is_string($url) ||
            preg_match('/[\s\\\\]/', $url) ||
            ! str_starts_with($url, 'https://')
        ) {
            throw new UnexpectedValueException(
                'URL RDAP inválida ou sem HTTPS.'
            );
        }

        $partes = parse_url($url);

        if (
            $partes === false ||
            ! isset($partes['host']) ||
            isset($partes['user']) ||
            isset($partes['pass']) ||
            isset($partes['query']) ||
            isset($partes['fragment']) ||
            (isset($partes['port']) && $partes['port'] !== 443)
        ) {
            throw new UnexpectedValueException(
                'Componentes de URL RDAP não permitidos.'
            );
        }

        $host = strtolower($partes['host']);

        if (
            filter_var($host, FILTER_VALIDATE_IP) !== false ||
            filter_var(
                $host,
                FILTER_VALIDATE_DOMAIN,
                FILTER_FLAG_HOSTNAME
            ) === false ||
            ! str_contains($host, '.') ||
            str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local') ||
            $host === 'localhost'
        ) {
            throw new UnexpectedValueException(
                'Destino RDAP não permitido.'
            );
        }
    }
}
