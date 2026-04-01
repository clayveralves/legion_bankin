<?php

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Enums\OperationStatus;
use App\Enums\OperationType;
use App\Models\Friendship;
use App\Models\Operation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Monta todos os dados exibidos no dashboard, incluindo amizades e extrato filtrado.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('wallet');
        $friendSearch = trim((string) $request->string('friend_search'));
        $operationsSearch = trim((string) $request->string('operations_search'));
        $operationsType = (string) $request->string('operations_type');
        $operationsStatus = (string) $request->string('operations_status');
        $operationsDateStart = trim((string) $request->string('operations_date_start'));
        $operationsDateEnd = trim((string) $request->string('operations_date_end'));
        $perPage = (int) $request->integer('operations_per_page', 10);

        if (! in_array($perPage, [5, 10, 20, 50], true)) {
            $perPage = 10;
        }

        $operationsQuery = Operation::query()
            ->whereHas('entries', fn ($query) => $query->where('wallet_id', $user->wallet->id))
            ->with(['entries.wallet.user', 'reversal'])
            ->latest();

        if ($operationsSearch !== '') {
            $currentWalletId = $user->wallet->id;

            $operationsQuery->whereHas('entries.wallet', function (Builder $walletQuery) use ($operationsSearch, $currentWalletId) {
                $walletQuery
                    ->whereKeyNot($currentWalletId)
                    ->whereHas('user', function (Builder $userQuery) use ($operationsSearch) {
                        $userQuery->where('name', 'like', "%{$operationsSearch}%");
                    });
            });
        }

        if (in_array($operationsType, array_column(OperationType::cases(), 'value'), true)) {
            $operationsQuery->where('type', $operationsType);
        }

        if (in_array($operationsStatus, array_column(OperationStatus::cases(), 'value'), true)) {
            $operationsQuery->where('status', $operationsStatus);
        }

        $normalizedStartDate = $this->normalizeOperationSearchDate($operationsDateStart);
        $normalizedEndDate = $this->normalizeOperationSearchDate($operationsDateEnd);

        if ($normalizedStartDate) {
            $operationsQuery->whereDate('created_at', '>=', $normalizedStartDate);
        }

        if ($normalizedEndDate) {
            $operationsQuery->whereDate('created_at', '<=', $normalizedEndDate);
        }

        $operations = $operationsQuery
            ->paginate($perPage, ['*'], 'operations_page')
            ->withQueryString();

        $incomingRequests = Friendship::query()
            ->where('addressee_id', $user->id)
            ->where('status', FriendshipStatus::Pending)
            ->with('requester.wallet')
            ->latest()
            ->get();

        $outgoingRequests = Friendship::query()
            ->where('requester_id', $user->id)
            ->where('status', FriendshipStatus::Pending)
            ->with('addressee.wallet')
            ->latest()
            ->get();

        $friends = $user->friends();

        $acceptedFriendships = Friendship::query()
            ->where('status', FriendshipStatus::Accepted)
            ->where(function (Builder $query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->get()
            ->mapWithKeys(function (Friendship $friendship) use ($user) {
                $friendId = $friendship->requester_id === $user->id
                    ? $friendship->addressee_id
                    : $friendship->requester_id;

                return [$friendId => $friendship];
            });

        $relatedUserIds = Friendship::query()
            ->whereIn('status', [FriendshipStatus::Pending, FriendshipStatus::Accepted])
            ->where(function (Builder $query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->get()
            ->flatMap(function (Friendship $friendship) use ($user) {
                return [$friendship->requester_id === $user->id ? $friendship->addressee_id : $friendship->requester_id];
            })
            ->push($user->id)
            ->unique()
            ->values();

        $friendSearchResults = collect();

        if ($friendSearch !== '') {
            $accountNumber = ltrim($friendSearch, '0');
            $accountNumber = $accountNumber === '' ? '0' : $accountNumber;

            $friendSearchResults = User::query()
                ->with('wallet')
                ->whereNotIn('id', $relatedUserIds)
                ->whereHas('wallet', fn (Builder $walletQuery) => $walletQuery->available())
                ->where(function (Builder $query) use ($friendSearch, $accountNumber) {
                    $query
                        ->where('name', 'like', "%{$friendSearch}%")
                        ->orWhere('email', 'like', "%{$friendSearch}%")
                        ->orWhereHas('wallet', function (Builder $walletQuery) use ($accountNumber) {
                            if (ctype_digit($accountNumber)) {
                                $walletQuery->whereKey((int) $accountNumber);
                            }
                        });
                })
                ->orderBy('name')
                ->limit(10)
                ->get();
        }

        return view('dashboard', [
            'currentUser' => $user,
            'operations' => $operations,
            'operationsPerPage' => $perPage,
            'operationsSearch' => $operationsSearch,
            'operationsType' => $operationsType,
            'operationsStatus' => $operationsStatus,
            'operationsDateStart' => $operationsDateStart,
            'operationsDateEnd' => $operationsDateEnd,
            'operationTypes' => OperationType::cases(),
            'operationStatuses' => OperationStatus::cases(),
            'friends' => $friends,
            'acceptedFriendships' => $acceptedFriendships,
            'incomingRequests' => $incomingRequests,
            'outgoingRequests' => $outgoingRequests,
            'friendSearch' => $friendSearch,
            'friendSearchResults' => $friendSearchResults,
        ]);
    }

    /**
     * Normaliza datas digitadas em formatos aceitos para o padrão usado na consulta.
     */
    private function normalizeOperationSearchDate(string $search): ?string
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $search)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        return null;
    }
}