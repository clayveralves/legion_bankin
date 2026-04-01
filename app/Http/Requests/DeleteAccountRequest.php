<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    /**
     * Permite que o usuario autenticado solicite a exclusao logica da propria conta.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Exige a senha atual para confirmar a exclusao logica da conta.
     */
    public function rules(): array
    {
        return [
            'delete_form' => ['required', 'in:1'],
            'delete_current_password' => ['required', 'current_password'],
        ];
    }

    /**
     * Nomeia os campos usados na confirmacao da exclusao.
     */
    public function attributes(): array
    {
        return [
            'delete_current_password' => 'senha atual',
        ];
    }

    /**
     * Personaliza a mensagem de senha incorreta no fluxo de exclusao logica.
     */
    public function messages(): array
    {
        return [
            'delete_current_password.current_password' => 'A senha informada para exclusão está incorreta.',
        ];
    }
}