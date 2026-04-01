<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    /**
     * Permite a validação para usuários autenticados.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Valida o valor e a descrição opcional do depósito.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}