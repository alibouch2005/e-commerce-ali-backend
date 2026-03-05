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
        'stock',
        'image',
        'category_id',
    ];
    
    // Product n ─── 1 Category (plusieurs produits peuvent appartenir à une catégorie)

    public function category()
    {
        return $this->belongsTo(Category::class);// definire la relation de produit à catégorie
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
}
