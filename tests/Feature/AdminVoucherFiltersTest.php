<?php

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('filters payments by phone number, status, and date', function () {
    $user = User::factory()->create();
    $matchingDate = Carbon::now()->subDay();

    Payment::factory()->create([
        'phone_number' => '+2348012345678',
        'status' => 'paid',
        'created_at' => $matchingDate,
    ]);

    Payment::factory()->create([
        'phone_number' => '+2348099999999',
        'status' => 'failed',
        'created_at' => Carbon::now()->subDays(10),
    ]);

    $response = $this->actingAs($user)->get(route('admin.vouchers.index', [
        'phone_number' => '2348012345678',
        'status' => 'paid',
        'date_from' => $matchingDate->copy()->subDay()->toDateString(),
        'date_to' => $matchingDate->copy()->addDay()->toDateString(),
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Vouchers/Index')
        ->has('payments', 1)
        ->where('payments.0.phone_number', '+2348012345678')
        ->where('payments.0.status', 'paid')
        ->where('filters.phone_number', '2348012345678')
        ->where('filters.status', 'paid'));
});
