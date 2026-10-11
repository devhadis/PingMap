<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_usuario_pode_se_cadastrar(): void
    {
        $this->post('/cadastro', [
            'name' => 'Usuario Teste',
            'email' => 'teste@example.com',
            'password' => 'SenhaSegura123!',
            'password_confirmation' => 'SenhaSegura123!',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();

        $usuario = User::where(
            'email', 'teste@example.com'
        )->firstOrFail();

        $this->assertTrue(
            Hash::check('SenhaSegura123!', $usuario->password)
        );
    }

    public function test_usuario_pode_fazer_login(): void
    {
        $usuario = User::factory()->create([
            'password' => Hash::make('SenhaSegura123!'),
        ]);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'SenhaSegura123!',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_usuario_pode_sair(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_senha_incorreta_nao_autentica(): void
    {
        $usuario = User::factory()->create();

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'senha-incorreta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
