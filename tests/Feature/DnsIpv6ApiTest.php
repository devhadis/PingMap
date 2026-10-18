<?php

namespace Tests\Feature;

use App\Jobs\ExecutarConsultaDns;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DnsIpv6ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_usuario_solicita_ptr_ipv6(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/dns/consultas', [
                'dominio' => '2606:4700:4700::1111',
                'tipo' => 'PTR',
            ])
            ->assertStatus(202)
            ->assertJsonPath('tipo', 'PTR')
            ->assertJsonPath(
                'dominio',
                '2606:4700:4700::1111'
            );

        Queue::assertPushed(ExecutarConsultaDns::class, 1);
    }

    public function test_usuario_solicita_aaaa(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/dns/consultas', [
                'dominio' => 'cloudflare.com',
                'tipo' => 'AAAA',
            ])
            ->assertStatus(202)
            ->assertJsonPath('tipo', 'AAAA');

        Queue::assertPushed(ExecutarConsultaDns::class, 1);
    }

    public function test_api_rejeita_ptr_ipv6_reservado(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/dns/consultas', [
                'dominio' => '2001:db8::1',
                'tipo' => 'PTR',
            ])
            ->assertStatus(422);

        Queue::assertNotPushed(ExecutarConsultaDns::class);
    }
}
