<?php

namespace App\Jobs;

use App\Models\Diagnostico;
use App\Services\PingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExecutarDiagnostico implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $diagnosticoId
    ) {}

    public function handle(PingService $ping): void
    {
        $diagnostico = Diagnostico::findOrFail(
            $this->diagnosticoId
        );

        if ($diagnostico->status !== 'pendente') {
            return;
        }

        $diagnostico->update([
            'status' => 'executando',
        ]);

        try {
            $resultado = $ping->executar(
                $diagnostico->destino
            );

            $diagnostico->update([
                'status' => $resultado['status'],
                'perda_percentual' => $resultado['perda_percentual'],
                'latencia_media_ms' => $resultado['latencia_media_ms'],
                'erro' => null,
            ]);
        } catch (Throwable $e) {
            $diagnostico->update([
                'status' => 'erro',
                'erro' => 'Falha ao executar o diagnóstico.',
            ]);

            report($e);

            throw $e;
        }
    }
}
