<?php

namespace App\Services;

use Closure;
use UnexpectedValueException;

class RdapConsultaDns
{
    private const HOSTS_PERMITIDOS = [
        'rdap.arin.net',
        'rdap.db.ripe.net',
        'rdap.apnic.net',
        'rdap.lacnic.net',
        'rdap.afrinic.net',
    ];

    private Closure $consulta;

    public function __construct(?callable $consulta = null)
    {
        $this->consulta = $consulta !== null
            ? Closure::fromCallable($consulta)
            : static function (string $host): array {
                $registros = dns_get_record(
                    $host,
                    DNS_A | DNS_AAAA
                );

                if ($registros === false) {
                    throw new UnexpectedValueException(
                        'Falha na resolução DNS RDAP.'
                    );
                }

                $enderecos = [];

                foreach ($registros as $registro) {
                    if (
                        isset($registro['ip']) &&
                        is_string($registro['ip'])
                    ) {
                        $enderecos[] = $registro['ip'];
                    }

                    if (
                        isset($registro['ipv6']) &&
                        is_string($registro['ipv6'])
                    ) {
                        $enderecos[] = $registro['ipv6'];
                    }
                }

                return $enderecos;
            };
    }

    public function resolver(string $host): array
    {
        if (! in_array($host, self::HOSTS_PERMITIDOS, true)) {
            throw new UnexpectedValueException(
                'Hostname RDAP não autorizado.'
            );
        }

        $enderecos = ($this->consulta)($host);

        if (! is_array($enderecos)) {
            throw new UnexpectedValueException(
                'Resposta DNS RDAP inválida.'
            );
        }

        return (new RdapResolucaoDnsSegura())
            ->validar($enderecos);
    }
}
