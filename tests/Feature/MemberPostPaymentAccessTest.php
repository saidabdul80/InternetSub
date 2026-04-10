<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\HotspotPaymentFulfillmentService;
use App\Services\MikrotikService;
use Illuminate\Support\Facades\Hash;

it('updates an existing customer matched by phone variant so they can log in with their phone after payment fulfillment', function () {
    $existingCustomer = Customer::query()->create([
        'username' => 'legacy-user',
        'full_name' => 'Legacy Customer',
        'phone_number' => '+2348012345678',
        'password' => Hash::make('old-password'),
        'account_type' => 'Personal',
        'balance' => 0,
        'service_type' => 'Hotspot',
        'auto_renewal' => false,
        'status' => 'Active',
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

    app(HotspotPaymentFulfillmentService::class)
        ->finalizePaymentAndCreateHotspotUser($payment, $mikrotik);

    $existingCustomer->refresh();

    expect(Customer::query()->count())->toBe(1)
        ->and($existingCustomer->username)->toBe('08012345678')
        ->and($existingCustomer->phone_number)->toBe('08012345678');

    $response = $this->post(route('member.login.store'), [
        'login' => '08012345678',
        'password' => '08012345678',
    ]);

    $response->assertRedirect(route('member.dashboard'));
});

it('uses the latest fulfilled hotspot access point when reconnecting from the member dashboard', function () {
    $customer = Customer::query()->create([
        'username' => '08012345678',
        'full_name' => 'Portal Customer',
        'phone_number' => '08012345678',
        'password' => Hash::make('08012345678'),
        'account_type' => 'Personal',
        'balance' => 0,
        'service_type' => 'Hotspot',
        'auto_renewal' => false,
        'status' => 'Active',
    ]);

    Payment::factory()->create([
        'phone_number' => '08012345678',
        'status' => 'fulfilled',
        'access_point' => 'https://hotspot.test/login',
        'hotspot_dst' => 'https://example.com',
        'paid_at' => now(),
    ]);

    $response = $this
        ->withSession(['customer_id' => $customer->id])
        ->get(route('member.connect'));

    $response->assertRedirect();

    $location = (string) $response->headers->get('Location');

    expect($location)->toContain('https://hotspot.test/login')
        ->and($location)->toContain('username=08012345678')
        ->and($location)->toContain('password=08012345678')
        ->and($location)->toContain('dst='.urlencode('https://example.com'));
});
