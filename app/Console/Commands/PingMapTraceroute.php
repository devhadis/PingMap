<?php

namespace App\Console\Commands;

use App\Services\TracerouteService;
use Illuminate\Console\Command;
use Throwable;

class PingMapTraceroute extends Command
{
    protected $signature = 'pingmap:traceroute {destino : Endereço IPv4 ou IPv6 público}';

    protected $description = 'Executa traceroute seguro para um endereço IPv4 ou IPv6 público';

    public function handle(TracerouteService $servico): int
    {
        $destino = (string) $this->argument('destino');

        try {
            $destino = $servico->validarDestino($destino);

            $this->info("Traceroute para {$destino}");

            $saltos = $servico->executar($destino);

            if (empty($saltos)) {
                $this->error('Nenhum salto foi retornado.');
                return self::FAILURE;
            }

            $linhas = [];

            foreach ($saltos as $salto) {
                $ip = $salto['ip'] ?? null;
                $latencia = $salto['latencia_ms'] ?? null;

                $linhas[] = [
                    $salto['salto'] ?? '?',
                    $ip ?: '*',
                    $latencia === null
                        ? 'Sem resposta'
                        : number_format((float) $latencia, 3, '.', '') . ' ms',
                ];
            }

            $this->table(
                ['Salto', 'IP', 'Latência'],
                $linhas
            );

            $ultimo = end($saltos);

            if (($ultimo['ip'] ?? null) === $destino) {
                $this->info('Destino alcançado.');
            } else {
                $this->warn('Destino não confirmado nos saltos retornados.');
            }

            return self::SUCCESS;
        } catch (Throwable $erro) {
            $this->error('Não foi possível executar o Traceroute.');

            return self::FAILURE;
        }
    }
}
