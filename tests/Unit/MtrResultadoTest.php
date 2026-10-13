<?php

namespace Tests\Unit;

use App\Services\MtrService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MtrResultadoTest extends TestCase
{
    public function test_interpreta_resultado_json(): void
    {
        $json = json_encode([
            'report' => [
                'hubs' => [
                    [
                        'count' => 1,
                        'host' => '192.168.1.1',
                        'Loss%' => 0.0,
                        'Snt' => 3,
                        'Last' => 2.1,
                        'Avg' => 2.0,
                        'Best' => 1.8,
                        'Wrst' => 2.1,
                    ],
                    [
                        'count' => 2,
                        'host' => '1.1.1.1',
                        'Loss%' => 0.0,
                        'Snt' => 3,
                        'Last' => 25.0,
                        'Avg' => 24.5,
                        'Best' => 23.0,
                        'Wrst' => 25.0,
                    ],
                ],
            ],
        ]);

        $resultado = (new MtrService())->interpretarResultado($json);

        $this->assertCount(2, $resultado);
        $this->assertSame(1, $resultado[0]['salto']);
        $this->assertSame('192.168.1.1', $resultado[0]['ip']);
        $this->assertSame(2.0, $resultado[0]['media_ms']);
        $this->assertSame(24.5, $resultado[1]['media_ms']);
        $this->assertSame(3, $resultado[1]['enviados']);
    }

    public function test_interpreta_salto_sem_resposta(): void
    {
        $json = json_encode([
            'report' => [
                'hubs' => [
                    [
                        'count' => 1,
                        'host' => '???',
                        'Loss%' => 100.0,
                        'Snt' => 3,
                        'Last' => 0.0,
                        'Avg' => 0.0,
                        'Best' => 0.0,
                        'Wrst' => 0.0,
                    ],
                ],
            ],
        ]);

        $resultado = (new MtrService())->interpretarResultado($json);

        $this->assertNull($resultado[0]['ip']);
        $this->assertSame(100.0, $resultado[0]['perda_pct']);
        $this->assertNull($resultado[0]['media_ms']);
    }

    public function test_rejeita_json_invalido(): void
    {
        $this->expectException(RuntimeException::class);

        (new MtrService())->interpretarResultado('json invalido');
    }

    public function test_rejeita_resultado_sem_hubs(): void
    {
        $this->expectException(RuntimeException::class);

        (new MtrService())->interpretarResultado('{"report":{}}');
    }
}
