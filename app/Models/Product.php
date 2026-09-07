<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'short_description',
        'long_description',
        'price',
        'sale_price',
        'sale_ends_at',
        'stock',
        'free_delivery',
        'has_variants',
        'variant_options',
        'variant_media',
        'variant_prices',
        'image',
        'video',
        'category_id',
    ];

    // Product n ─── 1 Category (plusieurs produits peuvent appartenir à une catégorie)

    public function category()
    {
        return $this->belongsTo(Category::class); // definire la relation de produit à catégorie
    }

    // Product n ─── n CartItem (un produit peut être dans plusieurs items de panier différents)
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // Product n ─── n OrderItem (un produit peut être dans plusieurs items de commande différents)
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    protected $appends = ['current_price', 'is_on_sale'];

    protected function casts(): array
    {
        return [
            'sale_ends_at' => 'datetime',
            'free_delivery' => 'boolean',
            'has_variants' => 'boolean',
            'variant_options' => 'array',
            'variant_media' => 'array',
            'variant_prices' => 'array',
        ];
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price && (! $this->sale_ends_at || $this->sale_ends_at->isFuture());
    }

    public function getCurrentPriceAttribute(): float
    {
        return $this->is_on_sale ? (float) $this->sale_price : (float) $this->price;
    }

    public function currentPriceForOptions(?array $selectedOptions = null): float
    {
        $selectedOptions = $selectedOptions ?: [];
        $variantPrices = $this->variant_prices ?: [];

        if (count($selectedOptions) > 1 && isset($variantPrices['_combinations']) && is_array($variantPrices['_combinations'])) {
            $combination = $selectedOptions;
            ksort($combination);
            $combinationKey = collect($combination)
                ->map(fn ($value, $group) => "{$group}={$value}")
                ->implode('|');
            $price = $variantPrices['_combinations'][$combinationKey] ?? null;

            if ($price !== null && $price !== '' && is_numeric($price)) {
                return round((float) $price, 2);
            }
        }

        foreach ($selectedOptions as $group => $value) {
            $price = $variantPrices[$group][$value] ?? null;

            if ($price !== null && $price !== '' && is_numeric($price)) {
                return round((float) $price, 2);
            }
        }

        return round((float) $this->current_price, 2);
    }
}
