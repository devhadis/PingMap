<?php

namespace App\Jobs;

use App\Models\TcpConsulta;
use App\Services\TcpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExecutarConsultaTcp implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 15;

    public function __construct(
        public int $consultaId
    ) {}

    public function handle(TcpService $tcp): void
    {
        $consulta = TcpConsulta::find($this->consultaId);

        if (!$consulta || $consulta->status !== 'pendente') {
            return;
        }

        $consulta->update([
            'status' => 'executando',
        ]);

        try {
            $resultado = $tcp->verificar(
                $consulta->destino,
                (int) $consulta->porta
            );

            $consulta->update([
                'status' => $resultado['status'],
                'tempo_ms' => $resultado['tempo_ms'],
                'erro' => null,
            ]);
        } catch (Throwable $e) {
            $consulta->update([
                'status' => 'erro',
                'tempo_ms' => null,
                'erro' => 'Falha ao executar consulta TCP.',
            ]);

            throw $e;
        }
    }
}
