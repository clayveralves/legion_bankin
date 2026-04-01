<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Garante que todo novo usuário receba uma carteira ao ser criado.
     */
    protected static function booted(): void
    {
        static::created(function (self $user): void {
            if (! $user->wallet()->exists()) {
                $user->wallet()->create();
            }
        });
    }

    /**
     * Retorna a carteira principal do usuário.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Lista as operações iniciadas pelo usuário.
     */
    public function initiatedOperations(): HasMany
    {
        return $this->hasMany(Operation::class, 'initiator_id');
    }

    /**
     * Retorna as solicitações de amizade enviadas pelo usuário.
     */
    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    /**
     * Retorna as solicitações de amizade recebidas pelo usuário.
     */
    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    /**
     * Consolida a lista de amigos aceitos independentemente de quem iniciou a amizade.
     */
    public function friends(): Collection
    {
        $sentFriends = $this->sentFriendRequests()
            ->where('status', FriendshipStatus::Accepted)
            ->with('addressee.wallet')
            ->get()
            ->pluck('addressee');

        $receivedFriends = $this->receivedFriendRequests()
            ->where('status', FriendshipStatus::Accepted)
            ->with('requester.wallet')
            ->get()
            ->pluck('requester');

        return $sentFriends
            ->concat($receivedFriends)
            ->filter(fn (self $friend) => $friend->wallet?->isAvailable())
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * Define os tipos convertidos automaticamente pelo modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
