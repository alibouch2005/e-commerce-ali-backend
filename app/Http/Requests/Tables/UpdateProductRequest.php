<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $data = [
            'sale_price' => $this->filled('sale_price') ? $this->input('sale_price') : null,
            'sale_ends_at' => $this->filled('sale_ends_at') ? $this->input('sale_ends_at') : null,
        ];

        if ($this->has('free_delivery')) {
            $data['free_delivery'] = $this->boolean('free_delivery');
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0.01',
            'sale_price' => 'sometimes|nullable|numeric|min:0|lt:price',
            'sale_ends_at' => 'sometimes|nullable|date|after:now',
            'stock' => 'sometimes|integer|min:0',
            'free_delivery' => 'sometimes|boolean',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images' => 'sometimes|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'category_id' => 'sometimes|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'sale_price.lt' => 'Le prix promo doit etre inferieur au prix normal.',
            'sale_ends_at.after' => 'La date de fin de promotion doit etre dans le futur.',
            'image.max' => 'L image principale ne doit pas depasser 5 Mo.',
            'images.*.max' => 'Chaque image supplementaire ne doit pas depasser 5 Mo.',
        ];
    }
}
