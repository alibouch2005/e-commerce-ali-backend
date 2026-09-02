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
        'price',
        'sale_price',
        'sale_ends_at',
        'stock',
        'free_delivery',
        'image',
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
}
