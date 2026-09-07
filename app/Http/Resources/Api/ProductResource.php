<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description ?: $this->description,
            'long_description' => $this->long_description ?: $this->description,
            'price' => $this->price,
            'current_price' => $this->current_price,
            'sale_price' => $this->sale_price,
            'sale_ends_at' => $this->sale_ends_at,
            'is_on_sale' => $this->is_on_sale,
            'stock' => $this->stock,
            'low_stock' => $this->stock > 0 && $this->stock < 10,
            'soon_available' => $this->stock <= 0,
            'free_delivery' => (bool) $this->free_delivery,
            'has_variants' => (bool) $this->has_variants,
            'variant_options' => $this->has_variants ? ($this->variant_options ?: []) : [],
            'variant_media' => $this->variantMedia(),
            'variant_prices' => $this->has_variants ? ($this->variant_prices ?: []) : [],
            'image' => $this->imageUrl($this->image),
            'video' => $this->imageUrl($this->video),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $this->imageUrl($image->path),
            ])),
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    private function variantMedia(): array
    {
        $media = $this->variant_media ?: [];

        return [
            'color' => collect($media['color'] ?? [])
                ->mapWithKeys(fn ($path, $name) => [$name => $this->imageUrl($path)])
                ->all(),
        ];
    }
}
