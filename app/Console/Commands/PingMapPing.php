<?php

namespace App\Console\Commands;

use App\Services\PingService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class PingMapPing extends Command
{
    protected $signature = 'pingmap:ping {destino : Endereço IP público}';

    protected $description = 'Executa diagnóstico ICMP em um IP público';

    public function handle(PingService $ping): int
    {
        try {
            $resultado = $ping->executar(
                $this->argument('destino')
            );

            $this->line(json_encode(
                $resultado,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            ));

            return $resultado['status'] === 'sucesso'
                ? self::SUCCESS
                : self::FAILURE;

        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;

        } catch (Throwable $e) {
            $this->error('Falha ao executar o diagnóstico.');

            return self::FAILURE;
        }
    }
}
