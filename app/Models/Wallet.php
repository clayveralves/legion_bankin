<?php

namespace App\Models;

use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'balance', 'opt_active', 'opt_deleted'])]
class Wallet extends Model
{
    use HasFactory;

    /**
     * Restringe a consulta para carteiras aptas a receber movimentações.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('opt_active', true)
            ->where('opt_deleted', false);
    }

    /**
     * Indica se a carteira pode participar de novas transferências.
     */
    public function isAvailable(): bool
    {
        return $this->opt_active && ! $this->opt_deleted;
    }

    /**
     * Formata o identificador interno como número de conta com seis dígitos.
     */
    public function getFormattedAccountNumberAttribute(): string
    {
        return str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Retorna o titular da carteira.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lista os lançamentos contábeis vinculados à carteira.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Converte o saldo para decimal com duas casas.
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'opt_active' => 'boolean',
            'opt_deleted' => 'boolean',
        ];
    }
}