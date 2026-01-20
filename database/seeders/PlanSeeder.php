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
        Plan::query()->insert([
            [
                'name' => 'Daily Unlimited Plan',
                'price_kobo' => 10000,
                'speed_mbps' => 5,
                'duration_minutes' => 1440,
                'mikrotik_profile' => 'daily',
                'is_active' => true,
            ],
            [
                'name' => 'Weekly Unlimited Plan',
                'price_kobo' => 50000,
                'speed_mbps' => 8,
                'duration_minutes' => 10080,
                'mikrotik_profile' => 'weekly',
                'is_active' => true,
            ],  
            [
                'name' => 'Monthly Unlimited Plan',
                'price_kobo' => 180000,
                'speed_mbps' => 15,
                'duration_minutes' => 43200,
                'mikrotik_profile' => 'monthly',
                'is_active' => true,
            ]
        ]);
    }
}
