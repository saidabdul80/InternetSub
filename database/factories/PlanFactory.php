<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'price_kobo' => fake()->numberBetween(50000, 500000),
            'speed_mbps' => fake()->numberBetween(5, 100),
            'duration_minutes' => fake()->numberBetween(60, 10080),
            'mikrotik_profile' => fake()->word(),
            'is_active' => true,
        ];
    }
}
