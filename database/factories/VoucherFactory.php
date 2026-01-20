<?php

namespace Database\Factories;

use App\Models\MikrotikRouter;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->bothify('VCHR#######');

        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'mikrotik_router_id' => MikrotikRouter::factory(),
            'code' => $code,
            'username' => $code,
            'password' => $code,
            'status' => 'active',
            'payment_reference' => fake()->unique()->uuid(),
            'activated_at' => now(),
            'expires_at' => now()->addHours(6),
        ];
    }
}
