<?php

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Voucher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

it('initializes a payment and returns a paystack authorization url', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
        'services.paystack.default_email' => 'test@example.com',
    ]);

    $plan = Plan::factory()->create([
        'plan_type' => 1,
        'amount' => 5000,
        'currency' => 'NGN',
    ]);

    $voucher = Voucher::factory()->create([
        'plan_type' => $plan->plan_type,
        'status' => 'available',
    ]);

    Http::fake([
        '*transaction/initialize' => Http::response([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://paystack.test/authorize',
                'reference' => 'ps_ref_123',
            ],
        ]),
    ]);

    $response = $this->postJson('/api/pay', [
        'plan_type' => $plan->plan_type,
        'url' => 'https://ap.test/login',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'authorization_url' => 'https://paystack.test/authorize',
        ]);

    $payment = Payment::query()->first();
    $voucher->refresh();

    expect($payment)->not->toBeNull()
        ->and($payment->plan_id)->toBe($plan->id)
        ->and($payment->status)->toBe('pending')
        ->and($payment->paystack_reference)->toBe('ps_ref_123');

    expect($voucher->status)->toBe('reserved')
        ->and($voucher->payment_id)->toBe($payment->id)
        ->and($voucher->reserved_at)->not->toBeNull();
});

it('verifies paystack payment and redirects with a voucher code', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
        'services.paystack.default_email' => 'test@example.com',
    ]);

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
        'status' => 'pending',
    ]);

    $voucher = Voucher::factory()->create([
        'plan_type' => $plan->plan_type,
        'code' => '0066983371',
        'status' => 'reserved',
        'payment_id' => $payment->id,
        'reserved_at' => now(),
    ]);

    Http::fake([
        '*transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => 'success',
                'reference' => $payment->reference,
            ],
        ]),
    ]);

    $response = $this->get('/api/paystack/callback?reference='.$payment->reference);

    $response->assertRedirect('https://ap.test/login?voucher='.$voucher->code);

    $payment->refresh();
    $voucher->refresh();

    expect($payment->status)->toBe('fulfilled')
        ->and($voucher->status)->toBe('used')
        ->and($voucher->payment_id)->toBe($payment->id);
});

it('rejects payments when no vouchers are available', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
        'services.paystack.default_email' => 'test@example.com',
    ]);

    $plan = Plan::factory()->create([
        'plan_type' => 1,
        'amount' => 5000,
        'currency' => 'NGN',
    ]);

    Http::fake();

    $response = $this->postJson('/api/pay', [
        'plan_type' => $plan->plan_type,
        'url' => 'https://ap.test/login',
    ]);

    $response->assertStatus(409)
        ->assertJson([
            'message' => 'No vouchers available for this plan.',
        ]);

    Http::assertNothingSent();
    expect(Payment::query()->count())->toBe(0);
});

it('reuses an expired reservation when initializing payment', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
        'services.paystack.default_email' => 'test@example.com',
    ]);

    $plan = Plan::factory()->create([
        'plan_type' => 1,
        'amount' => 5000,
        'currency' => 'NGN',
    ]);

    $expired = Carbon::now()->subMinutes(20);

    $voucher = Voucher::factory()->create([
        'plan_type' => $plan->plan_type,
        'status' => 'reserved',
        'reserved_at' => $expired,
    ]);

    Http::fake([
        '*transaction/initialize' => Http::response([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://paystack.test/authorize',
                'reference' => 'ps_ref_123',
            ],
        ]),
    ]);

    $response = $this->postJson('/api/pay', [
        'plan_type' => $plan->plan_type,
        'url' => 'https://ap.test/login',
    ]);

    $response->assertSuccessful();

    $voucher->refresh();
    $payment = Payment::query()->first();

    expect($voucher->status)->toBe('reserved')
        ->and($voucher->payment_id)->toBe($payment->id)
        ->and($voucher->reserved_at)->not->toBeNull();
});

it('uses an available voucher when no reservation exists on successful payment', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
        'services.paystack.default_email' => 'test@example.com',
    ]);

    $plan = Plan::factory()->create([
        'plan_type' => 1,
        'amount' => 5000,
        'currency' => 'NGN',
    ]);

    $payment = Payment::query()->create([
        'plan_id' => $plan->id,
        'plan_type' => $plan->plan_type,
        'reference' => 'local_ref_456',
        'amount' => $plan->amount,
        'currency' => $plan->currency,
        'access_point' => 'https://ap.test/login',
        'callback_url' => 'https://app.test/api/paystack/callback',
        'status' => 'pending',
    ]);

    $voucher = Voucher::factory()->create([
        'plan_type' => $plan->plan_type,
        'code' => '0099999999',
        'status' => 'available',
    ]);

    Http::fake([
        '*transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => 'success',
                'reference' => $payment->reference,
            ],
        ]),
    ]);

    $response = $this->get('/api/paystack/callback?reference='.$payment->reference);

    $response->assertRedirect('https://ap.test/login?voucher='.$voucher->code);

    $payment->refresh();
    $voucher->refresh();

    expect($payment->status)->toBe('fulfilled')
        ->and($voucher->status)->toBe('used')
        ->and($voucher->payment_id)->toBe($payment->id);
});
