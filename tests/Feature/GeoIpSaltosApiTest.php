<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoIpSaltosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_geo_traceroute(): void
    {
        $this->getJson(
            '/api/v1/traceroute/consultas/1/geo'
        )->assertUnauthorized();
    }

    public function test_visitante_nao_acessa_geo_mtr(): void
    {
        $this->getJson(
            '/api/v1/mtr/consultas/1/geo'
        )->assertUnauthorized();
    }

    public function test_usuario_nao_acessa_geo_traceroute_de_outro(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();

        $consulta = $dono->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $this->actingAs($outro)->getJson(
            "/api/v1/traceroute/consultas/{$consulta->id}/geo"
        )->assertNotFound();
    }

    public function test_usuario_nao_acessa_geo_mtr_de_outro(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();

        $consulta = $dono->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $this->actingAs($outro)->getJson(
            "/api/v1/mtr/consultas/{$consulta->id}/geo"
        )->assertNotFound();
    }

    public function test_geo_traceroute_exige_diagnostico_concluido(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $this->actingAs($usuario)->getJson(
            "/api/v1/traceroute/consultas/{$consulta->id}/geo"
        )->assertStatus(409);
    }

    public function test_geo_mtr_exige_diagnostico_concluido(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'pendente',
        ]);

        $this->actingAs($usuario)->getJson(
            "/api/v1/mtr/consultas/{$consulta->id}/geo"
        )->assertStatus(409);
    }

    public function test_proprietario_acessa_geo_traceroute_concluido(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->tracerouteConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $this->actingAs($usuario)->getJson(
            "/api/v1/traceroute/consultas/{$consulta->id}/geo"
        )->assertStatus(202)->assertJsonPath('status', 'pendente');
    }

    public function test_proprietario_acessa_geo_mtr_concluido(): void
    {
        $usuario = User::factory()->create();

        $consulta = $usuario->mtrConsultas()->create([
            'destino' => '1.1.1.1',
            'status' => 'concluido',
            'saltos' => [
                ['salto' => 1, 'ip' => '1.1.1.1'],
            ],
        ]);

        $this->actingAs($usuario)->getJson(
            "/api/v1/mtr/consultas/{$consulta->id}/geo"
        )->assertStatus(202)->assertJsonPath('status', 'pendente');
    }

}
