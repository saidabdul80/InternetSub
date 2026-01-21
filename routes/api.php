<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaystackCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/pay', [PaymentController::class, 'store'])->name('api.pay');
Route::get('/paystack/callback', PaystackCallbackController::class)->name('api.paystack.callback');
