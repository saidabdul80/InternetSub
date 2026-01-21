<?php

use App\Models\Plan;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

it('uploads vouchers from csv, json, and txt files', function (string $filename, string $contents) {
    $user = User::factory()->create();
    Plan::factory()->create([
        'plan_type' => 1,
        'amount' => 5000,
        'currency' => 'NGN',
    ]);

    $file = UploadedFile::fake()->createWithContent($filename, $contents);

    $this->withoutMiddleware([
        VerifyCsrfToken::class,
        ValidateCsrfToken::class,
    ]);

    $response = $this->actingAs($user)->post(route('admin.vouchers.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect();
    expect(Voucher::query()->count())->toBe(2);
})->with([
    'csv' => [
        'vouchers.csv',
        "code,plan_type\n0066983371,1\n0308798903,1\n",
    ],
    'json' => [
        'vouchers.json',
        '[{"code":"0066983371","plan_type":1},{"code":"0308798903","plan_type":1}]',
    ],
    'json-bom' => [
        'vouchers.json',
        "\xEF\xBB\xBF".'[{"code":"0066983371","plan_type":1},{"code":"0308798903","plan_type":1}]',
    ],
    'txt' => [
        'vouchers.txt',
        "0066983371,1\n0308798903 1\n",
    ],
]);

it('shows uploaded vouchers in the admin list', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create([
        'plan_type' => 1,
        'amount' => 5000,
        'currency' => 'NGN',
    ]);

    Voucher::factory()->create([
        'plan_type' => $plan->plan_type,
        'code' => '0066983371',
        'status' => 'available',
    ]);

    $response = $this->actingAs($user)->get(route('admin.vouchers.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Vouchers/Index')
        ->has('vouchers', 1));
});
