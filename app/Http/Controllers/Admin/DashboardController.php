<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Voucher;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'counts' => [
                'users' => User::query()->count(),
                'plans' => Plan::query()->count(),
                'subscriptions' => Subscription::query()->count(),
                'payments' => Payment::query()->count(),
                'vouchers' => Voucher::query()->count(),
            ],
        ]);
    }
}
