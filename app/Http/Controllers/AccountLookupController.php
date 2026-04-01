<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountLookupController extends Controller
{
    /**
     * Consulta uma conta pelo número informado e devolve os dados do titular em JSON.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account' => ['required', 'integer', 'min:1'],
        ]);

        $wallet = Wallet::query()
            ->with('user')
            ->find($validated['account']);

        if (! $wallet) {
            return response()->json([
                'found' => false,
                'message' => 'Conta nao encontrada.',
            ]);
        }

        if ($wallet->opt_deleted) {
            return response()->json([
                'found' => false,
                'message' => 'A conta informada esta deletada e nao pode receber transferencias.',
            ]);
        }

        if (! $wallet->opt_active) {
            return response()->json([
                'found' => false,
                'message' => 'A conta informada esta inativa e nao pode receber transferencias.',
            ]);
        }

        return response()->json([
            'found' => true,
            'account_number' => $wallet->formatted_account_number,
            'holder_name' => $wallet->user->name,
            'holder_email' => $wallet->user->email,
            'is_own_account' => $wallet->user_id === $request->user()->id,
        ]);
    }
}