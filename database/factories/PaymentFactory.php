<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'reference' => fake()->unique()->uuid(),
            'amount_kobo' => fake()->numberBetween(50000, 500000),
            'currency' => 'NGN',
            'status' => 'success',
            'gateway' => 'paystack',
            'paid_at' => now(),
            'payload' => [
                'channel' => 'card',
                'ip' => fake()->ipv4(),
            ],
        ];
    }
}
