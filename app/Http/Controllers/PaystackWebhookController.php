<?php

namespace App\Http\Controllers;

use App\Jobs\ProvisionHotspotAccess;
use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystackService): JsonResponse
    {
        if (! $paystackService->isSignatureValid($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        if ($request->input('event') !== 'charge.success') {
            return response()->json(['status' => 'ignored']);
        }

        $reference = $request->input('data.reference');

        if (! $reference) {
            return response()->json(['message' => 'Missing reference.'], 422);
        }

        $payment = Payment::query()->where('reference', $reference)->first();

        if ($payment === null) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        if ($payment->status === 'success') {
            return response()->json(['status' => 'ok']);
        }

        $verification = $paystackService->verifyTransaction($reference);
        $data = Arr::get($verification, 'data');

        if (! Arr::get($verification, 'status') || Arr::get($data, 'status') !== 'success') {
            $payment->update([
                'status' => 'failed',
                'payload' => $data,
            ]);

            Log::warning('Paystack webhook verification failed.', [
                'payment_id' => $payment->id,
                'reference' => $reference,
            ]);

            return response()->json(['status' => 'failed'], 422);
        }

        $payment->update([
            'status' => 'success',
            'paid_at' => now(),
            'payload' => $data,
        ]);

        ProvisionHotspotAccess::dispatch($payment->id);

        return response()->json(['status' => 'ok']);
    }
}
