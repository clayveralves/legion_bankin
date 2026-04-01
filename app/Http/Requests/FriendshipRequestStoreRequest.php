<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FriendshipRequestStoreRequest extends FormRequest
{
    /**
     * Permite o envio de solicitação pelo usuário autenticado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Garante que a solicitação aponte para outro usuário válido.
     */
    public function rules(): array
    {
        return [
            'friend_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::notIn([$this->user()?->id]),
            ],
        ];
    }
}