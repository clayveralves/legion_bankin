<?php

namespace App\Services;

use App\Enums\EntryDirection;
use App\Enums\OperationStatus;
use App\Enums\OperationType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidOperationReversalException;
use App\Exceptions\RecipientUnavailableException;
use App\Models\LedgerEntry;
use App\Models\Operation;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Registra um depósito creditando o valor na carteira do usuário.
     */
    public function deposit(User $user, string $amount, ?string $description = null): Operation
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $wallet = $this->lockWalletForUser($user);

            $operation = Operation::query()->create([
                'type' => OperationType::Deposit,
                'status' => OperationStatus::Completed,
                'initiator_id' => $user->id,
                'description' => $description,
            ]);

            $this->applyEntry($wallet, $operation, EntryDirection::Credit, $this->toCents($amount));

            return $operation->load('entries.wallet.user');
        });
    }

    /**
     * Move saldo entre duas carteiras e grava os lançamentos da operação.
     */
    public function transfer(User $sender, User $recipient, string $amount, ?string $description = null): Operation
    {
        return DB::transaction(function () use ($sender, $recipient, $amount, $description) {
            $wallets = $this->lockWalletsForUsers([$sender, $recipient]);
            $senderWallet = $wallets[$sender->id];
            $recipientWallet = $wallets[$recipient->id];
            $amountInCents = $this->toCents($amount);

            if (! $recipientWallet->isAvailable()) {
                throw new RecipientUnavailableException('A conta de destino esta inativa ou deletada e nao pode receber transferencias.');
            }

            if ($this->toCents((string) $senderWallet->balance) < $amountInCents) {
                throw new InsufficientFundsException('Saldo insuficiente para concluir a transferência.');
            }

            $operation = Operation::query()->create([
                'type' => OperationType::Transfer,
                'status' => OperationStatus::Completed,
                'initiator_id' => $sender->id,
                'description' => $description,
            ]);

            $this->applyEntry($senderWallet, $operation, EntryDirection::Debit, $amountInCents);
            $this->applyEntry($recipientWallet, $operation, EntryDirection::Credit, $amountInCents);

            return $operation->load('entries.wallet.user');
        });
    }

    /**
     * Reverte uma operação concluída gerando lançamentos inversos.
     */
    public function reverse(Operation $operation, User $actor, ?string $reason = null): Operation
    {
        return DB::transaction(function () use ($operation, $actor, $reason) {
            $operation = Operation::query()
                ->whereKey($operation->getKey())
                ->with(['entries.wallet.user', 'reversal'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $operation->isParticipant($actor)) {
                throw new InvalidOperationReversalException('Você não pode reverter uma operação da qual não participa.');
            }

            if (! $operation->canBeReversed()) {
                throw new InvalidOperationReversalException('Essa operação já foi revertida ou não permite reversão.');
            }

            $walletIds = $operation->entries->pluck('wallet_id')->unique()->sort()->values();
            $wallets = Wallet::query()
                ->whereIn('id', $walletIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($wallets->contains(fn (Wallet $wallet) => ! $wallet->isAvailable())) {
                throw new InvalidOperationReversalException('Nao e possivel reverter uma operacao vinculada a uma conta inativa ou deletada.');
            }

            $reversal = Operation::query()->create([
                'type' => OperationType::Reversal,
                'status' => OperationStatus::Completed,
                'initiator_id' => $actor->id,
                'description' => 'Reversão da operação '.$operation->uuid,
                'reversal_of_id' => $operation->id,
            ]);

            foreach ($operation->entries as $entry) {
                $wallet = $wallets[$entry->wallet_id];
                $direction = $entry->direction === EntryDirection::Credit
                    ? EntryDirection::Debit
                    : EntryDirection::Credit;

                $this->applyEntry($wallet, $reversal, $direction, $this->toCents((string) $entry->amount));
            }

            $operation->update([
                'status' => OperationStatus::Reversed,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            return $reversal->load('entries.wallet.user', 'reversalOf');
        });
    }

    /**
     * Bloqueia a carteira do usuário para atualização durante a transação.
     */
    private function lockWalletForUser(User $user): Wallet
    {
        $walletId = $user->wallet()->firstOrCreate([], ['balance' => 0])->id;

        return Wallet::query()->whereKey($walletId)->lockForUpdate()->firstOrFail();
    }

    /**
        * Bloqueia e retorna as carteiras de vários usuários em ordem consistente.
        *
     * @param  array<int, User>  $users
     * @return array<int, Wallet>
     */
    private function lockWalletsForUsers(array $users): array
    {
        $walletIdsByUser = collect($users)
            ->mapWithKeys(fn (User $user) => [$user->id => $user->wallet()->firstOrCreate([], ['balance' => 0])->id]);

        $wallets = Wallet::query()
            ->whereIn('id', $walletIdsByUser->values())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return $walletIdsByUser
            ->mapWithKeys(fn (int $walletId, int $userId) => [$userId => $wallets[$walletId]])
            ->all();
    }

    /**
     * Aplica um lançamento contábil e atualiza o saldo resultante da carteira.
     */
    private function applyEntry(Wallet $wallet, Operation $operation, EntryDirection $direction, int $amountInCents): void
    {
        $balanceBefore = $this->toCents((string) $wallet->balance);
        $balanceAfter = $direction === EntryDirection::Credit
            ? $balanceBefore + $amountInCents
            : $balanceBefore - $amountInCents;

        $wallet->update([
            'balance' => $this->fromCents($balanceAfter),
        ]);

        LedgerEntry::query()->create([
            'operation_id' => $operation->id,
            'wallet_id' => $wallet->id,
            'direction' => $direction,
            'amount' => $this->fromCents($amountInCents),
            'balance_before' => $this->fromCents($balanceBefore),
            'balance_after' => $this->fromCents($balanceAfter),
            'created_at' => now(),
        ]);
    }

    /**
     * Converte um valor decimal em centavos para evitar erro de precisão.
     */
    private function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * Converte centavos para string decimal compatível com o banco.
     */
    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}