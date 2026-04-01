<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Garante que o usuario consegue atualizar o perfil ao confirmar com a senha atual.
     */
    public function test_user_can_update_profile_with_current_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Maria Oliveira',
            'email' => 'maria.oliveira@example.com',
            'password' => 'NovaSenha123',
            'password_confirmation' => 'NovaSenha123',
            'account_status' => 'active',
            'current_password' => 'Password123',
            'profile_form' => '1',
            'profile_edit_info' => '1',
            'profile_edit_password' => '1',
            'profile_edit_status' => '0',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('status', 'Perfil atualizado com sucesso.');

        $user->refresh();

        $this->assertSame('Maria Oliveira', $user->name);
        $this->assertSame('maria.oliveira@example.com', $user->email);
        $this->assertTrue(Hash::check('NovaSenha123', $user->password));
    }

    /**
     * Garante que a edicao do perfil falha quando a senha de confirmacao esta incorreta.
     */
    public function test_user_cannot_update_profile_with_invalid_current_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->from('/dashboard')->actingAs($user)->patch('/profile', [
            'name' => 'Maria Oliveira',
            'email' => 'maria.oliveira@example.com',
            'account_status' => 'active',
            'current_password' => 'SenhaInvalida123',
            'profile_form' => '1',
            'profile_edit_info' => '1',
            'profile_edit_password' => '0',
            'profile_edit_status' => '0',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors(['current_password']);

        $user->refresh();

        $this->assertSame('Maria Silva', $user->name);
        $this->assertSame('maria@example.com', $user->email);
    }

    /**
     * Garante que o usuario pode inativar a propria conta confirmando com a senha atual.
     */
    public function test_user_can_inactivate_account_with_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'account_status' => 'inactive',
            'current_password' => 'Password123',
            'profile_form' => '1',
            'profile_edit_info' => '0',
            'profile_edit_password' => '0',
            'profile_edit_status' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('status', 'Conta inativada com sucesso.');

        $user->refresh();

        $this->assertFalse($user->wallet->opt_active);
        $this->assertFalse($user->wallet->opt_deleted);
    }

    /**
     * Garante que uma conta inativa nao consegue executar operacoes bloqueadas.
     */
    public function test_inactive_account_cannot_make_deposit(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $user->wallet->update([
            'opt_active' => false,
        ]);

        $response = $this->actingAs($user)->post('/deposit', [
            'amount' => '10.00',
            'description' => 'Teste',
        ]);

        $response->assertRedirect('/dashboard?open_profile=1');
        $response->assertSessionHasErrors(['account']);
    }

    /**
     * Garante que a exclusao logica marca a conta como deletada e inativa sem remover os registros.
     */
    public function test_user_can_mark_account_as_deleted_when_balance_is_zero(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $user->wallet->update([
            'balance' => 0,
            'opt_active' => true,
            'opt_deleted' => false,
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'delete_form' => '1',
            'delete_current_password' => 'Password123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('status');
        $this->assertGuest();

        $user->refresh();

        $this->assertFalse($user->wallet->opt_active);
        $this->assertTrue($user->wallet->opt_deleted);
    }

    /**
     * Garante que a exclusao logica nao pode acontecer enquanto houver saldo na conta.
     */
    public function test_user_cannot_mark_account_as_deleted_with_positive_balance(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $user->wallet->update([
            'balance' => 50.00,
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'delete_form' => '1',
            'delete_current_password' => 'Password123',
        ]);

        $response->assertRedirect('/dashboard?open_profile=1');
        $response->assertSessionHasErrors(['delete_account']);

        $user->refresh();

        $this->assertTrue($user->wallet->opt_active);
        $this->assertFalse($user->wallet->opt_deleted);
    }
}