<?php

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Voucher;
use App\Services\MikrotikService;
use App\Services\PaystackService;
use Inertia\Testing\AssertableInertia as Assert;
use function Pest\Laravel\mock;

test('guests can view active plans', function () {
    $activePlan = Plan::factory()->create([
        'is_active' => true,
        'price_kobo' => 10000,
    ]);

    Plan::factory()->create([
        'is_active' => false,
        'price_kobo' => 20000,
    ]);

    $response = $this->get(route('plans.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('portal/Plans')
        ->has('plans', 1)
        ->where('plans.0.id', $activePlan->id)
    );
});

test('authenticated users can start checkout', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    mock(PaystackService::class, function ($mock) {
        $mock->shouldReceive('initializeTransaction')->andReturn([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://paystack.test/checkout',
                'access_code' => 'access',
            ],
        ]);
    });

    $response = $this->actingAs($user)->post(route('plans.checkout', $plan));

    $payment = Payment::first();

    expect($payment)->not->toBeNull();

    $response->assertRedirect(route('checkout.show', $payment, absolute: false));
    $this->assertDatabaseHas('payments', [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);
    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);
});

test('guests can start checkout with a phone number', function () {
    $plan = Plan::factory()->create();

    mock(PaystackService::class, function ($mock) {
        $mock->shouldReceive('initializeTransaction')->andReturn([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://paystack.test/checkout',
                'access_code' => 'access',
            ],
        ]);
    });

    $response = $this->post(route('plans.checkout', $plan), [
        'login' => '08012345678',
        'password' => 'A123',
    ]);

    $payment = Payment::first();

    $response->assertRedirect(route('checkout.show', $payment, absolute: false));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'phone' => '08012345678',
    ]);
});

test('payment confirmation provisions a voucher', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'duration_minutes' => 60,
    ]);

    $payment = Payment::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'reference' => 'paystack-ref',
        'amount_kobo' => 50000,
        'currency' => 'NGN',
        'status' => 'pending',
        'gateway' => 'paystack',
    ]);

    Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'payment_id' => $payment->id,
        'status' => 'pending',
    ]);

    mock(PaystackService::class, function ($mock) use ($payment) {
        $mock->shouldReceive('verifyTransaction')->andReturn([
            'status' => true,
            'data' => [
                'status' => 'success',
                'amount' => $payment->amount_kobo,
                'currency' => $payment->currency,
            ],
        ]);
    });

    mock(MikrotikService::class, function ($mock) {
        $mock->shouldReceive('upsertHotspotUser')->once();
    });

    $response = $this->actingAs($user)->postJson(
        route('checkout.confirm', $payment),
        ['reference' => $payment->reference],
    );

    $response->assertOk()->assertJson([
        'status' => 'success',
    ]);

    expect($payment->fresh()->status)->toBe('success');
    expect(Subscription::first()?->status)->toBe('active');
    expect(Voucher::where('payment_id', $payment->id)->exists())->toBeTrue();
});
