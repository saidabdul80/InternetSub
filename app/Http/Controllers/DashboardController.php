<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $paidStatuses = ['paid', 'fulfilled'];
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $yearStart = $now->copy()->startOfYear();

        $paidBaseQuery = Payment::query()
            ->whereIn('status', $paidStatuses)
            ->whereNotNull('paid_at');

        $todayCount = (clone $paidBaseQuery)->whereBetween('paid_at', [$todayStart, $todayEnd])->count();
        $todayAmount = (clone $paidBaseQuery)->whereBetween('paid_at', [$todayStart, $todayEnd])->sum('amount');
        $monthCount = (clone $paidBaseQuery)->whereBetween('paid_at', [$monthStart, $todayEnd])->count();
        $monthAmount = (clone $paidBaseQuery)->whereBetween('paid_at', [$monthStart, $todayEnd])->sum('amount');
        $yearCount = (clone $paidBaseQuery)->whereBetween('paid_at', [$yearStart, $todayEnd])->count();
        $yearAmount = (clone $paidBaseQuery)->whereBetween('paid_at', [$yearStart, $todayEnd])->sum('amount');
        $totalAmount = (clone $paidBaseQuery)->sum('amount');
        $totalPayments = Payment::query()->count();
        $paidPayments = (clone $paidBaseQuery)->count();
        $pendingPayments = Payment::query()->where('status', 'pending')->count();
        $failedPayments = Payment::query()->where('status', 'failed')->count();
        $totalSubscribers = Payment::query()
            ->whereIn('status', $paidStatuses)
            ->whereNotNull('phone_number')
            ->distinct('phone_number')
            ->count('phone_number');

        $voucherTotals = [
            'total' => Voucher::query()->count(),
            'available' => Voucher::query()->where('status', 'available')->count(),
            'reserved' => Voucher::query()->where('status', 'reserved')->count(),
            'used' => Voucher::query()->where('status', 'used')->count(),
        ];
        $expectedAmount = Voucher::query()
            ->join('plans', 'vouchers.plan_type', '=', 'plans.plan_type')
            ->sum('plans.amount');

        return Inertia::render('Dashboard', [
            'stats' => [
                'today_count' => $todayCount,
                'today_amount' => $todayAmount,
                'month_count' => $monthCount,
                'month_amount' => $monthAmount,
                'year_count' => $yearCount,
                'year_amount' => $yearAmount,
                'total_subscribers' => $totalSubscribers,
                'total_amount' => $totalAmount,
                'total_payments' => $totalPayments,
                'paid_payments' => $paidPayments,
                'pending_payments' => $pendingPayments,
                'failed_payments' => $failedPayments,
                'voucher_totals' => $voucherTotals,
                'expected_amount' => $expectedAmount,
                'currency' => 'NGN',
            ],
        ]);
    }
}
