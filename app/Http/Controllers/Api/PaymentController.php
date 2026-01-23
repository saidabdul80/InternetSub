<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Voucher;
use App\Services\PaystackClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentController extends Controller
{
    public function store(CreatePaymentRequest $request, PaystackClient $paystackClient): JsonResponse
    {
        $planType = $request->integer('plan_type');
        $plan = Plan::query()->where('plan_type', $planType)->firstOrFail();

        $reference = Str::random(15);
        $callbackUrl = route('api.paystack.callback');
        $accessPoint = (string) $request->string('url');
        $phoneNumber = $request->string('phone_number')->toString();

        $payment = null;
        $voucher = null;
        $reservationWindow = Carbon::now()->subMinutes(15);

        try {
            DB::transaction(function () use ($plan, $reference, $accessPoint, $callbackUrl, $phoneNumber, $reservationWindow, &$payment, &$voucher): void {
                $payment = Payment::query()->create([
                    'plan_id' => $plan->id,
                    'plan_type' => $plan->plan_type,
                    'reference' => $reference,
                    'amount' => $plan->amount,
                    'currency' => $plan->currency,
                    'access_point' => $accessPoint,
                    'callback_url' => $callbackUrl,
                    'phone_number' => $phoneNumber,
                    'status' => 'pending',
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
                return response()->json([
                    'message' => 'No vouchers available for this plan.',
                ], 409);
            }

            throw $exception;
        }

        try {
            $response = $paystackClient->initializeTransaction([
                'amount' => $plan->amount,
                'email' => $request->input('email', config('services.paystack.default_email')),
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'plan_type' => $plan->plan_type,
                    'access_point' => $accessPoint,
                    'phone_number' => $phoneNumber,
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

            return response()->json([
                'message' => 'Unable to initialize payment.',
            ], 502);
        }

        $payment->update([
            'paystack_reference' => data_get($response, 'reference', $reference),
        ]);

        return response()->json([
            'authorization_url' => data_get($response, 'authorization_url'),
            'reference' => $payment->reference,
        ]);
    }
}
