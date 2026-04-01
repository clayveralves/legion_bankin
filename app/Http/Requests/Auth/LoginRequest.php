<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Permite o uso desta validação para visitantes não autenticados.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Valida o identificador de acesso e a senha do login.
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}