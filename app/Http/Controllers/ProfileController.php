<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Atualiza os dados editaveis do perfil autenticado mediante confirmacao por senha.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $payload = $request->safe()->only(['name', 'email']);
        $wallet = $user->wallet;
        $accountStatus = $request->validated('account_status');
        $profileUpdated = false;
        $accountStatusUpdated = false;

        if ($request->filled('password')) {
            $payload['password'] = $request->string('password')->toString();
        }

        if ($user->name !== ($payload['name'] ?? $user->name) || $user->email !== ($payload['email'] ?? $user->email) || array_key_exists('password', $payload)) {
            $user->update($payload);
            $profileUpdated = true;
        }

        $nextAccountActive = $accountStatus === 'active';

        if ($wallet->opt_active !== $nextAccountActive) {
            $wallet->update([
                'opt_active' => $nextAccountActive,
            ]);

            $accountStatusUpdated = true;
        }

        $statusMessage = 'Perfil atualizado com sucesso.';

        if ($profileUpdated && $accountStatusUpdated) {
            $statusMessage = 'Perfil e status da conta atualizados com sucesso.';
        } elseif ($accountStatusUpdated) {
            $statusMessage = $nextAccountActive
                ? 'Conta ativada com sucesso.'
                : 'Conta inativada com sucesso.';
        }

        return redirect()
            ->route('dashboard')
            ->with('status', $statusMessage);
    }

    /**
     * Marca a conta como deletada sem remover os registros fisicamente.
     */
    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if ((float) $wallet->balance > 0) {
            return redirect()
                ->route('dashboard', ['open_profile' => 1])
                ->withErrors(['delete_account' => 'É necessário retirar todo o saldo da conta antes de solicitar a exclusão.'])
                ->withInput(['delete_form' => '1']);
        }

        $wallet->update([
            'opt_active' => false,
            'opt_deleted' => true,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', 'Conta marcada como excluída com sucesso. Para uma futura reativação, solicite apoio de um administrador do sistema.');
    }
}