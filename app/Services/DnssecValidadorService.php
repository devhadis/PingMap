<?php

namespace App\Services;

class DnssecValidadorService
{
    public function montarComando(string $dominio): array
    {
        $entrada = (new DnsService())->validarEntrada(
            $dominio,
            'A'
        );

        return [
            '/usr/bin/delv',
            '@1.1.1.1',
            $entrada['dominio'],
            'A',
        ];
    }

    public function executar(
        string $dominio,
        ?callable $executor = null
    ): string {
        // Validar antes de iniciar qualquer processo.
        $comando = $this->montarComando($dominio);

        if ($executor !== null) {
            try {
                $resultado = $executor($comando);
            } catch (\RuntimeException $erro) {
                return 'indeterminado';
            }
        } else {
            $processo = new \Symfony\Component\Process\Process(
                $comando
            );

            $processo->setTimeout(12);

            try {
                $processo->run();

                $resultado = [
                    'saida' => $processo->getOutput(),
                    'erro' => $processo->getErrorOutput(),
                    'codigo' => $processo->getExitCode(),
                ];
            } catch (
                \Symfony\Component\Process\Exception\ExceptionInterface $erro
            ) {
                return 'indeterminado';
            }
        }

        if (
            !is_array($resultado) ||
            !is_string($resultado['saida'] ?? null) ||
            !is_string($resultado['erro'] ?? null) ||
            !is_int($resultado['codigo'] ?? null)
        ) {
            return 'indeterminado';
        }

        if (
            preg_match(
                '/^\\s*;;\\s*resolution failed:/mi',
                $resultado['erro']
            )
        ) {
            return 'indeterminado';
        }

        if (trim($resultado['erro']) !== '') {
            return 'indeterminado';
        }

        $estado = $this->interpretarSaida(
            $resultado['saida'],
            $resultado['codigo']
        );

        if ($estado !== 'seguro') {
            return $estado;
        }

        // Confirmar que a resposta contém dados válidos.
        $dominioConsultado = $comando[2];

        if (!$this->possuiRegistroValido(
            $resultado['saida'],
            $dominioConsultado
        )) {
            return 'indeterminado';
        }

        return 'seguro';
    }

    public function interpretarSaida(
        string $saida,
        int $codigoSaida
    ): string {
        if ($codigoSaida !== 0) {
            return 'indeterminado';
        }

        if (preg_match('/^\s*;;\s*resolution failed:/mi', $saida)) {
            return 'indeterminado';
        }

        $validado = preg_match(
            '/^;\s*fully validated\s*$/mi',
            $saida
        ) === 1;

        $semAssinatura = preg_match(
            '/^;\s*unsigned answer\s*$/mi',
            $saida
        ) === 1;

        if ($validado && $semAssinatura) {
            return 'indeterminado';
        }

        if ($validado) {
            return 'seguro';
        }

        if ($semAssinatura) {
            return 'inseguro';
        }

        return 'indeterminado';
    }


    private function possuiRegistroValido(
        string $saida,
        string $dominio
    ): bool {
        $registros = [];

        foreach (preg_split('/\r?\n/', $saida) as $linha) {
            if (!preg_match(
                '/^([a-zA-Z0-9.-]+)\s+(?:\d+\s+)?IN\s+(A|CNAME)\s+(\S+)\s*$/i',
                trim($linha),
                $partes
            )) {
                continue;
            }

            $nome = strtolower(rtrim($partes[1], '.'));
            $tipo = strtoupper($partes[2]);
            $valor = $partes[3];

            $registros[$nome][$tipo][] = $valor;
        }

        $dominio = strtolower(rtrim($dominio, '.'));

        // Resposta A diretamente para o domínio.
        foreach ($registros[$dominio]['A'] ?? [] as $ip) {
            if (filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4
            ) !== false) {
                return true;
            }
        }

        // Resposta CNAME com destino e registro A válido.
        foreach ($registros[$dominio]['CNAME'] ?? [] as $destino) {
            $destino = strtolower(rtrim($destino, '.'));

            if (
                strlen($destino) > 253 ||
                !preg_match(
                    '/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/',
                    $destino
                )
            ) {
                continue;
            }

            foreach ($registros[$destino]['A'] ?? [] as $ip) {
                if (filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_IPV4
                ) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

}
