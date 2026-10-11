<?php

namespace App\Console\Commands;

use App\Services\TcpService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class PingMapTcp extends Command
{
    protected $signature = 'pingmap:tcp
        {destino : Endereço IPv4 público}
        {porta : Porta TCP autorizada}';

    protected $description = 'Verifica conectividade TCP com IP público';

    public function handle(TcpService $tcp): int
    {
        $destino = (string) $this->argument('destino');
        $portaTexto = (string) $this->argument('porta');

        if (!ctype_digit($portaTexto)) {
            $this->error('A porta deve ser um número inteiro.');

            return self::FAILURE;
        }

        $porta = (int) $portaTexto;

        try {
            $resultado = $tcp->verificar($destino, $porta);

            $this->line(json_encode(
                $resultado,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            ));

            return $resultado['status'] === 'conectado'
                ? self::SUCCESS
                : self::FAILURE;

        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;

        } catch (Throwable $e) {
            $this->error('Falha ao verificar conexão TCP.');

            return self::FAILURE;
        }
    }
}
