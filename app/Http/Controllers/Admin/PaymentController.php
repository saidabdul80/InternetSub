<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaystackClient;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class PaymentController extends Controller
{
    public function reverify(Payment $payment, PaystackClient $paystackClient): RedirectResponse
    {
        $reference = $payment->paystack_reference ?? $payment->reference;

        try {
            $verification = $paystackClient->verifyTransaction($reference);
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->with('error', 'Unable to verify payment with Paystack.');
        }

        if (data_get($verification, 'status') !== 'success') {
            return back()->with('error', 'Payment was not successful on Paystack.');
        }

        $payment->update([
            'status' => $payment->status === 'fulfilled' ? 'fulfilled' : 'paid',
            'paid_at' => $payment->paid_at ?? now(),
        ]);

        return back()->with('success', 'Payment verified as successful.');
    }

    public function fulfill(Payment $payment): RedirectResponse
    {
        $payment->update([
            'status' => 'fulfilled',
            'paid_at' => $payment->paid_at ?? now(),
        ]);

        return back()->with('success', 'Payment marked as fulfilled.');
    }
}
