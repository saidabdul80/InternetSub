<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionHotspotAccess;
use App\Models\Payment;
use App\Models\Voucher;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function confirm(Request $request, Payment $payment, PaystackService $paystackService): JsonResponse
    {
        abort_unless($payment->user_id === auth()->id(), 403);

        if ($payment->status === 'success') {
            return $this->successResponse($payment);
        }

        $verification = $paystackService->verifyTransaction($payment->reference);
        $data = Arr::get($verification, 'data');
        $transactionStatus = Arr::get($data, 'status');

        if (! Arr::get($verification, 'status') || $transactionStatus !== 'success') {
            $payment->update([
                'status' => 'failed',
                'payload' => $data,
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Payment verification failed.',
            ], 422);
        }

        $amount = (int) Arr::get($data, 'amount');
        $currency = Arr::get($data, 'currency', 'NGN');

        if ($amount !== $payment->amount_kobo || $currency !== $payment->currency) {
            Log::warning('Paystack amount mismatch.', [
                'payment_id' => $payment->id,
                'expected_amount' => $payment->amount_kobo,
                'actual_amount' => $amount,
                'expected_currency' => $payment->currency,
                'actual_currency' => $currency,
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Payment amount mismatch.',
            ], 422);
        }

        $payment->update([
            'status' => 'success',
            'paid_at' => now(),
            'payload' => $data,
        ]);

        ProvisionHotspotAccess::dispatchSync($payment->id);

        return $this->successResponse($payment);
    }

    public function handleReturn(
        Request $request,
        Payment $payment,
        PaystackService $paystackService
    ): RedirectResponse {
        $reference = (string) $request->query('reference');

        if ($reference === '' || $reference !== $payment->reference) {
            return redirect()->route('plans.index')->with('error', 'Invalid payment reference.');
        }

        if ($payment->status !== 'success') {
            $verification = $paystackService->verifyTransaction($payment->reference);
            $data = Arr::get($verification, 'data');
            $transactionStatus = Arr::get($data, 'status');

            if (! Arr::get($verification, 'status') || $transactionStatus !== 'success') {
                return redirect()->route('plans.index')->with('error', 'Payment verification failed.');
            }

            $payment->update([
                'status' => 'success',
                'paid_at' => now(),
                'payload' => $data,
            ]);

            ProvisionHotspotAccess::dispatchSync($payment->id);
        }

        $voucher = Voucher::query()
            ->where('payment_id', $payment->id)
            ->latest()
            ->first();

        if ($voucher !== null) {
            return redirect()->to($this->buildLoginUrl($voucher->username, $voucher->password));
        }

        return redirect()->route('plans.index')->with('status', 'Payment received. Access is being prepared.');
    }

    private function successResponse(Payment $payment): JsonResponse
    {
        $voucher = Voucher::query()
            ->where('payment_id', $payment->id)
            ->latest()
            ->first();

        return response()->json([
            'status' => 'success',
            'voucher' => $voucher?->code,
            'redirect_url' => $voucher === null
                ? null
                : $this->buildLoginUrl($voucher->username, $voucher->password),
        ]);
    }

    private function buildLoginUrl(string $username, string $password): string
    {
        $baseUrl = rtrim((string) config('hotspot.portal.login_url'), '/');

        return sprintf('%s/login?username=%s&password=%s', $baseUrl, $username, $password);
    }
}
