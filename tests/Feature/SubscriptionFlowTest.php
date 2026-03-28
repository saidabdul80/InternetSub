<?php

use App\Models\MikrotikUser;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\MikrotikService;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

test('subscription plans page renders with hotspot context', function () {
    $plan = Plan::factory()->create([
        'plan_type' => 1,
        'name' => '4 Hours Unlimited Access',
        'amount' => 10000,
        'currency' => 'NGN',
    ]);

    $response = $this->get('/app?hotspot_return=https%3A%2F%2Fhotspot.test%2Flogin&hotspot_dst=https%3A%2F%2Fexample.com&phone=08012345678');

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Subscription/Plans')
        ->where('hotspot.return_url', 'https://hotspot.test/login')
        ->where('hotspot.dst', 'https://example.com')
        ->where('hotspot.phone', '08012345678')
        ->where('plans.0.plan_type', $plan->plan_type));
});

test('subscription start skips payment when phone has active mikrotik user', function () {
    Plan::factory()->create([
        'plan_type' => 2,
        'name' => '24 Hours Unlimited Access',
        'amount' => 20000,
        'currency' => 'NGN',
    ]);

    MikrotikUser::query()->create([
        'phone_number' => '08012345678',
        'username' => '08012345678',
        'profile' => '24-hours',
        'plan_type' => 2,
        'status' => 'active',
        'activated_at' => now()->subHour(),
        'expires_at' => now()->addHours(12),
        'last_synced_at' => now(),
    ]);

    $response = $this->post('/app/start', [
        'plan_type' => 2,
        'gateway' => 'paystack',
        'phone_number' => '08012345678',
        'hotspot_return' => 'https://hotspot.test/login',
        'hotspot_dst' => 'https://example.com',
    ]);

    $response->assertRedirect();

    $location = (string) $response->headers->get('Location');

    expect($location)->toContain('https://hotspot.test/login')
        ->and($location)->toContain('autologin=1')
        ->and($location)->toContain('username=08012345678');

    expect(Payment::query()->count())->toBe(0);
});

test('subscription start initializes paystack with configured checkout channels', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
        'services.paystack.default_email' => 'guest@example.com',
        'services.paystack.channels' => ['card', 'bank', 'ussd', 'bank_transfer', 'qr'],
    ]);

    Plan::factory()->create([
        'plan_type' => 1,
        'name' => '4 Hours Unlimited Access',
        'amount' => 10000,
        'currency' => 'NGN',
    ]);

    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://paystack.test/authorize',
                'reference' => 'ps_ref_123',
            ],
        ]),
    ]);

    $response = $this->post('/app/start', [
        'plan_type' => 1,
        'gateway' => 'paystack',
        'phone_number' => '08012345678',
        'hotspot_return' => 'https://hotspot.test/login',
        'hotspot_dst' => 'https://example.com',
    ]);

    $response->assertRedirect('https://paystack.test/authorize');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.paystack.co/transaction/initialize'
            && data_get($request->data(), 'email') === 'guest@example.com'
            && data_get($request->data(), 'channels') === ['card', 'bank', 'ussd', 'bank_transfer', 'qr']
            && data_get($request->data(), 'metadata.phone_number') === '08012345678'
            && data_get($request->data(), 'metadata.custom_fields.0.variable_name') === 'phone_number';
    });

    $payment = Payment::query()->latest('id')->first();

    expect($payment)->not->toBeNull()
        ->and($payment?->paystack_reference)->toBe('ps_ref_123')
        ->and($payment?->status)->toBe('pending');
});

test('payment callback provisions mikrotik user and redirects to hotspot login', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
    ]);

    $plan = Plan::factory()->create([
        'plan_type' => 1,
        'name' => '4 Hours Unlimited Access',
        'amount' => 10000,
        'currency' => 'NGN',
    ]);

    $payment = Payment::query()->create([
        'plan_id' => $plan->id,
        'plan_type' => $plan->plan_type,
        'reference' => 'LOCALREF123',
        'amount' => $plan->amount,
        'currency' => $plan->currency,
        'access_point' => 'https://hotspot.test/login',
        'hotspot_dst' => 'https://example.com',
        'callback_url' => 'https://app.test/app/callback/paystack',
        'phone_number' => '08012345678',
        'status' => 'pending',
        'gateway' => 'paystack',
    ]);

    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => 'success',
                'reference' => 'LOCALREF123',
            ],
        ]),
    ]);

    $mikrotik = \Mockery::mock(MikrotikService::class);
    $mikrotik->shouldReceive('profileForPlan')
        ->once()
        ->with(1)
        ->andReturn('4-hours');
    $mikrotik->shouldReceive('provisionAccessUser')
        ->once()
        ->with('08012345678', '08012345678', '4-hours', 'Payment LOCALREF123', 1)
        ->andReturnNull();

    $this->app->instance(MikrotikService::class, $mikrotik);

    $response = $this->get('/app/callback/paystack?reference=LOCALREF123');

    $response->assertRedirect();

    $location = (string) $response->headers->get('Location');
    expect($location)->toContain('https://hotspot.test/login')
        ->and($location)->toContain('autologin=1')
        ->and($location)->toContain('username=08012345678')
        ->and($location)->toContain('dst='.urlencode('https://example.com'));

    $payment->refresh();
    expect($payment->status)->toBe('fulfilled');

    $mikrotikUser = MikrotikUser::query()->where('phone_number', '08012345678')->first();
    expect($mikrotikUser)->not->toBeNull()
        ->and($mikrotikUser?->status)->toBe('active')
        ->and($mikrotikUser?->plan_type)->toBe(1);
});
