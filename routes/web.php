<?php

use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\RechargeController;
use App\Http\Controllers\Admin\RouterController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Member\AuthController as MemberAuthController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\MessageController as MemberMessageController;
use App\Http\Controllers\Member\OrderController as MemberOrderController;
use App\Http\Controllers\Member\PackageController as MemberPackageController;
use App\Http\Controllers\Member\ProfileController as MemberProfileController;
use App\Http\Controllers\Portal\SubscriptionController;
use App\Http\Middleware\EnsureCustomerIsAuthenticated;
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

Route::prefix('app')
    ->name('portal.')
    ->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('plans');
        Route::post('/start', [SubscriptionController::class, 'start'])->name('start');
        Route::get('/callback/{gateway?}', [SubscriptionController::class, 'callback'])->name('callback');
    });

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::get('routers', [RouterController::class, 'index'])->name('routers.index');
        Route::post('routers', [RouterController::class, 'store'])->name('routers.store');
        Route::get('routers/{router}', [RouterController::class, 'show'])->name('routers.show');
        Route::put('routers/{router}', [RouterController::class, 'update'])->name('routers.update');
        Route::get('recharges', [RechargeController::class, 'index'])->name('recharges.index');
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('vouchers/upload', [VoucherController::class, 'store'])->name('vouchers.upload');
        Route::post('vouchers/clear', [VoucherController::class, 'clear'])->name('vouchers.clear');
        Route::post('payments/{payment}/reverify', [PaymentController::class, 'reverify'])
            ->name('payments.reverify');
        Route::post('payments/{payment}/fulfill', [PaymentController::class, 'fulfill'])
            ->name('payments.fulfill');
        Route::post('payments/purchase', [PaymentController::class, 'purchase'])
            ->name('payments.purchase');
    });

Route::prefix('member')
    ->name('member.')
    ->group(function () {
        Route::get('login', [MemberAuthController::class, 'create'])->name('login');
        Route::post('login', [MemberAuthController::class, 'store'])->name('login.store');

        Route::middleware(EnsureCustomerIsAuthenticated::class)->group(function (): void {
            Route::post('logout', [MemberAuthController::class, 'destroy'])->name('logout');
            Route::get('/', MemberDashboardController::class)->name('dashboard');
            Route::get('connect', [MemberDashboardController::class, 'connect'])->name('connect');
            Route::get('packages', [MemberPackageController::class, 'index'])->name('packages.index');
            Route::get('messages', [MemberMessageController::class, 'index'])->name('messages.index');
            Route::get('profile', [MemberProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [MemberProfileController::class, 'update'])->name('profile.update');
            Route::get('orders', [MemberOrderController::class, 'index'])->name('orders.index');
        });
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
