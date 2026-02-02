<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaystackCallbackController;
use App\Http\Controllers\Api\VoucherController;
use Illuminate\Support\Facades\Route;

Route::post('/pay', [PaymentController::class, 'store'])->name('api.pay');
Route::get('/paystack/callback', PaystackCallbackController::class)->name('api.paystack.callback');
Route::post('/voucher/last', [VoucherController::class, 'last'])->name('api.voucher.last');
Route::post('/webhook/{gateway?}', [PaystackCallbackController::class, 'handleWebhook'])->name('webhook.paystack');
