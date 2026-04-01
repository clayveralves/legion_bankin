<?php

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Http\Requests\FriendshipRequestStoreRequest;
use App\Http\Requests\FriendshipResponseRequest;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class FriendshipController extends Controller
{
    /**
     * Envia uma nova solicitação de amizade ou reabre uma solicitação recusada.
     */
    public function store(FriendshipRequestStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $friendId = $request->integer('friend_id');

        $friendships = Friendship::query()
            ->where(function ($query) use ($user, $friendId) {
                $query
                    ->where('requester_id', $user->id)
                    ->where('addressee_id', $friendId);
            })
            ->orWhere(function ($query) use ($user, $friendId) {
                $query
                    ->where('requester_id', $friendId)
                    ->where('addressee_id', $user->id);
            })
            ->get();

        if ($friendships->contains(fn (Friendship $friendship) => in_array($friendship->status, [FriendshipStatus::Pending, FriendshipStatus::Accepted], true))) {
            return back()->withErrors([
                'friendship' => 'Ja existe uma solicitacao ou amizade registrada com esse usuario.',
            ]);
        }

        $declinedRequest = $friendships->first(fn (Friendship $friendship) =>
            $friendship->requester_id === $user->id
            && $friendship->addressee_id === $friendId
            && $friendship->status === FriendshipStatus::Declined
        );

        if ($declinedRequest) {
            $declinedRequest->update([
                'status' => FriendshipStatus::Pending,
                'responded_at' => null,
            ]);
        } else {
            Friendship::query()->create([
                'requester_id' => $user->id,
                'addressee_id' => User::query()->findOrFail($friendId)->id,
                'status' => FriendshipStatus::Pending,
            ]);
        }

        return back()->with('status', 'Solicitacao de amizade enviada com sucesso.');
    }

    /**
     * Permite ao destinatário aceitar ou recusar uma solicitação pendente.
     */
    public function respond(FriendshipResponseRequest $request, Friendship $friendship): RedirectResponse
    {
        if ($friendship->addressee_id !== $request->user()->id || $friendship->status !== FriendshipStatus::Pending) {
            abort(403);
        }

        $action = $request->validated('action');

        $friendship->update([
            'status' => $action === 'accept' ? FriendshipStatus::Accepted : FriendshipStatus::Declined,
            'responded_at' => now(),
        ]);

        return back()->with(
            'status',
            $action === 'accept'
                ? 'Solicitacao de amizade aceita com sucesso.'
                : 'Solicitacao de amizade recusada.'
        );
    }

    /**
     * Cancela solicitação pendente ou remove uma amizade já aceita.
     */
    public function destroy(Friendship $friendship): RedirectResponse
    {
        $userId = request()->user()->id;

        $canCancelPending = $friendship->requester_id === $userId && $friendship->status === FriendshipStatus::Pending;
        $canRemoveAccepted = in_array($userId, [$friendship->requester_id, $friendship->addressee_id], true)
            && $friendship->status === FriendshipStatus::Accepted;

        if (! $canCancelPending && ! $canRemoveAccepted) {
            abort(403);
        }

        $wasAccepted = $friendship->status === FriendshipStatus::Accepted;
        $friendship->delete();

        return back()->with(
            'status',
            $wasAccepted
                ? 'Amizade removida com sucesso.'
                : 'Solicitacao de amizade cancelada com sucesso.'
        );
    }
}