<?php

namespace App\Http\Requests\auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LogoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
       
        if (Auth::guard('web')->check()) {
            return true; // Autorise la déconnexion si l'utilisateur est authentifié
        }
        return false; // Refuse la déconnexion si l'utilisateur n'est pas authentifié
       

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        
        ];
    }
}
