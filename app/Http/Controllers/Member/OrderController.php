<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Plan;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        /** @var Customer $customer */
        $customer = request()->attributes->get('customer');

        $transactions = $customer->transactions()
            ->latest()
            ->get()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'invoice' => $transaction->invoice,
                'plan_name' => $transaction->plan_name,
                'price' => (string) $transaction->price,
                'method' => $transaction->method,
                'router_name' => $transaction->router_name,
                'service_type' => $transaction->service_type,
                'recharged_at' => $transaction->recharged_at,
                'expires_at' => $transaction->expires_at,
            ]);

        $payments = collect();
        if ($customer->phone_number) {
            $payments = Payment::query()
                ->with('plan')
                ->where('phone_number', $customer->phone_number)
                ->latest()
                ->get()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'gateway' => $payment->gateway,
                    'plan_name' => $payment->plan?->name,
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at,
                ]);
        }

        return Inertia::render('Member/Orders', [
            'customer' => [
                'id' => $customer->id,
                'username' => $customer->username,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'balance' => (string) $customer->balance,
                'status' => $customer->status,
            ],
            'plans' => Plan::query()
                ->orderBy('plan_type')
                ->get(['id', 'plan_type', 'name', 'amount', 'currency']),
            'transactions' => $transactions,
            'payments' => $payments,
        ]);
    }
}
