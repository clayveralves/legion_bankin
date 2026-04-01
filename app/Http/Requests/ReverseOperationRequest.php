<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReverseOperationRequest extends FormRequest
{
    /**
     * Permite a validação para usuários autenticados.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Valida o motivo opcional e a confirmação por senha da reversão.
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
            'current_password' => ['required', 'current_password'],
        ];
    }

    /**
     * Define nomes mais claros para os campos nas mensagens de erro.
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'senha atual',
        ];
    }

    /**
     * Personaliza mensagens de validação específicas da reversão.
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'A senha informada esta incorreta.',
        ];
    }
}