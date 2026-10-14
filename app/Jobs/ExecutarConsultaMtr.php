<?php

namespace App\Jobs;

use App\Models\MtrConsulta;
use App\Services\MtrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExecutarConsultaMtr implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 40;

    public function __construct(
        public int $consultaId
    ) {}

    public function handle(MtrService $servico): void
    {
        // Reserva a consulta de forma atomica.
        $reservadas = MtrConsulta::query()
            ->whereKey($this->consultaId)
            ->where('status', 'pendente')
            ->update([
                'status' => 'executando',
            ]);

        // Impede processamento duplicado.
        if ($reservadas !== 1) {
            return;
        }

        $consulta = MtrConsulta::findOrFail(
            $this->consultaId
        );

        try {
            $saltos = $servico->executar($consulta->destino);

            $consulta->update([
                'status' => 'concluido',
                'saltos' => $saltos,
                'erro' => null,
            ]);
        } catch (Throwable $e) {
            $consulta->update([
                'status' => 'erro',
                'saltos' => null,
                'erro' => 'Falha ao executar MTR.',
            ]);

            throw $e;
        }
    }
}
