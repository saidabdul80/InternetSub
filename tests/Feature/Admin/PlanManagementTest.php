<?php

use App\Models\Plan;
use App\Models\User;

test('admins can create plans', function () {
    $user = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($user)->post(route('admin.plans.store'), [
        'name' => 'Weekly Plan',
        'price_kobo' => 150000,
        'speed_mbps' => 20,
        'duration_minutes' => 10080,
        'mikrotik_profile' => 'weekly',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.plans.index', absolute: false));

    $this->assertDatabaseHas('plans', [
        'name' => 'Weekly Plan',
        'mikrotik_profile' => 'weekly',
        'is_active' => true,
    ]);
});

test('admins can update plans', function () {
    $user = User::factory()->create([
        'is_admin' => true,
    ]);

    $plan = Plan::factory()->create([
        'name' => 'Starter Plan',
    ]);

    $response = $this->actingAs($user)->put(route('admin.plans.update', $plan), [
        'name' => 'Updated Plan',
        'price_kobo' => $plan->price_kobo,
        'speed_mbps' => $plan->speed_mbps,
        'duration_minutes' => $plan->duration_minutes,
        'mikrotik_profile' => $plan->mikrotik_profile,
        'is_active' => $plan->is_active,
    ]);

    $response->assertRedirect(route('admin.plans.index', absolute: false));

    expect($plan->fresh()->name)->toBe('Updated Plan');
});

test('admins can delete plans', function () {
    $user = User::factory()->create([
        'is_admin' => true,
    ]);

    $plan = Plan::factory()->create();

    $response = $this->actingAs($user)->delete(route('admin.plans.destroy', $plan));

    $response->assertRedirect(route('admin.plans.index', absolute: false));

    $this->assertDatabaseMissing('plans', [
        'id' => $plan->id,
    ]);
});
