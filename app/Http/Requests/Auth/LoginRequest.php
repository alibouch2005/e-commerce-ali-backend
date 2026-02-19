<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorise tous les utilisateurs à faire cette requête (connexion), vous pouvez ajouter des conditions ici si nécessaire
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email|max:255', // L'email est requis, doit être une chaîne de caractères au format email et ne pas dépasser 255 caractères
            'password' => 'required|string|min:8', // Le mot de passe est requis, doit être une chaîne de caractères et doit comporter au moins 8 caractères
            
        ];
    }
}
