<?php

namespace App\Services;

class EmailDnsService
{
    public function __construct(
        private readonly DnsService $dns
    ) {}

    public function analisarSpf(string $dominio): array
    {
        $consulta = $this->dns->consultar($dominio, 'TXT');

        $registros = [];

        foreach ($consulta['registros'] as $registro) {
            $texto = $registro['txt'] ?? null;

            if (!is_string($texto)) {
                $partes = $registro['entries'] ?? null;

                if (!is_array($partes)) {
                    continue;
                }

                foreach ($partes as $parte) {
                    if (!is_string($parte)) {
                        continue 2;
                    }
                }

                $texto = implode('', $partes);
            }

            if (preg_match('/^v=spf1(?:\s|$)/i', $texto)) {
                $registros[] = $texto;
            }
        }

        $quantidade = count($registros);

        if ($quantidade === 0) {
            $status = 'ausente';
        } elseif ($quantidade === 1) {
            $status = 'presente';
        } else {
            $status = 'multiplo';
        }

        $politica = null;

        if ($quantidade === 1) {
            $politica = 'nao_definida';

            $termos = preg_split('/\\s+/', trim($registros[0]));

            foreach ($termos as $termo) {
                if (preg_match('/^([+~?-]?)all$/i', $termo, $partes)) {
                    $politica = match ($partes[1]) {
                        '-' => 'fail',
                        '~' => 'softfail',
                        '?' => 'neutral',
                        default => 'pass',
                    };

                    break;
                }
            }
        }

        return [
            'dominio' => $consulta['dominio'],
            'tipo' => 'SPF',
            'status' => $status,
            'quantidade' => $quantidade,
            'politica' => $politica,
            'registros' => $registros,
        ];
    }

    public function analisarDkim(
        string $dominio,
        string $seletor
    ): array {
        $seletor = strtolower($seletor);

        if (
            strlen($seletor) > 63 ||
            !preg_match(
                '/\\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\z/',
                $seletor
            )
        ) {
            throw new \InvalidArgumentException(
                'Seletor DKIM inválido.'
            );
        }

        $nome = $seletor . '._domainkey.' . $dominio;

        $consulta = $this->dns->consultar($nome, 'TXT');

        $registros = [];

        foreach ($consulta['registros'] as $registro) {
            $texto = $registro['txt'] ?? null;

            if (!is_string($texto)) {
                $partes = $registro['entries'] ?? null;

                if (!is_array($partes)) {
                    continue;
                }

                foreach ($partes as $parte) {
                    if (!is_string($parte)) {
                        continue 2;
                    }
                }

                $texto = implode('', $partes);
            }

            if (preg_match('/^v=DKIM1(?:\\s*;|$)/i', $texto)) {
                $registros[] = $texto;
            }
        }

        $quantidade = count($registros);

        return [
            'dominio' => $dominio,
            'seletor' => $seletor,
            'nome' => $consulta['dominio'],
            'tipo' => 'DKIM',
            'status' => match (true) {
                $quantidade === 0 => 'ausente',
                $quantidade === 1
                && !preg_match(
                    '/(?:^|;)\s*p\s*=\s*[^;\s]+/i',
                    $registros[0]
                ) => 'invalido',
                $quantidade === 1 => 'presente',
                default => 'multiplo',
            },
            'quantidade' => $quantidade,
            'registros' => $registros,
        ];
    }


    public function analisarDmarc(string $dominio): array
    {
        $consulta = $this->dns->consultar(
            '_dmarc.' . $dominio,
            'TXT'
        );

        $registros = [];

        foreach ($consulta['registros'] as $registro) {
            $texto = $registro['txt'] ?? null;

            if (!is_string($texto)) {
                $partes = $registro['entries'] ?? null;

                if (!is_array($partes)) {
                    continue;
                }

                foreach ($partes as $parte) {
                    if (!is_string($parte)) {
                        continue 2;
                    }
                }

                $texto = implode('', $partes);
            }

            if (preg_match('/^v=DMARC1(?:;|$)/i', trim($texto))) {
                $registros[] = trim($texto);
            }
        }

        $quantidade = count($registros);
        $politica = null;
        $status = 'ausente';

        if ($quantidade > 1) {
            $status = 'multiplo';
        } elseif ($quantidade === 1) {
            $status = 'invalido';

            $campos = explode(';', $registros[0]);

            $segundo = explode(
                '=',
                trim($campos[1] ?? ''),
                2
            );

            if (
                count($segundo) !== 2 ||
                strtolower(trim($segundo[0])) !== 'p'
            ) {
                return [
                    'dominio' => $dominio,
                    'tipo' => 'DMARC',
                    'status' => 'invalido',
                    'politica' => null,
                    'quantidade' => $quantidade,
                    'registros' => $registros,
                ];
            }

            $nomes = [];
            foreach ($campos as $campo) {
                $partes = explode('=', trim($campo), 2);

                if (count($partes) !== 2) {
                    if (trim($campo) === '') {
                        continue;
                    }

                    return [
                        'dominio' => $dominio,
                        'tipo' => 'DMARC',
                        'status' => 'invalido',
                        'politica' => null,
                        'quantidade' => $quantidade,
                        'registros' => $registros,
                    ];
                }

                $nome = strtolower(trim($partes[0]));

                if (isset($nomes[$nome])) {
                    return [
                        'dominio' => $dominio,
                        'tipo' => 'DMARC',
                        'status' => 'invalido',
                        'politica' => null,
                        'quantidade' => $quantidade,
                        'registros' => $registros,
                    ];
                }

                $nomes[$nome] = true;
            }

            foreach (array_slice($campos, 1) as $campo) {
                $partes = explode('=', trim($campo), 2);

                if (
                    count($partes) === 2 &&
                    strtolower(trim($partes[0])) === 'p'
                ) {
                    $valor = strtolower(trim($partes[1]));

                    if (in_array(
                        $valor,
                        ['none', 'quarantine', 'reject'],
                        true
                    )) {
                        $politica = $valor;
                        $status = 'presente';
                    }

                    break;
                }
            }
        }

        return [
            'dominio' => $dominio,
            'tipo' => 'DMARC',
            'status' => $status,
            'politica' => $politica,
            'quantidade' => $quantidade,
            'registros' => $registros,
        ];
    }

}
