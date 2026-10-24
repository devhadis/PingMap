<?php

namespace App\Services;

use Closure;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class BgpResolucaoDominioService
{
    private const LIMITE_IPS = 8;

    private Closure $consultaDns;

    public function __construct(?callable $consultaDns = null)
    {
        // Permite simular o DNS durante os testes.
        $this->consultaDns = $consultaDns !== null
            ? Closure::fromCallable($consultaDns)
            : static fn (string $dominio, int $tipos, ?float $prazo = null): array =>
                (new BgpDnsConsultaProtegida())->consultar($dominio, $prazo);
    }

    public function resolver(string $entrada, ?float $prazo = null): array
    {
        // Reutiliza a validação de domínio existente.
        $alvo = (new BgpAlvoValidador())
            ->normalizar($entrada);

        if ($alvo['tipo'] !== 'dominio') {
            throw new InvalidArgumentException(
                'Informe um domínio válido.'
            );
        }

        $dominio = $alvo['valor'];

        // Busca endereços IPv4 e IPv6.
        $registros = ($this->consultaDns)($dominio, DNS_A | DNS_AAAA, $prazo);

        if ($registros === false) {
            throw new RuntimeException(
                'Falha na resolução DNS.'
            );
        }

        if (! is_array($registros)) {
            throw new UnexpectedValueException(
                'Resposta DNS inválida.'
            );
        }

        // Proteção contra respostas excessivas.
        if (count($registros) > 128) {
            throw new RuntimeException(
                'Quantidade excessiva de registros DNS.'
            );
        }

        $ips = [];
        $vistos = [];
        $validador = new EnderecoIpPublico();

        foreach ($registros as $registro) {
            if (! is_array($registro)) {
                throw new UnexpectedValueException(
                    'Registro DNS inválido.'
                );
            }

            $tipo = $registro['type'] ?? null;

            if ($tipo === 'A') {
                $ip = $registro['ip'] ?? null;
                $familia = FILTER_FLAG_IPV4;

            } elseif ($tipo === 'AAAA') {
                $ip = $registro['ipv6'] ?? null;
                $familia = FILTER_FLAG_IPV6;

            } else {
                continue;
            }

            if (
                ! is_string($ip) ||
                filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    $familia
                ) === false
            ) {
                throw new UnexpectedValueException(
                    'Endereço retornado pelo DNS é inválido.'
                );
            }

            // Rejeita IPs privados, reservados e especiais.
            $validador->validar($ip);

            // Normaliza também a representação IPv6.
            $ip = inet_ntop(inet_pton($ip));

            if (isset($vistos[$ip])) {
                continue;
            }

            $vistos[$ip] = true;

            // Continua validando todos os registros,
            // mesmo depois de atingir o limite.
            if (count($ips) < self::LIMITE_IPS) {
                $ips[] = $ip;
            }
        }

        if ($ips === []) {
            throw new RuntimeException(
                'Domínio sem endereços IP públicos.'
            );
        }

        return [
            'dominio' => $dominio,
            'ips' => $ips,
        ];
    }
}
