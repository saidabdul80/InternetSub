<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetLastVoucherRequest;
use App\Models\Payment;
use App\Models\Voucher;
use App\Services\GatewayFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VoucherController extends Controller
{
    public function last(GetLastVoucherRequest $request, GatewayFactory $gatewayFactory): JsonResponse
    {
        $phoneNumber = $request->string('phone_number')->toString();
        $normalized = preg_replace('/\D+/', '', $phoneNumber);
        $planType = $request->input('plan_type');

        if ($normalized === '') {
            return response()->json([
                'message' => 'Unable to locate a voucher for that number.',
            ], 404);
        }

        $local = $normalized;
        if (str_starts_with($normalized, '234')) {
            $local = substr($normalized, 3);
        }
        $local = ltrim($local, '0');
        $local = '0' . $normalized;
        $payment = Payment::query()
            ->with('voucher')
            ->where('status', 'fulfilled')
            ->when(is_numeric($planType), function ($query) use ($planType): void {
                $query->where('plan_type', (int) $planType);
            })
            ->whereNotNull('paid_at')
            ->where(function ($query) use ($phoneNumber, $normalized, $local): void {
                $query->where('phone_number', $phoneNumber)
                    ->orWhere('phone_number', 'like', '%'.$normalized.'%')
                    ->orWhere('phone_number', 'like', '%'.$local.'%');
            })
            ->latest('paid_at')
            ->first();

        if (! $payment || ! $payment->voucher) {
            $foundUnfulfilledPayment = null;
            $last3PendingPayments = Payment::query()
                ->where('status', 'pending')
                ->when(is_numeric($planType), function ($query) use ($planType): void {
                    $query->where('plan_type', (int) $planType);
                })
                ->where(function ($query) use ($phoneNumber, $normalized, $local): void {
                    $query->where('phone_number', $phoneNumber)
                        ->orWhere('phone_number', 'like', '%'.$normalized.'%')
                        ->orWhere('phone_number', 'like', '%'.$local.'%');
                })
                ->latest()
                ->limit(3)
                ->get();

            foreach ($last3PendingPayments as $payment) {
                $reference = $payment->paystack_reference ?? $payment->reference;
                $gateway = $payment->gateway ?: 'paystack';
                $gatewayClient = $gatewayFactory->create($gateway);
                try {
                    $verification = $gatewayClient->verifyTransaction($reference);
                    if (data_get($verification, 'status') === 'success') {
                        $payment->update([
                            'paystack_reference' => data_get($verification, 'reference', $reference),
                        ]);
                        $foundUnfulfilledPayment = $payment;
                        break;
                    }
                } catch (RuntimeException $exception) {
                    continue;
                }
            }
            if ($foundUnfulfilledPayment) {
                $voucher = $this->finalizeSuccessfulPayment($foundUnfulfilledPayment);
                if ($voucher) {
                    return response()->json([
                        'voucher_code' => $voucher->code,
                        'paid_at' => $foundUnfulfilledPayment->paid_at,
                    ]);
                }

                return response()->json([
                    'message' => 'Payment verified but no voucher is available.',
                ], 409);
            }

            return response()->json([
                'message' => 'No fulfilled voucher found yet.',
            ], 404);
        }

        return response()->json([
            'voucher_code' => $payment->voucher->code,
            'paid_at' => $payment->paid_at,
        ]);

    }

    protected function finalizeSuccessfulPayment(Payment $payment): ?Voucher
    {
        $voucher = null;

        return DB::transaction(function () use ($payment, &$voucher): ?Voucher {
            $payment->update([
                'status' => 'paid',
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            $voucher = Voucher::query()
                ->where('payment_id', $payment->id)
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

                return $voucher;
            }

            return null;
        });
    }
}
