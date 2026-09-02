<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sale_price' => $this->filled('sale_price') ? $this->input('sale_price') : null,
            'sale_ends_at' => $this->filled('sale_ends_at') ? $this->input('sale_ends_at') : null,
            'free_delivery' => $this->boolean('free_delivery'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'sale_ends_at' => 'nullable|date|after:now',
            'stock' => 'required|integer|min:0',
            'free_delivery' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'category_id' => 'required|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'sale_price.lt' => 'Le prix promo doit etre inferieur au prix normal.',
            'sale_ends_at.after' => 'La date de fin de promotion doit etre dans le futur.',
            'category_id.required' => 'Choisissez une categorie.',
            'category_id.exists' => 'La categorie choisie est invalide.',
            'image.max' => 'L image principale ne doit pas depasser 5 Mo.',
            'images.*.max' => 'Chaque image supplementaire ne doit pas depasser 5 Mo.',
        ];
    }
}
