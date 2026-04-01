<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Bloqueia ações mutáveis quando a conta estiver inativa ou marcada como deletada.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $wallet = $request->user()?->wallet;

        if (! $wallet || ! $wallet->opt_active || $wallet->opt_deleted) {
            return redirect()
                ->route('dashboard', ['open_profile' => 1])
                ->withErrors(['account' => 'Sua conta está inativa. Reative-a no perfil para voltar a movimentá-la.']);
        }

        return $next($request);
    }
}