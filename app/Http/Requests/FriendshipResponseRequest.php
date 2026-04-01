<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FriendshipResponseRequest extends FormRequest
{
    /**
     * Permite o tratamento da resposta da solicitação.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Restringe a ação para aceitar ou recusar.
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['accept', 'decline'])],
        ];
    }
}