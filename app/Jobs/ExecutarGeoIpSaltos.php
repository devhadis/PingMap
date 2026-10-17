<?php

namespace App\Jobs;

use App\Models\MtrConsulta;
use App\Models\TracerouteConsulta;
use App\Services\GeoIpSaltosService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ExecutarGeoIpSaltos implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 90;
    public bool $failOnTimeout = true;

    public function __construct(
        public readonly string $tipo,
        public readonly int $consultaId,
        public readonly int $usuarioId,
        public readonly ?string $tentativa = null
    ) {
    }

    public function handle(GeoIpSaltosService $servico): void
    {
        $chave = self::chave(
            $this->tipo,
            $this->consultaId,
            $this->usuarioId
        );

        if (! $this->autorizado($chave)) {
            return;
        }

        try {
            $consulta = match ($this->tipo) {
                'traceroute' => TracerouteConsulta::find($this->consultaId),
                'mtr' => MtrConsulta::find($this->consultaId),
                default => null,
            };

            if (
                ! $consulta
                || (int) $consulta->user_id !== $this->usuarioId
                || $consulta->status !== 'concluido'
            ) {
                $this->removerSeAutorizado($chave);
                return;
            }

            $saltos = $servico->enriquecer(
                is_array($consulta->saltos) ? $consulta->saltos : []
            );

            $this->gravarSeAutorizado(
                $chave,
                [
                    'status' => 'concluido',
                    'saltos' => $saltos,
                ],
                now()->addHours(6)
            );
        } catch (Throwable $erro) {
            $this->registrarErro($chave);
            throw $erro;
        }
    }

    public function failed(?Throwable $erro): void
    {
        $this->registrarErro(
            self::chave(
                $this->tipo,
                $this->consultaId,
                $this->usuarioId
            )
        );
    }

    private function registrarErro(string $chave): void
    {
        $this->gravarSeAutorizado(
            $chave,
            [
                'status' => 'erro',
                'message' => 'Não foi possível processar a geolocalização.',
            ],
            now()->addMinutes(2)
        );
    }

    private function autorizado(string $chave): bool
    {
        return Cache::lock($chave . ':lock', 10)->block(
            5,
            fn () => $this->podeAlterar(Cache::get($chave))
        );
    }

    private function gravarSeAutorizado(
        string $chave,
        array $resultado,
        mixed $expiracao
    ): void {
        Cache::lock($chave . ':lock', 10)->block(
            5,
            function () use ($chave, $resultado, $expiracao): void {
                if (! $this->podeAlterar(Cache::get($chave))) {
                    return;
                }

                Cache::put($chave, $resultado, $expiracao);
            }
        );
    }

    private function removerSeAutorizado(string $chave): void
    {
        Cache::lock($chave . ':lock', 10)->block(
            5,
            function () use ($chave): void {
                if ($this->podeAlterar(Cache::get($chave))) {
                    Cache::forget($chave);
                }
            }
        );
    }

    private function podeAlterar(mixed $atual): bool
    {
        if ($this->tentativa === null || $this->tentativa === '') {
            return false;
        }

        return is_array($atual)
            && ($atual['status'] ?? null) === 'pendente'
            && ($atual['tentativa'] ?? null) === $this->tentativa;
    }

    public static function chave(
        string $tipo,
        int $consultaId,
        int $usuarioId
    ): string {
        return "pingmap:geo-saltos:v1:{$usuarioId}:{$tipo}:{$consultaId}";
    }
}
