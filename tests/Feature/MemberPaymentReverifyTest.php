<?php

use App\Models\Customer;
use App\Models\MikrotikUser;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\MikrotikService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

it('reverifies a member pending payment and fulfills hotspot access', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
    ]);

    $customer = Customer::query()->create([
        'username' => '08012345678',
        'full_name' => 'Test Member',
        'phone_number' => '08012345678',
        'password' => Hash::make('08012345678'),
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
        'paystack_reference' => 'ps_ref_123',
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
                'reference' => $payment->paystack_reference,
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

    $response = $this
        ->from(route('member.orders.index'))
        ->withSession(['customer_id' => $customer->id])
        ->post(route('member.payments.reverify', $payment));

    $response->assertRedirect(route('member.orders.index'));
    $payment->refresh();

    expect($payment->status)->toBe('fulfilled')
        ->and($payment->paid_at)->not->toBeNull();

    $mikrotikUser = MikrotikUser::query()->where('phone_number', '08012345678')->first();
    expect($mikrotikUser)->not->toBeNull()
        ->and($mikrotikUser?->status)->toBe('active')
        ->and($mikrotikUser?->payment_id)->toBe($payment->id);
});

it('does not allow a member to reverify another members payment', function () {
    $customer = Customer::query()->create([
        'username' => '08012345678',
        'full_name' => 'Test Member',
        'phone_number' => '08012345678',
        'password' => Hash::make('08012345678'),
        'account_type' => 'Personal',
        'balance' => 0,
        'service_type' => 'Hotspot',
        'auto_renewal' => false,
        'status' => 'Active',
    ]);

    $payment = Payment::factory()->create([
        'phone_number' => '08099999999',
        'status' => 'pending',
    ]);

    $this
        ->withSession(['customer_id' => $customer->id])
        ->post(route('member.payments.reverify', $payment))
        ->assertNotFound();
});
