<?php

namespace App\Jobs;

use App\Models\TracerouteConsulta;
use App\Services\TracerouteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExecutarConsultaTraceroute implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public int $consultaId
    ) {}

    public function handle(TracerouteService $servico): void
    {
        $consulta = TracerouteConsulta::find($this->consultaId);

        if (!$consulta || $consulta->status !== 'pendente') {
            return;
        }

        $consulta->update([
            'status' => 'executando',
        ]);

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
                'erro' => 'Falha ao executar Traceroute.',
            ]);

            throw $e;
        }
    }
}
