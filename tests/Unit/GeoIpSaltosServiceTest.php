<?php

namespace Tests\Unit;

use App\Services\GeoIpSaltosService;
use App\Services\GeoIpService;
use Mockery;
use Tests\TestCase;

class GeoIpSaltosServiceTest extends TestCase
{
    public function test_enriquece_salto_publico(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);

        $geoIp->shouldReceive('localizar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([
                'ip' => '1.1.1.1',
                'pais' => 'Australia',
                'cidade' => 'Sydney',
                'latitude' => -33.8688,
                'longitude' => 151.2093,
            ]);

        $servico = new GeoIpSaltosService($geoIp);

        $resultado = $servico->enriquecer([
            [
                'salto' => 1,
                'ip' => '1.1.1.1',
                'latencia_ms' => 20.5,
            ],
        ]);

        $this->assertSame(1, $resultado[0]['salto']);
        $this->assertSame(20.5, $resultado[0]['latencia_ms']);
        $this->assertSame('Australia', $resultado[0]['geo']['pais']);
        $this->assertSame(-33.8688, $resultado[0]['geo']['latitude']);
    }

    public function test_ignora_salto_sem_resposta(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);
        $geoIp->shouldNotReceive('localizar');

        $servico = new GeoIpSaltosService($geoIp);

        $resultado = $servico->enriquecer([
            [
                'salto' => 2,
                'ip' => null,
                'latencia_ms' => null,
            ],
        ]);

        $this->assertNull($resultado[0]['ip']);
        $this->assertNull($resultado[0]['geo']);
    }

    public function test_ignora_ip_privado(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);
        $geoIp->shouldNotReceive('localizar');

        $servico = new GeoIpSaltosService($geoIp);

        $resultado = $servico->enriquecer([
            [
                'salto' => 1,
                'ip' => '192.168.1.1',
                'media_ms' => 5.2,
            ],
        ]);

        $this->assertNull($resultado[0]['geo']);
        $this->assertSame(5.2, $resultado[0]['media_ms']);
    }

    public function test_mantem_salto_sem_localizacao(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);

        $geoIp->shouldReceive('localizar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn(null);

        $servico = new GeoIpSaltosService($geoIp);

        $resultado = $servico->enriquecer([
            [
                'salto' => 3,
                'ip' => '1.1.1.1',
                'perda_pct' => 0.0,
            ],
        ]);

        $this->assertNull($resultado[0]['geo']);
        $this->assertSame(0.0, $resultado[0]['perda_pct']);
    }

    public function test_reutiliza_localizacao_de_ip_repetido(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);

        $geoIp->shouldReceive('localizar')
            ->once()
            ->with('1.1.1.1')
            ->andReturn([
                'ip' => '1.1.1.1',
                'pais' => 'Australia',
                'latitude' => -33.8688,
                'longitude' => 151.2093,
            ]);

        $servico = new GeoIpSaltosService($geoIp);

        $resultado = $servico->enriquecer([
            ['salto' => 1, 'ip' => '1.1.1.1'],
            ['salto' => 2, 'ip' => '1.1.1.1'],
        ]);

        $this->assertSame(
            $resultado[0]['geo'],
            $resultado[1]['geo']
        );
    }

    public function test_ignora_ipv4_de_documentacao(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);
        $geoIp->shouldNotReceive('localizar');

        $resultado = (new GeoIpSaltosService($geoIp))->enriquecer([
            ['salto' => 1, 'ip' => '203.0.113.10'],
        ]);

        $this->assertNull($resultado[0]['geo']);
    }

    public function test_ignora_ipv6_local(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);
        $geoIp->shouldNotReceive('localizar');

        $resultado = (new GeoIpSaltosService($geoIp))->enriquecer([
            ['salto' => 1, 'ip' => 'fe80::1'],
        ]);

        $this->assertNull($resultado[0]['geo']);
    }

    public function test_ignora_entrada_malformada(): void
    {
        $geoIp = Mockery::mock(GeoIpService::class);
        $geoIp->shouldNotReceive('localizar');

        $resultado = (new GeoIpSaltosService($geoIp))->enriquecer([
            ['salto' => 1, 'ip' => '1.1.1.1;whoami'],
        ]);

        $this->assertNull($resultado[0]['geo']);
    }

}
