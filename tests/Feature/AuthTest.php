<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Garante que um novo cadastro autentica o usuario e cria sua carteira.
     */
    public function test_user_can_register_and_receives_wallet(): void
    {
        $response = $this->post('/register', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('wallets', [
            'user_id' => User::query()->firstOrFail()->id,
            'opt_active' => 1,
            'opt_deleted' => 0,
        ]);
    }

    /**
     * Garante que o login continua funcionando quando o usuario informa o e-mail.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'Password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Garante que o login tambem aceita o numero da conta formatado.
     */
    public function test_user_can_login_with_account_number(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $response = $this->post('/login', [
            'login' => $user->wallet->formatted_account_number,
            'password' => 'Password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Garante que uma conta marcada como deletada nao consegue mais iniciar sessao.
     */
    public function test_deleted_account_cannot_login_even_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $user->wallet->update([
            'opt_active' => false,
            'opt_deleted' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'Password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    /**
     * Garante que nao e possivel cadastrar outra conta com o mesmo e-mail de uma conta excluida.
     */
    public function test_cannot_register_with_email_of_deleted_account(): void
    {
        $user = User::factory()->create([
            'email' => 'maria@example.com',
        ]);

        $user->wallet->update([
            'opt_active' => false,
            'opt_deleted' => true,
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Maria Nova',
            'email' => 'maria@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email']);
    }
}