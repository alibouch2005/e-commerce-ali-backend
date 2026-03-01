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
}
