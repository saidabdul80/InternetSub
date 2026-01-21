<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $plan = Plan::factory()->create();

        return [
            'plan_id' => $plan->id,
            'plan_type' => $plan->plan_type,
            'reference' => (string) Str::uuid(),
            'amount' => $plan->amount,
            'currency' => $plan->currency,
            'access_point' => $this->faker->url(),
            'callback_url' => $this->faker->url(),
            'status' => 'pending',
        ];
    }
}
