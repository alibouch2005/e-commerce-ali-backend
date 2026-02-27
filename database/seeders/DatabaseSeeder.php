<?php

namespace Database\Seeders;

use App\Models\Category;
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
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'role' => 'admin',
            'password' => bcrypt('12345678'),
        ]);

        //  5 clients
        User::factory(5)->create([
            'role' => 'client'
        ]);

        //  3 livreurs
        User::factory(3)->create([
            'role' => 'livreur'
        ]);

        // 10 catégories
        Category::factory(10)->create();
    }
}
