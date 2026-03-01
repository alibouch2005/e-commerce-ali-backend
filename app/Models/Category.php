<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
     use HasFactory;// Permet d'utiliser les fonctionnalités de la factory pour le modèle Category
    // Spécifie les attributs qui peuvent être assignés en masse (mass assignable) pour le modèle Category. Cela permet de protéger contre les assignations de masse non intentionnelles.
    protected $fillable =
    [
        'name',
        'description'
    ];

    // Category 1 ─── n Product (une catégorie peut avoir plusieurs produits)

    public function products()
    {
        return $this->hasMany(Product::class);// definire la relation de catégorie à produit
    }
}
