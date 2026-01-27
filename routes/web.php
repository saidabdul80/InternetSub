<?php

use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('vouchers/upload', [VoucherController::class, 'store'])->name('vouchers.upload');
        Route::post('payments/{payment}/reverify', [PaymentController::class, 'reverify'])
            ->name('payments.reverify');
        Route::post('payments/{payment}/fulfill', [PaymentController::class, 'fulfill'])
            ->name('payments.fulfill');
    });

Route::get('send/sms', function () {

    $config = config('services.sms');
    $baseUrl = config('services.sms.base_url');
    $username = config('services.sms.email_address');
    $apiKey = config('services.sms.api_key');
    $senderName = config('services.sms.sender_name');

    // if ($baseUrl === '' || $username === '' || $apiKey === '' || $senderName === '') {
    //     return;
    // }

    $messageText = sprintf('Your GoodNews Wi-Fi voucher code is %s.', 12343);
    Log::info('Sending SMS', [
        'base_url' => $baseUrl,
        'username' => $username,
        'api_key' => $apiKey,
        'sender_name' => $senderName,
        'message_text' => $messageText,
    ]);
    try {
        $response = Http::get($baseUrl, [
            'username' => $username,
            'apikey' => $apiKey,
            'sender' => $senderName,
            'messagetext' => $messageText,
            'flash' => 0,
            'recipients' => '2348065291757',
            'dndsender' => 1,
        ]);
        Log::info('SMS sent', ['response' => $response->body()]);
        echo $response->body();
    } catch (\Exception $exception) {
        Log::error($exception);
    }
})->name('vouchers.index');
require __DIR__.'/settings.php';
