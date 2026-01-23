<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Voucher;
use App\Services\PaystackClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PaystackCallbackController extends Controller
{
    public function __invoke(Request $request, PaystackClient $paystackClient): RedirectResponse
    {

        $reference = $request->string('reference')->toString();

        if ($reference === '') {
            abort(400, 'Missing payment reference.');
        }

        $payment = Payment::query()
            ->where('reference', $reference)
            ->orWhere('paystack_reference', $reference)
            ->first();

        if (! $payment) {
            abort(404, 'Payment not found.');
        }

        try {
            $verification = $paystackClient->verifyTransaction($reference);
        } catch (RuntimeException $exception) {
            report($exception);

            $payment->update([
                'status' => 'failed',
            ]);

            abort(502, 'Unable to verify payment.');
        }

        if (data_get($verification, 'status') !== 'success') {
            $payment->update([
                'status' => 'failed',
            ]);

            abort(409, 'Payment was not successful.');
        }

        $voucher = null;
        $reservationWindow = Carbon::now()->subMinutes(15);

        DB::transaction(function () use ($payment, $reservationWindow, &$voucher): void {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $voucher = Voucher::query()
                ->where('payment_id', $payment->id)
                ->whereIn('status', ['reserved', 'used'])
                ->lockForUpdate()
                ->first();

            if ($voucher && $voucher->status === 'reserved') {
                $voucher->update([
                    'status' => 'used',
                    'reserved_at' => null,
                    'used_at' => now(),
                ]);
            }

            if ($voucher) {
                $payment->update([
                    'status' => 'fulfilled',
                ]);
            }

            if (! $voucher) {
                $voucher = Voucher::query()
                    ->where('plan_type', $payment->plan_type)
                    ->where(function ($query) use ($reservationWindow): void {
                        $query->where('status', 'available')
                            ->orWhere(function ($innerQuery) use ($reservationWindow): void {
                                $innerQuery->where('status', 'reserved')
                                    ->where('reserved_at', '<=', $reservationWindow);
                            });
                    })
                    ->lockForUpdate()
                    ->first();

                if ($voucher) {
                    $voucher->update([
                        'status' => 'used',
                        'payment_id' => $payment->id,
                        'reserved_at' => null,
                        'used_at' => now(),
                    ]);

                    $payment->update([
                        'status' => 'fulfilled',
                    ]);
                }
            }
        });

        if (! $voucher) {
            abort(409, 'No vouchers available for this plan.');
        }

        $redirectUrl = $this->buildAccessPointUrl($payment->access_point, $voucher->code);
        $this->sendVoucherSms($payment, $voucher);

        return redirect()->away($redirectUrl);
    }

    protected function buildAccessPointUrl(string $accessPoint, string $code): string
    {
        $separator = str_contains($accessPoint, '?') ? '&' : '?';

        return $accessPoint.$separator.'voucher='.urlencode($code);
    }

    protected function sendVoucherSms(Payment $payment, Voucher $voucher): void
    {
        $phoneNumber = (string) $payment->phone_number;
        $recipient = preg_replace('/\D+/', '', $phoneNumber);

        if ($recipient === '') {
            return;
        }

        if (! str_starts_with($recipient, '234')) {
            $recipient = ltrim($recipient, '0');
            $recipient = '234'.$recipient;
        }

        $config = config('services.sms');
        $baseUrl = (string) data_get($config, 'base_url');
        $username = (string) data_get($config, 'email_address');
        $apiKey = (string) data_get($config, 'api_key');
        $senderName = (string) data_get($config, 'sender_name');

        if ($baseUrl === '' || $username === '' || $apiKey === '' || $senderName === '') {
            return;
        }

        $messageText = sprintf('Your GoodNews Wi-Fi voucher code is %s.', $voucher->code);

        try {
            Http::get($baseUrl, [
                'username' => $username,
                'apikey' => $apiKey,
                'sender' => $senderName,
                'messagetext' => $messageText,
                'flash' => 0,
                'recipients' => $recipient,
                'dndsender' => 1,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
