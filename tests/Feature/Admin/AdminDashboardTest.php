<?php

use App\Models\User;

test('non-admin users cannot access the admin dashboard', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

test('admin users can access the admin dashboard', function () {
    $user = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
});
