<?php

namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
       if($this->user()){
        return true; // Autorise la mise à jour du compte si l'utilisateur est authentifié
       }
       return false; // Refuse la mise à jour du compte si l'utilisateur n'est pas  authentifié
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255', // Le nom est optionnel, mais s'il est présent, il doit être une chaîne de caractères et ne pas dépasser 255 caractères
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $this->user()->id, // L'email est optionnel, mais s'il est présent, il doit être une chaîne de caractères au format email, ne pas dépasser 255 caractères et doit être unique dans la table users (en ignorant l'email de l'utilisateur actuel)
            'phone' => 'sometimes|string|digits:10', // Le numéro de téléphone est optionnel, mais s'il est présent, il doit être une chaîne de caractères et ne pas dépasser 20 caractères
            'address' => 'sometimes|nullable|string|max:500', // L'adresse est optionnelle, mais si elle est présente, elle doit être une chaîne de caractères et ne pas dépasser 500 caractères
        ];
    }
}
