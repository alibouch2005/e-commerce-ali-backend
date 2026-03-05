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
            'product' => $this->product->name,
            'quantity' => $this->quantity,
            'total_price' => $this->quantity * $this->price,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
