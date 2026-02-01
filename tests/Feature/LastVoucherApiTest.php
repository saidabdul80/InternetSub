<?php

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Voucher;

it('returns the last fulfilled voucher for a phone number', function () {
    $plan = Plan::factory()->create([
        'plan_type' => 1,
        'amount' => 5000,
        'currency' => 'NGN',
    ]);

    $payment = Payment::query()->create([
        'plan_id' => $plan->id,
        'plan_type' => $plan->plan_type,
        'reference' => 'local_ref_123',
        'amount' => $plan->amount,
        'currency' => $plan->currency,
        'access_point' => 'https://ap.test/login',
        'callback_url' => 'https://app.test/api/paystack/callback',
        'phone_number' => '+2347035398873',
        'status' => 'fulfilled',
        'paid_at' => now(),
    ]);

    Voucher::factory()->create([
        'plan_type' => $plan->plan_type,
        'code' => '0011223344',
        'status' => 'used',
        'payment_id' => $payment->id,
        'used_at' => now(),
    ]);

    $response = $this->postJson('/api/voucher/last', [
        'phone_number' => '+2347035398873',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'voucher_code' => '0011223344',
        ]);
});

it('returns 404 when no fulfilled voucher exists for a phone number', function () {
    $response = $this->postJson('/api/voucher/last', [
        'phone_number' => '+2347035398873',
    ]);

    $response->assertNotFound();
});
