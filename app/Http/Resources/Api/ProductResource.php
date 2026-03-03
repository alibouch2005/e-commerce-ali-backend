<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // role de la ressource : transformer les données du modèle Product en un format approprié pour l'API, en incluant les informations de la catégorie associée
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'image' => $this->image
                ? asset('storage/' . $this->image)
                : null,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
        ];
    }
}
