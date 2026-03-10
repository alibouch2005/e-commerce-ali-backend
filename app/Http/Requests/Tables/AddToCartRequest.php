<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
       
        return true; // L'authentification est gérée par le middleware, donc on autorise ici tous les utilisateurs à faire cette requête
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id', // Vérifie que le produit existe dans la table products
            'quantity' => 'required|integer|min:1', // La quantité doit être un entier positif
        ];
    }
}
