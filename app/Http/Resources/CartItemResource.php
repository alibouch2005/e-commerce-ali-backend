<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => $this->quantity,
            'total_price' => $this->price * $this->quantity,
        ];
    }
}
