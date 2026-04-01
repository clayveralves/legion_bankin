<?php

namespace App\Models;

use App\Enums\OperationStatus;
use App\Enums\OperationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'type',
    'status',
    'initiator_id',
    'description',
    'reversed_at',
    'reversal_reason',
    'reversal_of_id',
])]
class Operation extends Model
{
    use HasFactory;

    /**
     * Gera automaticamente um UUID para novas operações.
     */
    protected static function booted(): void
    {
        static::creating(function (self $operation): void {
            if (blank($operation->uuid)) {
                $operation->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Usa o UUID como identificador público nas rotas.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Retorna o usuário que iniciou a operação.
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    /**
     * Retorna a operação original quando este registro representa uma reversão.
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /**
     * Lista as reversões vinculadas a esta operação.
     */
    public function reversal(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    /**
     * Retorna os lançamentos de débito e crédito associados à operação.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Verifica se o usuário participou da operação por alguma das carteiras envolvidas.
     */
    public function isParticipant(User $user): bool
    {
        return $this->entries->contains(fn (LedgerEntry $entry) => $entry->wallet->user_id === $user->id);
    }

    /**
     * Identifica a contraparte da operação para o usuário informado.
     */
    public function counterpartyFor(User $user): ?User
    {
        $entry = $this->entries
            ->first(fn (LedgerEntry $ledgerEntry) => $ledgerEntry->wallet->user_id !== $user->id);

        return $entry?->wallet?->user;
    }

    /**
     * Calcula o valor formatado com sinal positivo ou negativo para o usuário.
     */
    public function signedAmountFor(User $user): ?string
    {
        $entry = $this->entries
            ->first(fn (LedgerEntry $ledgerEntry) => $ledgerEntry->wallet->user_id === $user->id);

        if (! $entry) {
            return null;
        }

        $signal = $entry->direction->value === 'credit' ? '+' : '-';

        return $signal.number_format((float) $entry->amount, 2, ',', '.');
    }

    /**
     * Informa se a operação ainda pode ser revertida.
     */
    public function canBeReversed(): bool
    {
        $this->loadMissing('entries.wallet');

        return $this->status === OperationStatus::Completed
            && $this->reversal_of_id === null
            && ! $this->reversal()->exists()
            && ! $this->entries->contains(fn (LedgerEntry $entry) => ! $entry->wallet?->isAvailable());
    }

    /**
     * Converte tipo, status e datas para os respectivos tipos do domínio.
     */
    protected function casts(): array
    {
        return [
            'type' => OperationType::class,
            'status' => OperationStatus::class,
            'reversed_at' => 'datetime',
        ];
    }
}