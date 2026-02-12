<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Voucher;
use App\Services\GatewayFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentController extends Controller
{
    public function reverify(Payment $payment, GatewayFactory $gatewayFactory): RedirectResponse
    {
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
        }

        if (data_get($verification, 'status') !== 'success') {
            return back()->with('error', 'Payment was not successful on the selected gateway.');
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

    public function purchase(Request $request, GatewayFactory $gatewayFactory): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^\\+?\\d{8,20}$/'],
            'plan_type' => ['required', 'integer', 'exists:plans,plan_type'],
            'payment_method' => ['required', 'string', 'in:manual,paystack,monnify'],
            'email' => ['nullable', 'email'],
        ]);

        $plan = Plan::query()->where('plan_type', $data['plan_type'])->firstOrFail();
        $reservationWindow = Carbon::now()->subMinutes(15);
        $accessPoint = route('admin.vouchers.index');
        $callbackUrl = route('api.callback');
        $reference = Str::random(15);

        if ($data['payment_method'] === 'manual') {
            $payment = null;
            $voucher = null;

            try {
                DB::transaction(function () use ($plan, $reference, $accessPoint, $callbackUrl, $data, $reservationWindow, &$payment, &$voucher): void {
                    $payment = Payment::query()->create([
                        'plan_id' => $plan->id,
                        'plan_type' => $plan->plan_type,
                        'reference' => $reference,
                        'amount' => $plan->amount,
                        'currency' => $plan->currency,
                        'access_point' => $accessPoint,
                        'callback_url' => $callbackUrl,
                        'phone_number' => $data['phone_number'],
                        'status' => 'paid',
                        'paid_at' => now(),
                        'gateway' => 'manual',
                    ]);

                    $voucher = Voucher::query()
                        ->where('plan_type', $plan->plan_type)
                        ->where(function ($query) use ($reservationWindow): void {
                            $query->where('status', 'available')
                                ->orWhere(function ($innerQuery) use ($reservationWindow): void {
                                    $innerQuery->where('status', 'reserved')
                                        ->where('reserved_at', '<=', $reservationWindow);
                                });
                        })
                        ->lockForUpdate()
                        ->first();

                    if (! $voucher) {
                        throw new RuntimeException('no_voucher_available');
                    }

                    $voucher->update([
                        'status' => 'used',
                        'payment_id' => $payment->id,
                        'reserved_at' => null,
                        'used_at' => now(),
                    ]);

                    $payment->update([
                        'status' => 'fulfilled',
                    ]);
                });
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() === 'no_voucher_available') {
                    return back()->with('error', 'No vouchers available for this plan.');
                }

                throw $exception;
            }

            if (! $voucher) {
                return back()->with('error', 'No vouchers available for this plan.');
            }

            return back()->with('success', 'Voucher assigned: '.$voucher->code);
        }

        $payment = null;
        $voucher = null;
        $gateway = $data['payment_method'];
        $gatewayClient = $gatewayFactory->create($gateway);

        try {
            DB::transaction(function () use ($plan, $reference, $accessPoint, $callbackUrl, $data, $reservationWindow, &$payment, &$voucher, $gateway): void {
                $payment = Payment::query()->create([
                    'plan_id' => $plan->id,
                    'plan_type' => $plan->plan_type,
                    'reference' => $reference,
                    'amount' => $plan->amount,
                    'currency' => $plan->currency,
                    'access_point' => $accessPoint,
                    'callback_url' => $callbackUrl,
                    'phone_number' => $data['phone_number'],
                    'status' => 'pending',
                    'gateway' => $gateway,
                ]);

                $voucher = Voucher::query()
                    ->where('plan_type', $plan->plan_type)
                    ->where(function ($query) use ($reservationWindow): void {
                        $query->where('status', 'available')
                            ->orWhere(function ($innerQuery) use ($reservationWindow): void {
                                $innerQuery->where('status', 'reserved')
                                    ->where('reserved_at', '<=', $reservationWindow);
                            });
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $voucher) {
                    throw new RuntimeException('no_voucher_available');
                }

                $voucher->update([
                    'status' => 'reserved',
                    'payment_id' => $payment->id,
                    'reserved_at' => now(),
                ]);
            });
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'no_voucher_available') {
                return back()->with('error', 'No vouchers available for this plan.');
            }

            throw $exception;
        }

        try {
            $response = $gatewayClient->initializeTransaction([
                'amount' => $plan->amount,
                'currency' => $plan->currency,
                'description' => $plan->name ?? 'Voucher purchase',
                'email' => $data['email'] ?? config('services.paystack.default_email'),
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'plan_type' => $plan->plan_type,
                    'access_point' => $accessPoint,
                    'phone_number' => $data['phone_number'],
                ],
            ]);
        } catch (RuntimeException $exception) {
            report($exception);

            $payment->update([
                'status' => 'failed',
            ]);

            if ($voucher) {
                $voucher->update([
                    'status' => 'available',
                    'payment_id' => null,
                    'reserved_at' => null,
                ]);
            }

            return back()->with('error', 'Unable to initialize payment with the selected gateway.');
        }

        $gatewayReference = data_get($response, 'reference', $reference);
        if ($gateway === 'monnify') {
            $payment->update([
                'reference' => $gatewayReference,
            ]);
        } else {
            $payment->update([
                'paystack_reference' => $gatewayReference,
            ]);
        }

        $authorizationUrl = (string) data_get($response, 'authorization_url');

        if ($authorizationUrl === '') {
            return back()->with('error', 'Payment gateway did not return an authorization URL.');
        }

        return redirect()->away($authorizationUrl);
    }
}
