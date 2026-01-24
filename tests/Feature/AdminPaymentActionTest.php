<?php

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('reverifies a payment with paystack and marks it as paid', function () {
    config([
        'services.paystack.secret_key' => 'test-secret',
    ]);

    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'status' => 'pending',
        'paystack_reference' => 'ps_ref_123',
        'paid_at' => null,
    ]);

    Http::fake([
        '*transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => 'success',
                'reference' => $payment->paystack_reference,
            ],
        ]),
    ]);

    $response = $this->actingAs($user)->post(
        route('admin.payments.reverify', $payment),
    );

    $response->assertRedirect();
    $payment->refresh();

    expect($payment->status)->toBe('paid')
        ->and($payment->paid_at)->not->toBeNull();
});

it('marks a payment as fulfilled', function () {
    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'status' => 'paid',
        'paid_at' => null,
    ]);

    $response = $this->actingAs($user)->post(
        route('admin.payments.fulfill', $payment),
    );

    $response->assertRedirect();
    $payment->refresh();

    expect($payment->status)->toBe('fulfilled')
        ->and($payment->paid_at)->not->toBeNull();
});
