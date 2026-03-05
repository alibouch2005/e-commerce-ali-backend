<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'cart_id', 
        'product_id', 
        'quantity', 
        'price'
        ];

    // CartItem n ─── 1 Cart (plusieurs items de panier peuvent appartenir à un même panier)
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }  
    
    // CartItem n ─── 1 Product (plusieurs items de panier peuvent référencer le même produit)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
