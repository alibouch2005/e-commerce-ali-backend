<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;// Autorise tous les utilisateurs à faire cette requête (inscription), vous pouvez ajouter des conditions ici si nécessaire
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255', // Le nom est requis, doit être une chaîne de caractères et ne pas dépasser 255 caractères
            'email' => 'required|string|email|max:255|unique:users', // L'email est requis, doit être une chaîne de caractères au format email, ne pas dépasser 255 caractères et doit être unique dans la table users
            'password' => 'required|string|min:8|confirmed', // Le mot de passe est requis, doit être une chaîne de caractères, doit comporter au moins 8 caractères et doit être confirmé (doit correspondre à un champ password_confirmation)
            'password_confirmation' => 'required|string|min:8', // Le champ de confirmation du mot de passe est requis, doit être une chaîne de caractères et doit comporter au moins 8 caractères
            'role' => 'required|in:client,livreur,admin', // Le rôle est requis et doit être l'un des suivants : client, livreur, admin
            'phone' => 'required|string|max:10', // Le numéro de téléphone est requis, doit être une chaîne de caractères et ne pas dépasser 20 caractères
            'address' => 'nullable|string|max:500', // L'adresse est optionnelle, doit être une chaîne de caractères et ne pas dépasser 500 caractères
            
            
        ];
    }
}
