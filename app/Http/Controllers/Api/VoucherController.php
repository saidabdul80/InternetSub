<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetLastVoucherRequest;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    public function last(GetLastVoucherRequest $request): JsonResponse
    {
        $phoneNumber = $request->string('phone_number')->toString();
        $normalized = preg_replace('/\D+/', '', $phoneNumber);

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

        $payment = Payment::query()
            ->with('voucher')
            ->where('status', 'fulfilled')
            ->where('plan_type', $request->plan_type)
            ->whereNotNull('paid_at')
            ->where(function ($query) use ($phoneNumber, $normalized, $local): void {
                $query->where('phone_number', $phoneNumber)
                    ->orWhere('phone_number', 'like', '%'.$normalized.'%')
                    ->orWhere('phone_number', 'like', '%'.$local.'%');
            })
            ->latest('paid_at')
            ->first();

        if (! $payment || ! $payment->voucher) {
            return response()->json([
                'message' => 'No fulfilled voucher found yet.',
            ], 404);
        }

        return response()->json([
            'voucher_code' => $payment->voucher->code,
            'paid_at' => $payment->paid_at,
        ]);
    }
}
