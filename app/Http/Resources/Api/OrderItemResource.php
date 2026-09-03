<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'product_name' => $this->relationLoaded('product') ? $this->product?->name : null,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total_price' => round((float) $this->quantity * (float) $this->price, 2),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
