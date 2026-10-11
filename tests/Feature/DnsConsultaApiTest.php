<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaDns;
use App\Models\DnsConsulta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DnsConsultaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_cria_consulta(): void
    {
        $this->postJson('/api/v1/dns/consultas', [
            'dominio' => 'cloudflare.com',
            'tipo' => 'A',
        ])->assertUnauthorized();
    }

    public function test_usuario_cria_consulta_dns(): void
    {
        Queue::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/api/v1/dns/consultas', [
                'dominio' => 'CLOUDFLARE.COM',
                'tipo' => 'a',
            ])
            ->assertStatus(202)
            ->assertJsonPath('dominio', 'cloudflare.com')
            ->assertJsonPath('tipo', 'A')
            ->assertJsonPath('status', 'pendente');

        $consulta = DnsConsulta::firstOrFail();

        $this->assertSame($usuario->id, $consulta->user_id);

        Queue::assertPushed(ExecutarConsultaDns::class, 1);
    }

    public function test_rejeita_ptr_para_ip_privado(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/dns/consultas', [
                'dominio' => '127.0.0.1',
                'tipo' => 'PTR',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('dns_consultas', 0);
        Queue::assertNothingPushed();
    }

    public function test_usuario_nao_consulta_resultado_de_outro(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();

        $consulta = DnsConsulta::create([
            'user_id' => $dono->id,
            'dominio' => 'cloudflare.com',
            'tipo' => 'A',
            'status' => 'sucesso',
            'quantidade' => 1,
            'registros' => [['ip' => '1.1.1.1']],
        ]);

        $this->actingAs($outro)
            ->getJson('/api/v1/dns/consultas/' . $consulta->id)
            ->assertNotFound();

        $this->actingAs($dono)
            ->getJson('/api/v1/dns/consultas/' . $consulta->id)
            ->assertOk()
            ->assertJsonPath('registros.0.ip', '1.1.1.1');
    }

    public function test_historico_mostra_apenas_consultas_do_usuario(): void
    {
        $usuario = User::factory()->create();
        $outro = User::factory()->create();

        foreach ([
            [$usuario->id, 'cloudflare.com'],
            [$outro->id, 'google.com'],
        ] as [$userId, $dominio]) {
            DnsConsulta::create([
                'user_id' => $userId,
                'dominio' => $dominio,
                'tipo' => 'A',
                'status' => 'pendente',
            ]);
        }

        $this->actingAs($usuario)
            ->getJson('/api/v1/dns/consultas')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.dominio', 'cloudflare.com')
            ->assertDontSee('google.com');
    }
}
