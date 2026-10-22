<?php

namespace Tests\Unit;

use App\Services\DnssecValidadorService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class DnssecFalhasProcessoTest extends TestCase
{
    public function test_timeout_retorna_indeterminado(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            function (array $comando): array {
                $processo = new Process($comando);

                throw new ProcessTimedOutException(
                    $processo,
                    ProcessTimedOutException::TYPE_GENERAL
                );
            }
        );

        $this->assertSame('indeterminado', $resultado);
    }

    public function test_falha_operacional_retorna_indeterminado(): void
    {
        $servico = new DnssecValidadorService();

        $resultado = $servico->executar(
            'cloudflare.com',
            function (array $comando): array {
                throw new RuntimeException(
                    'Falha ao iniciar o processo.'
                );
            }
        );

        $this->assertSame('indeterminado', $resultado);
    }
}
