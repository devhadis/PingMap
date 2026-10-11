<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DnsService
{
    private const TIPOS = [
        'A', 'AAAA', 'MX', 'TXT', 'NS',
        'CNAME', 'SOA', 'PTR', 'CAA',
    ];

    public function __construct(
        private readonly ?\Closure $resolvedor = null
    ) {}

    public function consultar(string $dominio, string $tipo): array
    {
        $tipo = strtoupper(trim($tipo));
        $dominio = strtolower(rtrim(trim($dominio), '.'));

        if (!in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException(
                'Tipo DNS não permitido.'
            );
        }

        if ($tipo === 'PTR' && filter_var($dominio, FILTER_VALIDATE_IP)) {
            if (!filter_var(
                $dominio,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                throw new InvalidArgumentException(
                    'Informe um IP público permitido.'
                );
            }

            $binario = inet_pton($dominio);

            if (strlen($binario) === 4) {
                $dominio = implode(
                    '.',
                    array_reverse(explode('.', $dominio))
                ) . '.in-addr.arpa';
            } else {
                $dominio = implode(
                    '.',
                    array_reverse(str_split(bin2hex($binario)))
                ) . '.ip6.arpa';
            }
        }

        $this->validarDominio($dominio, $tipo);

        $constante = 'DNS_' . $tipo;

        if (!defined($constante)) {
            throw new RuntimeException(
                'Tipo DNS indisponível nesta versão do PHP.'
            );
        }

        try {
            $registros = $this->resolvedor
                ? ($this->resolvedor)($dominio, constant($constante))
                : @dns_get_record($dominio, constant($constante));
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Falha ao consultar o serviço DNS.',
                0,
                $e
            );
        }

        if ($registros === false || !is_array($registros)) {
            throw new RuntimeException(
                'Falha ao consultar o serviço DNS.'
            );
        }

        return [
            'dominio' => $dominio,
            'tipo' => $tipo,
            'status' => count($registros) > 0
                ? 'sucesso'
                : 'sem_registros',
            'quantidade' => count($registros),
            'registros' => $registros,
        ];
    }

    private function validarDominio(string $dominio, string $tipo): void
    {
        if (
            strlen($dominio) > 253 ||
            strlen($dominio) < 4 ||
            !preg_match('/^[a-z0-9._-]+$/D', $dominio)
        ) {
            throw new InvalidArgumentException(
                'Domínio DNS inválido.'
            );
        }

        $labels = explode('.', $dominio);

        if (count($labels) < 2) {
            throw new InvalidArgumentException(
                'Informe um domínio completo.'
            );
        }

        foreach ($labels as $label) {
            if (
                strlen($label) < 1 ||
                strlen($label) > 63 ||
                !preg_match('/^[a-z0-9_](?:[a-z0-9_-]*[a-z0-9_])?$/D', $label)
            ) {
                throw new InvalidArgumentException(
                    'Domínio DNS inválido.'
                );
            }

            if (str_contains($label, '_') && $tipo !== 'TXT') {
                if (
                    !($tipo === 'PTR' &&
                    (str_ends_with($dominio, '.in-addr.arpa') ||
                     str_ends_with($dominio, '.ip6.arpa')))
                ) {
                    throw new InvalidArgumentException(
                        'Nome DNS incompatível com o tipo solicitado.'
                    );
                }
            }
        }

        $sufixosBloqueados = [
            'localhost', 'local', 'internal', 'lan',
            'test', 'invalid', 'example', 'onion',
            'home.arpa',
        ];

        foreach ($sufixosBloqueados as $sufixo) {
            if (
                $dominio === $sufixo ||
                str_ends_with($dominio, '.' . $sufixo)
            ) {
                throw new InvalidArgumentException(
                    'Domínio reservado ou interno não permitido.'
                );
            }
        }

        if (
            str_ends_with($dominio, '.arpa') &&
            $tipo !== 'PTR'
        ) {
            throw new InvalidArgumentException(
                'Zona reversa permitida apenas para PTR.'
            );
        }
    }
}
