<?php

namespace App\Console\Commands;

use App\Services\MtrService;
use Illuminate\Console\Command;
use Throwable;

class PingMapMtr extends Command
{
    protected $signature = 'pingmap:mtr {destino : Endereço IPv4 ou IPv6 público}';

    protected $description = 'Executa MTR seguro para um IPv4 ou IPv6 público';

    public function handle(MtrService $servico): int
    {
        $destino = (string) $this->argument('destino');

        try {
            $destino = $servico->validarDestino($destino);

            $this->info("MTR para {$destino}");

            $saltos = $servico->executar($destino);

            if (empty($saltos)) {
                $this->error('Nenhum salto foi retornado.');

                return self::FAILURE;
            }

            $linhas = [];

            foreach ($saltos as $salto) {
                $media = $salto['media_ms'] ?? null;
                $melhor = $salto['melhor_ms'] ?? null;
                $pior = $salto['pior_ms'] ?? null;

                $linhas[] = [
                    $salto['salto'] ?? '?',
                    $salto['ip'] ?? '*',
                    $salto['enviados'] ?? 0,
                    number_format(
                        (float) ($salto['perda_pct'] ?? 0),
                        2,
                        '.',
                        ''
                    ) . '%',
                    $media === null
                        ? '-'
                        : number_format((float) $media, 3) . ' ms',
                    $melhor === null
                        ? '-'
                        : number_format((float) $melhor, 3) . ' ms',
                    $pior === null
                        ? '-'
                        : number_format((float) $pior, 3) . ' ms',
                ];
            }

            $this->table(
                [
                    'Salto',
                    'IP',
                    'Enviados',
                    'Sem resposta',
                    'Média',
                    'Melhor',
                    'Pior',
                ],
                $linhas
            );

            $destinoAlcancado = false;

            foreach ($saltos as $salto) {
                if (($salto['ip'] ?? null) === $destino) {
                    $destinoAlcancado = true;
                    break;
                }
            }

            if ($destinoAlcancado) {
                $this->info('Destino alcançado.');
            } else {
                $this->warn('Destino não confirmado.');
            }

            $this->warn(
                'Ausência de resposta intermediária não comprova perda até o destino.'
            );

            return self::SUCCESS;
        } catch (Throwable $erro) {
            $this->error('Não foi possível executar o MTR.');

            return self::FAILURE;
        }
    }
}
