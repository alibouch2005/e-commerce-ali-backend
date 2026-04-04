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
            'status' => $this->status,
            'adresse_livraison' => $this->adresse_livraison,
            'phone' => $this->phone,
            'payment_method' => $this->payment_method,
            'created_at' => $this->created_at, // Utile pour la date
            
            // 🔥 CHARGEMENT DES RELATIONS
            // On utilise $this->user pour récupérer l'objet client
            'user' => $this->whenLoaded('user'), 
            
            // Tes articles
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}