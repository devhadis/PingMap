<?php

namespace App\Services;

use UnexpectedValueException;

class RdapRespostaIpParser
{
    public function interpretar(array $dados): array
    {
        if (($dados['objectClassName'] ?? null) !== 'ip network') {
            throw new UnexpectedValueException(
                'Resposta RDAP não representa uma rede IP.'
            );
        }

        $inicio = $dados['startAddress'] ?? null;
        $fim = $dados['endAddress'] ?? null;
        $versao = $dados['ipVersion'] ?? null;

        if (! is_string($inicio) || ! is_string($fim)) {
            throw new UnexpectedValueException(
                'Endereços RDAP ausentes ou inválidos.'
            );
        }

        $inicioBinario = inet_pton($inicio);
        $fimBinario = inet_pton($fim);

        if ($inicioBinario === false || $fimBinario === false) {
            throw new UnexpectedValueException(
                'Endereço IP inválido na resposta RDAP.'
            );
        }

        if (strlen($inicioBinario) !== strlen($fimBinario)) {
            throw new UnexpectedValueException(
                'Versões de IP incompatíveis.'
            );
        }

        $versaoEsperada = strlen($inicioBinario) === 4
            ? 'v4'
            : 'v6';

        if ($versao !== $versaoEsperada) {
            throw new UnexpectedValueException(
                'Versão IP inconsistente na resposta RDAP.'
            );
        }

        if (strcmp($inicioBinario, $fimBinario) > 0) {
            throw new UnexpectedValueException(
                'Intervalo de endereços IP invertido.'
            );
        }

        return [
            'handle' => $this->textoOpcional($dados, 'handle'),
            'nome' => $this->textoOpcional($dados, 'name'),
            'inicio' => $inicio,
            'fim' => $fim,
            'versao' => $versao === 'v4' ? 'IPv4' : 'IPv6',
            'pais' => $this->textoOpcional($dados, 'country'),
        ];
    }


    public function interpretarParaIp(
        string $ip,
        array $dados
    ): array {
        $ip = (new EnderecoIpPublico())->validar($ip);

        $resultado = $this->interpretar($dados);

        $ipBinario = inet_pton($ip);
        $inicioBinario = inet_pton($resultado['inicio']);
        $fimBinario = inet_pton($resultado['fim']);

        if (
            strlen($ipBinario) !== strlen($inicioBinario) ||
            strlen($ipBinario) !== strlen($fimBinario)
        ) {
            throw new UnexpectedValueException(
                'A versão IP não corresponde à rede RDAP.'
            );
        }

        if (
            strcmp($ipBinario, $inicioBinario) < 0 ||
            strcmp($ipBinario, $fimBinario) > 0
        ) {
            throw new UnexpectedValueException(
                'O IP consultado não pertence à rede RDAP.'
            );
        }

        return $resultado;
    }

    private function textoOpcional(
        array $dados,
        string $campo
    ): ?string {
        $valor = $dados[$campo] ?? null;

        if ($valor === null) {
            return null;
        }

        if (! is_string($valor)) {
            throw new UnexpectedValueException(
                'Campo RDAP inválido: ' . $campo
            );
        }

        return $valor;
    }
}
