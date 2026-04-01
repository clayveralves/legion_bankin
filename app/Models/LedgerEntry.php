<?php

namespace App\Models;

use App\Enums\EntryDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'operation_id',
    'wallet_id',
    'direction',
    'amount',
    'balance_before',
    'balance_after',
    'created_at',
])]
class LedgerEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'ledger_entries';

    /**
     * Retorna a operação que originou este lançamento contábil.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    /**
     * Retorna a carteira impactada por este lançamento.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Converte direção, valores e data para tipos adequados no modelo.
     */
    protected function casts(): array
    {
        return [
            'direction' => EntryDirection::class,
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }
}