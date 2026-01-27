<?php

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows payment statistics', function () {
    Carbon::setTestNow('2026-01-27 10:00:00');

    $user = User::factory()->create();
    $this->actingAs($user);

    Payment::factory()->create([
        'status' => 'paid',
        'phone_number' => '+2348012345678',
        'paid_at' => Carbon::now()->subHour(),
        'amount' => 5000,
    ]);

    Payment::factory()->create([
        'status' => 'fulfilled',
        'phone_number' => '+2348012345678',
        'paid_at' => Carbon::now()->subDays(10),
        'amount' => 10000,
    ]);

    Payment::factory()->create([
        'status' => 'paid',
        'phone_number' => '+2348099999999',
        'paid_at' => Carbon::now()->subMonths(2),
        'amount' => 20000,
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('stats.today_count', 1)
        ->where('stats.month_count', 2)
        ->where('stats.year_count', 3)
        ->where('stats.total_subscribers', 2)
        ->where('stats.total_amount', 35000)
        ->where('stats.currency', 'NGN'));
});
