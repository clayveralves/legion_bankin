<?php

namespace App\Http\Requests;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    /**
     * Permite a validação para usuários autenticados.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Valida destinatário, valor, descrição e confirmação por senha da transferência.
     */
    public function rules(): array
    {
        return [
            'recipient_type' => ['required', 'string', Rule::in(['friend', 'account'])],
            'recipient_friend_id' => [
                Rule::requiredIf(fn () => $this->input('recipient_type') === 'friend'),
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $this->input('recipient_type') !== 'friend') {
                        return;
                    }

                    $isAcceptedFriend = Friendship::query()
                        ->where('status', FriendshipStatus::Accepted)
                        ->where(function ($query) use ($value) {
                            $query
                                ->where(function ($innerQuery) use ($value) {
                                    $innerQuery
                                        ->where('requester_id', $this->user()?->id)
                                        ->where('addressee_id', $value);
                                })
                                ->orWhere(function ($innerQuery) use ($value) {
                                    $innerQuery
                                        ->where('requester_id', $value)
                                        ->where('addressee_id', $this->user()?->id);
                                });
                        })
                        ->exists();

                    if (! $isAcceptedFriend) {
                        $fail('Selecione um amigo valido para realizar a transferencia.');

                        return;
                    }

                    $friend = User::query()->with('wallet')->find($value);

                    if (! $friend?->wallet?->isAvailable()) {
                        $fail('Nao e possivel transferir para um amigo com conta inativa ou deletada.');
                    }
                },
            ],
            'recipient_account' => [
                Rule::requiredIf(fn () => $this->input('recipient_type') === 'account'),
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $this->input('recipient_type') !== 'account') {
                        return;
                    }

                    $wallet = Wallet::query()->find($value);

                    if (! $wallet || $wallet->user_id === $this->user()?->id) {
                        $fail('Selecione uma conta de destino valida para realizar a transferencia.');

                        return;
                    }

                    if (! $wallet->isAvailable()) {
                        $fail('Nao e possivel transferir para uma conta inativa ou deletada.');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'description' => ['nullable', 'string', 'max:255'],
            'current_password' => ['required', 'current_password'],
        ];
    }

    /**
     * Traduz nomes de campos exibidos nas mensagens de validação.
     */
    public function attributes(): array
    {
        return [
            'recipient_friend_id' => 'amigo',
            'recipient_account' => 'numero da conta',
            'recipient_type' => 'tipo de destinatario',
            'current_password' => 'senha atual',
        ];
    }

    /**
     * Personaliza mensagens de erro específicas da transferência.
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'A senha informada esta incorreta.',
            'recipient_account.integer' => 'Informe um numero de conta valido.',
        ];
    }
}