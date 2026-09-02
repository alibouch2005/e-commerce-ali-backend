<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'livreur_id' => $this->livreur_id, // Ajouté pour le suivi
            'total_price' => $this->total_price,
            'delivery_fee' => $this->delivery_fee,
            'delivery_distance_km' => $this->delivery_distance_km,
            'status' => $this->status,
            'adresse_livraison' => $this->adresse_livraison,
            'delivery_latitude' => $this->delivery_latitude,
            'delivery_longitude' => $this->delivery_longitude,
            'phone' => $this->phone,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'payment_reference' => $this->payment_reference,
            'paid_at' => $this->paid_at,
            'fulfillment_method' => $this->fulfillment_method,
            'coupon_code' => $this->coupon_code,
            'discount_amount' => $this->discount_amount,
            'created_at' => $this->created_at, // Utile pour la date

            // 🔥 CHARGEMENT DES RELATIONS
            // On utilise $this->user pour récupérer l'objet client
            'user' => $this->whenLoaded('user'),

            // Tes articles
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'delivery' => $this->whenLoaded('delivery'),
        ];
    }
}
