<?php

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Voucher;
use App\Services\MikrotikService;
use function Pest\Laravel\mock;

test('admins can manually subscribe users', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $user = User::factory()->create([
        'is_admin' => false,
    ]);
    $plan = Plan::factory()->create([
        'duration_minutes' => 120,
    ]);

    mock(MikrotikService::class, function ($mock) {
        $mock->shouldReceive('upsertHotspotUser')->once();
    });

    $response = $this->actingAs($admin)->post(route('admin.subscriptions.store'), [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
    ]);

    $response->assertRedirect(route('admin.subscriptions.index', absolute: false));

    expect(Payment::query()->where('gateway', 'manual')->exists())->toBeTrue();
    expect(Subscription::query()->where('user_id', $user->id)->first()?->status)->toBe('active');
    expect(Voucher::query()->where('user_id', $user->id)->exists())->toBeTrue();
});
