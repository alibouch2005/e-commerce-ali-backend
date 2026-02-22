<?php

namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if($this->user()){
            return true; // Autorise le changement de mot de passe si l'utilisateur est authentifié
        }
        return false; // Refuse le changement de mot de passe si l'utilisateur n'est pas  authentifié
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // (current_password) Vérifie automatiquement que le mot de passe actuel est correct grâce à la règle de validation "current_password"
            'current_password' => 'required|string|min:8', // Le mot de passe actuel est requis, doit être une chaîne de caractères et doit comporter au moins 8 caractères
            'new_password' => 'required|string|min:8|confirmed', // Le nouveau mot de passe est requis, doit être une chaîne de caractères, doit comporter au moins 8 caractères et doit être confirmé (doit correspondre à un champ new_password_confirmation)
            'new_password_confirmation' => 'required|string|min:8', // Le champ de confirmation du nouveau mot de passe est requis, doit être une chaîne de caractères et doit comporter au moins 8 caractères
        ];
    }
}
