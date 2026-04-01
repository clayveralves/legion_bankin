<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['requester_id', 'addressee_id', 'status', 'responded_at'])]
class Friendship extends Model
{
    /**
     * Retorna o usuário que enviou a solicitação de amizade.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Retorna o usuário que recebeu a solicitação de amizade.
     */
    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    /**
     * Converte status e data de resposta para seus tipos apropriados.
     */
    protected function casts(): array
    {
        return [
            'status' => FriendshipStatus::class,
            'responded_at' => 'datetime',
        ];
    }
}