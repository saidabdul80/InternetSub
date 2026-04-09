<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\GatewayFactory;
use App\Services\HotspotPaymentFulfillmentService;
use App\Services\MikrotikService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Throwable;
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
                    'can_reverify' => $payment->status === 'pending' && ($payment->gateway ?: 'paystack') !== 'manual',
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

    public function reverify(
        Payment $payment,
        GatewayFactory $gatewayFactory,
        MikrotikService $mikrotikService,
        HotspotPaymentFulfillmentService $paymentFulfillmentService
    ): RedirectResponse {
        /** @var Customer $customer */
        $customer = request()->attributes->get('customer');

        if (! $this->paymentBelongsToCustomer($payment, $customer)) {
            abort(404);
        }

        if ($payment->status === 'fulfilled') {
            return back()->with('success', 'This payment has already been activated.');
        }

        $gateway = $payment->gateway ?: 'paystack';
        if ($gateway === 'manual') {
            return back()->with('error', 'Manual payments cannot be reverified.');
        }

        $reference = $payment->paystack_reference ?? $payment->reference;
        $gatewayClient = $gatewayFactory->create($gateway);

        try {
            $verification = $gatewayClient->verifyTransaction($reference);
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->with('error', 'Unable to verify payment with the selected gateway.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Unable to verify payment right now. Please try again.');
        }

        if (data_get($verification, 'status') !== 'success') {
            return back()->with('error', 'Payment is still pending on the selected gateway.');
        }

        try {
            $paymentFulfillmentService->finalizePaymentAndCreateHotspotUser($payment, $mikrotikService);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Payment was successful but activation failed. Please contact support.');
        }

        return back()->with('success', 'Payment verified and plan activated. You can now connect to the internet.');
    }

    protected function paymentBelongsToCustomer(Payment $payment, Customer $customer): bool
    {
        $customerPhone = $customer->phone_number ?: $customer->username;

        return $this->normalizeNigerianPhone((string) $payment->phone_number) === $this->normalizeNigerianPhone((string) $customerPhone);
    }

    protected function normalizeNigerianPhone(string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($normalized, '234')) {
            $normalized = substr($normalized, 3);
        }

        if ($normalized !== '' && ! str_starts_with($normalized, '0')) {
            $normalized = '0'.$normalized;
        }

        return $normalized;
    }
}
