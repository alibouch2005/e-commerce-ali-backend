<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin fixe pour tests
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => bcrypt('12345678'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'client@gmail.com'],
            [
                'name' => 'Client Test',
                'phone' => '0612345678',
                'address' => 'Casablanca',
                'role' => 'client',
                'password' => bcrypt('Client123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'livreur@gmail.com'],
            [
                'name' => 'Livreur Test',
                'phone' => '0623456789',
                'address' => 'Casablanca',
                'role' => 'livreur',
                'password' => bcrypt('Livreur123'),
            ]
        );

        //  5 clients
        User::factory(5)->create([
            'role' => 'client',
        ]);

        //  3 livreurs
        User::factory(3)->create([
            'role' => 'livreur',
        ]);

        // 10 catégories, sans dupliquer les catégories existantes lors d'un nouvel appel.
        $categories = Category::query()->get();
        for ($index = 1; $categories->count() < 10; $index++) {
            $category = Category::firstOrCreate(
                ['name' => "Category {$index}"],
                ['description' => "Category {$index} description"]
            );

            $categories->push($category);
        }

        // 20 produits
        Product::factory(20)->make()->each(function ($product) use ($categories) {
            $product->category_id = $categories->random()->id;
            $product->save();
        });
    }
}
