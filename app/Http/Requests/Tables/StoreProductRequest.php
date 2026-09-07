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
        $variantOptions = $this->input('variant_options');
        if (is_string($variantOptions)) {
            $decoded = json_decode($variantOptions, true);
            $variantOptions = is_array($decoded) ? $decoded : [];
        }

        $variantPrices = $this->input('variant_prices');
        if (is_string($variantPrices)) {
            $decoded = json_decode($variantPrices, true);
            $variantPrices = is_array($decoded) ? $decoded : [];
        }

        $this->merge([
            'sale_price' => $this->filled('sale_price') ? $this->input('sale_price') : null,
            'sale_ends_at' => $this->filled('sale_ends_at') ? $this->input('sale_ends_at') : null,
            'free_delivery' => $this->boolean('free_delivery'),
            'has_variants' => $this->boolean('has_variants'),
            'variant_options' => $variantOptions,
            'variant_prices' => $variantPrices,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'long_description' => 'nullable|string',
            'price' => 'required|numeric|min:0.01',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'sale_ends_at' => 'nullable|date|after:now',
            'stock' => 'required|integer|min:0',
            'free_delivery' => 'boolean',
            'has_variants' => 'boolean',
            'variant_options' => 'nullable|array',
            'variant_options.*' => 'nullable|array',
            'variant_options.*.*' => 'nullable|string|max:80',
            'variant_prices' => 'nullable|array',
            'variant_prices.*' => 'nullable|array',
            'variant_prices.*.*' => 'nullable|numeric|min:0.01',
            'variant_color_names' => 'nullable|array|max:30',
            'variant_color_names.*' => 'nullable|string|max:80',
            'variant_color_images' => 'nullable|array|max:30',
            'variant_color_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,webm|max:51200',
            'remove_video' => 'nullable|boolean',
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
            'video.mimes' => 'La video doit etre au format MP4, MOV ou WEBM.',
            'video.max' => 'La video produit ne doit pas depasser 50 Mo.',
            'images.*.max' => 'Chaque image supplementaire ne doit pas depasser 5 Mo.',
        ];
    }
}
