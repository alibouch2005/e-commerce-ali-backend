<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'name' => $this->faker->name(),
        'email' => $this->faker->unique()->safeEmail(),
        'password' => Hash::make('12345678'),
        'phone' => $this->faker->numerify('06########'),
        'role' => 'client', // rôle par défaut
        'address' => $this->faker->address(),
    ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    
         public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin()
    {
        return $this->state(fn () => [
            'role' => 'admin',
        ]);
    }

    public function client()
    {
        return $this->state(fn () => [
            'role' => 'client',
        ]);
    }

    public function livreur()
    {
        return $this->state(fn () => [
            'role' => 'livreur',
        ]);
    }
}
