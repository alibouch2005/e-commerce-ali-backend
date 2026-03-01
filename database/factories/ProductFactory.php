<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */

  class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),// Génère un nom de produit composé de 3 mots
            'description' => $this->faker->sentence(),// Génère une description de produit sous forme de phrase
            'price' => $this->faker->randomFloat(2, 10, 2000),// Génère un prix aléatoire pour le produit, avec 2 décimales, entre 10 et 2000
            'stock' => $this->faker->numberBetween(0, 100),// Génère une quantité de stock aléatoire pour le produit, entre 0 et 100
            'category_id' => Category::inRandomOrder()->first()?->id 
                ?? Category::factory(),// Associe le produit à une catégorie existante aléatoire, ou crée une nouvelle catégorie si aucune n'existe
        ];
    }
}

