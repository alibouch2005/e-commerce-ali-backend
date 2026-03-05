<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
    ];

    // OrderItem n ─── 1 Order (plusieurs items de commande peuvent appartenir à une même commande)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // OrderItem n ─── 1 Product (plusieurs items de commande peuvent référencer le même produit)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }   
    
}
