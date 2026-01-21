<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::query()->upsert([
            [
                'plan_type' => 1,
                'name' => 'Hourly Unlimited Access',
                'amount' => 5000,
                'currency' => 'NGN',
            ],
            [
                'plan_type' => 2,
                'name' => '24 Hours Unlimited Access',
                'amount' => 20000,
                'currency' => 'NGN',
            ],
            [
                'plan_type' => 3,
                'name' => 'Weekly Unlimited Access',
                'amount' => 100000,
                'currency' => 'NGN',
            ],
            [
                'plan_type' => 4,
                'name' => 'Monthly Unlimited Access',
                'amount' => 195000,
                'currency' => 'NGN',
            ],
        ], ['plan_type'], ['name', 'amount', 'currency']);
    }
}
