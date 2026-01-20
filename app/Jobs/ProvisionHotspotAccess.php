<?php

namespace App\Jobs;

use App\Models\MikrotikRouter;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Voucher;
use App\Services\MikrotikService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProvisionHotspotAccess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $paymentId)
    {
    }

   public function handle(MikrotikService $mikrotikService): void
    {
        $payment = Payment::query()
            ->with(['plan', 'user'])
            ->find($this->paymentId);

        if ($payment === null || $payment->status !== 'success') {
            return;
        }

        $subscription = Subscription::query()
            ->where('payment_id', $payment->id)
            ->first();

        if ($subscription === null || $payment->plan === null) {
            return;
        }

        // First try to get router from database
        $router = MikrotikRouter::query()
            ->where('is_active', true)
            ->first();

        // If no router in DB, check if .env configuration exists
        $useEnvConfig = false;
        if ($router === null) {
            if (empty(config('hotspot.mikrotik.host')) || 
                empty(config('hotspot.mikrotik.username')) || 
                empty(config('hotspot.mikrotik.password'))) {
                Log::error('No Mikrotik router configured for provisioning.', [
                    'payment_id' => $payment->id,
                ]);
                $this->fail('No Mikrotik router configured');
                return;
            }
            $useEnvConfig = true;
        }

        $voucherCode = $this->generateVoucherCode();
        $expiresAt = now()->addMinutes($payment->plan->duration_minutes);

        $voucherData = [
            'user_id' => $payment->user_id,
            'plan_id' => $payment->plan_id,
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'code' => $voucherCode,
            'username' => $voucherCode,
            'password' => $voucherCode,
            'status' => 'active',
            'payment_reference' => $payment->reference,
        ];

        // Only set router_id if we have a router from DB
        if ($router !== null) {
            $voucherData['mikrotik_router_id'] = $router->id;
        }

        $voucher = Voucher::create($voucherData);

        try {
            // Create client based on configuration source
            $client = $router === null ? $mikrotikService : new MikrotikService($router);
            
            $client->upsertHotspotUser(
                $voucher->username,
                $voucher->password,
                $payment->plan->mikrotik_profile,
                $payment->reference,
            );

            $subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            $voucher->update([
                'activated_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            Log::info('Voucher provisioned for hotspot access.', [
                'voucher_id' => $voucher->id,
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'router_source' => $router ? 'database' : 'env',
            ]);
        } catch (Throwable $exception) {
            $voucher->update([
                'status' => 'failed',
            ]);

            $subscription->update([
                'status' => 'failed',
            ]);

            Log::error('Failed to provision hotspot access.', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            
            // Re-throw to mark job as failed
            throw $exception;
        }
    }

    private function generateVoucherCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Voucher::query()->where('code', $code)->exists());

        return $code;
    }

}
