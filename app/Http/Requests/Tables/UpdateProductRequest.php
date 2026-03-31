<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Définit les règles de validation pour les champs du formulaire de mise à jour de produit. Les règles sont similaires à celles de la création, mais avec "sometimes" pour permettre la mise à jour partielle.
        return [
            'name'=>'sometimes|string|max:255',
            'description'=>'sometimes|nullable|string',
            'price'=>'sometimes|numeric|min:0',
            'stock'=>'sometimes|integer|min:0',
            'image'=>'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Vous pouvez ajuster cette règle en fonction de la manière dont vous gérez les images (par exemple, si vous utilisez des fichiers téléchargés, vous pourriez utiliser 'image' au lieu de 'string')
            'category_id'=>'sometimes|exists:categories,id',// Vérifie que la catégorie existe dans la table categories

        ];
    }
}
