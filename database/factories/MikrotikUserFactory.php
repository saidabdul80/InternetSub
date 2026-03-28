<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MikrotikUser>
 */
class MikrotikUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone_number' => '0'.$this->faker->numerify('8#########'),
            'username' => '0'.$this->faker->unique()->numerify('8#########'),
            'profile' => $this->faker->randomElement(['4-hours', '24-hours', 'weekly']),
            'plan_type' => $this->faker->numberBetween(1, 4),
            'status' => 'active',
            'payment_id' => Payment::factory(),
            'activated_at' => now(),
            'expires_at' => now()->addDay(),
            'last_synced_at' => now(),
        ];
    }
}
