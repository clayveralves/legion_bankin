<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Permite que o usuario autenticado atualize o proprio perfil.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Define as regras de validacao para a edicao de perfil.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'current_password' => ['required', 'current_password'],
            'account_status' => ['required', 'in:active,inactive'],
            'profile_form' => ['nullable', 'in:1'],
            'profile_edit_info' => ['nullable', 'in:0,1'],
            'profile_edit_password' => ['nullable', 'in:0,1'],
            'profile_edit_status' => ['nullable', 'in:0,1'],
        ];
    }

    /**
     * Define nomes mais claros para os campos exibidos nas mensagens.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'nova senha',
            'current_password' => 'senha atual',
            'account_status' => 'status da conta',
        ];
    }

    /**
     * Personaliza mensagens especificas do fluxo de confirmacao por senha.
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'A senha informada esta incorreta.',
            'password.confirmed' => 'A confirmacao da nova senha nao confere.',
        ];
    }
}