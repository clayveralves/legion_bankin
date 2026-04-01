<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidOperationReversalException;
use App\Exceptions\RecipientUnavailableException;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\ReverseOperationRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Operation;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;

class TransactionController extends Controller
{
    /**
     * Injeta o serviço responsável pelas regras financeiras da carteira.
     */
    public function __construct(private readonly WalletService $walletService)
    {
    }

    /**
     * Registra um depósito na conta autenticada.
     */
    public function deposit(DepositRequest $request): RedirectResponse
    {
        $this->walletService->deposit(
            $request->user(),
            (string) $request->validated('amount'),
            $request->validated('description'),
        );

        return back()->with('status', 'Depósito realizado com sucesso.');
    }

    /**
     * Executa uma transferência para amigo ou para conta informada.
     */
    public function transfer(TransferRequest $request): RedirectResponse
    {
        if ($request->validated('recipient_type') === 'friend') {
            $recipient = User::query()->findOrFail($request->integer('recipient_friend_id'));
        } else {
            $recipientWallet = Wallet::query()
                ->with('user')
                ->findOrFail($request->integer('recipient_account'));

            $recipient = $recipientWallet->user;
        }

        try {
            $this->walletService->transfer(
                $request->user(),
                $recipient,
                (string) $request->validated('amount'),
                $request->validated('description'),
            );
        } catch (InsufficientFundsException | RecipientUnavailableException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Transferência realizada com sucesso.');
    }

    /**
     * Reverte uma operação válida para um dos participantes.
     */
    public function reverse(ReverseOperationRequest $request, Operation $operation): RedirectResponse
    {
        try {
            $this->walletService->reverse(
                $operation,
                $request->user(),
                $request->validated('reason'),
            );
        } catch (InvalidOperationReversalException $exception) {
            return back()->withErrors(['reversal' => $exception->getMessage()]);
        }

        return back()->with('status', 'Operação revertida com sucesso.');
    }
}