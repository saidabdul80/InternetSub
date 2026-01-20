<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\Portal\CheckoutController;
use App\Http\Controllers\Portal\PaymentController as PortalPaymentController;
use App\Http\Controllers\Portal\PlanController as PortalPlanController;

Route::get('/', [PortalPlanController::class, 'index'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

Route::get('/plans', [PortalPlanController::class, 'index'])->name('plans.index');
Route::get('/plans/phone-check', [PortalPlanController::class, 'checkPhone'])
    ->middleware('throttle:30,1')
    ->name('plans.phone-check');
Route::post('/plans/{plan}/checkout', [CheckoutController::class, 'store'])
    ->name('plans.checkout');
Route::get('/checkout/{payment}', [CheckoutController::class, 'show'])
    ->middleware('auth')
    ->name('checkout.show');
Route::post('/checkout/{payment}/confirm', [PortalPaymentController::class, 'confirm'])
    ->middleware('auth')
    ->name('checkout.confirm');
Route::get('/checkout/{payment}/return', [PortalPaymentController::class, 'handleReturn'])
    ->name('checkout.return');

Route::post('/webhooks/paystack', PaystackWebhookController::class)->name('webhooks.paystack');

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::resource('plans', PlanController::class);
    });
