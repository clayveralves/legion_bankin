<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Permite o cadastro para visitantes.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define as regras de validação para abertura de conta.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    /**
     * Personaliza mensagens de validacao do cadastro.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Já existe uma conta cadastrada com este e-mail. Se for uma conta excluída, solicite a um administrador a reativação.',
        ];
    }
}