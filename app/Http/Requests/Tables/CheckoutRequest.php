<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fulfillment_method' => $this->input('fulfillment_method', 'delivery'),
        ]);
    }

    public function rules(): array
    {
        return [
            'fulfillment_method' => 'required|in:delivery,pickup',
            'adresse_livraison' => 'required_if:fulfillment_method,delivery|nullable|string|max:255',
            'delivery_latitude' => 'nullable|numeric|between:-90,90',
            'delivery_longitude' => 'nullable|numeric|between:-180,180',
            'delivery_time_slot' => 'nullable|in:08_12,12_18,18_21',
            'phone' => 'required|string|max:20',
            'payment_method' => 'required|in:cash_on_delivery,card',
            'coupon_code' => 'nullable|string|max:50',
        ];
    }
}
