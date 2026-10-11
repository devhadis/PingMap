<?php

namespace App\Jobs;

use App\Models\DnsConsulta;
use App\Services\DnsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExecutarConsultaDns implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 25;

    public function __construct(
        public int $consultaId
    ) {}

    public function handle(DnsService $dns): void
    {
        $consulta = DnsConsulta::find($this->consultaId);

        if (!$consulta || $consulta->status !== 'pendente') {
            return;
        }

        $consulta->update(['status' => 'executando']);

        try {
            $resultado = $dns->consultar(
                $consulta->dominio,
                $consulta->tipo
            );

            $consulta->update([
                'status' => $resultado['status'],
                'quantidade' => $resultado['quantidade'],
                'registros' => $resultado['registros'],
                'erro' => null,
            ]);
        } catch (Throwable $e) {
            $consulta->update([
                'status' => 'erro',
                'erro' => 'Falha ao executar consulta DNS.',
            ]);

            throw $e;
        }
    }
}
