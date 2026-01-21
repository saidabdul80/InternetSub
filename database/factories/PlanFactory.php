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
            'plan_type' => $this->faker->unique()->numberBetween(1, 20),
            'name' => $this->faker->words(2, true),
            'amount' => $this->faker->numberBetween(5000, 50000),
            'currency' => 'NGN',
        ];
    }
}
