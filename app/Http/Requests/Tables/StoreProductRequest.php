<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
          if ($this->user() && $this->user()->role === 'admin') {
            return true; // L'utilisateur est autorisé à faire cette requête s'il est authentifié et a le rôle d'administrateur
        }
        return false; // L'utilisateur n'est pas autorisé à faire cette requête
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Définit les règles de validation pour les champs du formulaire de création de produit.
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Vous pouvez ajuster cette règle en fonction de la manière dont vous gérez les images (par exemple, si vous utilisez des fichiers téléchargés, vous pourriez utiliser 'image' au lieu de 'string')
            'category_id' => 'required|exists:categories,id',// Vérifie que la catégorie existe dans la table categories
        ];
    }
}
