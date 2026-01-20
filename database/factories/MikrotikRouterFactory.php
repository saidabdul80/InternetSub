<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MikrotikRouter>
 */
class MikrotikRouterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'host' => fake()->ipv4(),
            'port' => 8728,
            'username' => fake()->userName(),
            'password' => fake()->password(12),
            'use_ssl' => false,
            'timeout' => 10,
            'is_active' => true,
        ];
    }
}
