<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Support\HotspotLoginUrl;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        /** @var Customer $customer */
        $customer = request()->attributes->get('customer');

        $activeRecharges = $customer->recharges()
            ->where('status', 'on')
            ->latest()
            ->get()
            ->map(fn ($recharge) => [
                'id' => $recharge->id,
                'plan_name' => $recharge->plan_name,
                'router_name' => $recharge->router_name,
                'method' => $recharge->method,
                'recharged_at' => $recharge->recharged_at,
                'expires_at' => $recharge->expires_at,
                'status' => $recharge->status,
            ]);

        $recentTransactions = $customer->transactions()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'invoice' => $transaction->invoice,
                'plan_name' => $transaction->plan_name,
                'price' => (string) $transaction->price,
                'method' => $transaction->method,
                'created_at' => $transaction->created_at,
                'expires_at' => $transaction->expires_at,
            ]);

        $recentPayments = collect();
        if ($customer->phone_number) {
            $recentPayments = Payment::query()
                ->where('phone_number', $customer->phone_number)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'gateway' => $payment->gateway,
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at,
                ]);
        }

        return Inertia::render('Member/Dashboard', [
            'customer' => [
                'id' => $customer->id,
                'username' => $customer->username,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'balance' => (string) $customer->balance,
                'status' => $customer->status,
                'auto_renewal' => $customer->auto_renewal,
            ],
            'active_recharges' => $activeRecharges,
            'recent_transactions' => $recentTransactions,
            'recent_payments' => $recentPayments,
            'messages' => $customer->messages()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($message) => [
                    'id' => $message->id,
                    'subject' => $message->subject,
                    'from_type' => $message->from_type,
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at,
                ]),
        ]);
    }

    public function connect(): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = request()->attributes->get('customer');

        $phoneNumber = $customer->phone_number ?: $customer->username;
        $loginUrl = HotspotLoginUrl::build(
            (string) config('services.mikrotik.login_url'),
            '',
            $phoneNumber,
            $phoneNumber
        );

        return redirect()->away($loginUrl);
    }
}
