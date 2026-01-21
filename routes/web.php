<?php

use App\Http\Controllers\Admin\VoucherController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('vouchers/upload', [VoucherController::class, 'store'])->name('vouchers.upload');
    });

require __DIR__.'/settings.php';
