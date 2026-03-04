<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'user_id' => $this->user_id,
            'items' => CartItemResource::collection($this->items), // Utilise CartItemResource pour transformer les items du panier
            'total' => $this->items->sum(fn ($item) => $item->quantity * $item->price), // Calcule le total du panier
            'item_count' => $this->items->count(), // Compte le nombre d'items dans le panier
        ]   ;
    }
}
