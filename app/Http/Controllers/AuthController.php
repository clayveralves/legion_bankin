<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Exibe a tela de cadastro de nova conta.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Cria o usuário, gera a conta automaticamente e inicia a sessão.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create($request->safe()->only(['name', 'email', 'password']));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Cadastro realizado com sucesso.');
    }

    /**
     * Exibe a tela de login.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Autentica o usuário por e-mail ou número da conta.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => $this->resolveLoginEmail($request->validated('login')),
            'password' => $request->validated('password'),
        ];

        if (! $credentials['email'] || ! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['login' => 'Credenciais inválidas.'])
                ->onlyInput('login');
        }

        $wallet = $request->user()->wallet;

        if ($wallet && $wallet->opt_deleted) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['login' => 'Esta conta foi marcada como excluída. Solicite a um administrador a reativação para voltar a acessá-la.'])
                ->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Sessão iniciada com sucesso.');
    }

    /**
     * Converte o identificador digitado no login para o e-mail do usuário.
     */
    private function resolveLoginEmail(string $login): ?string
    {
        $normalizedLogin = trim($login);

        if (filter_var($normalizedLogin, FILTER_VALIDATE_EMAIL)) {
            return $normalizedLogin;
        }

        if (! preg_match('/^\d+$/', $normalizedLogin)) {
            return null;
        }

        $accountId = (int) ltrim($normalizedLogin, '0');

        if ($accountId < 1) {
            return null;
        }

        return Wallet::query()
            ->with('user:id,email')
            ->find($accountId)
            ?->user
            ?->email;
    }

    /**
     * Encerra a sessão atual com limpeza segura dos dados de autenticação.
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Sessão encerrada.');
    }
}