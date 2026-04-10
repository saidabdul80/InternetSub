<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MikrotikUser;
use App\Models\Payment;
use App\Support\HotspotLoginUrl;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HotspotPaymentFulfillmentService
{
    public function finalizePaymentAndCreateHotspotUser(Payment $payment, MikrotikService $mikrotikService): array
    {
        $phoneNumber = $this->normalizeNigerianPhone((string) $payment->phone_number);
        $profile = $mikrotikService->profileForPlan((int) $payment->plan_type);
        $expiresAt = $this->calculatePlanExpiry((int) $payment->plan_type);
        $customer = null;

        DB::transaction(function () use ($payment, $phoneNumber, $profile, $expiresAt, $mikrotikService, &$customer): void {
            $customer = $this->ensureMemberCustomerForPhone($phoneNumber);

            $mikrotikService->provisionAccessUser(
                $phoneNumber,
                $phoneNumber,
                $profile,
                'Payment '.$payment->reference,
                (int) $payment->plan_type
            );

            MikrotikUser::query()->updateOrCreate(
                ['phone_number' => $phoneNumber],
                [
                    'username' => $phoneNumber,
                    'profile' => $profile,
                    'plan_type' => $payment->plan_type,
                    'status' => 'active',
                    'payment_id' => $payment->id,
                    'activated_at' => now(),
                    'expires_at' => $expiresAt,
                    'last_synced_at' => now(),
                ]
            );

            $payment->update([
                'status' => 'fulfilled',
                'paid_at' => $payment->paid_at ?? now(),
                'phone_number' => $phoneNumber,
            ]);
        });

        return [
            'login_url' => $this->buildHotspotLoginUrl(
                (string) $payment->access_point,
                (string) ($payment->hotspot_dst ?? ''),
                $phoneNumber,
                $phoneNumber
            ),
            'customer' => $customer,
        ];
    }

    protected function buildHotspotLoginUrl(string $hotspotReturn, string $hotspotDst, string $username, string $password): string
    {
        $baseUrl = trim($hotspotReturn) !== ''
            ? $hotspotReturn
            : (string) config('services.mikrotik.login_url');

        return HotspotLoginUrl::build($baseUrl, $hotspotDst, $username, $password);
    }

    protected function ensureMemberCustomerForPhone(string $phoneNumber): Customer
    {
        $customer = Customer::query()
            ->where(function ($query) use ($phoneNumber): void {
                $query->whereIn('phone_number', $this->phoneLookupVariants($phoneNumber))
                    ->orWhere('username', $phoneNumber);
            })
            ->orderByRaw('CASE WHEN phone_number = ? THEN 0 ELSE 1 END', [$phoneNumber])
            ->first();

        if ($customer) {
            $customer->fill([
                'username' => $phoneNumber,
                'phone_number' => $phoneNumber,
                'password' => Hash::make($phoneNumber),
                'service_type' => $customer->service_type ?: 'Hotspot',
                'status' => 'Active',
            ]);
            $customer->save();

            return $customer;
        }

        return Customer::query()->create([
            'username' => $this->resolveAvailableCustomerUsername($phoneNumber),
            'full_name' => $phoneNumber,
            'phone_number' => $phoneNumber,
            'password' => Hash::make($phoneNumber),
            'account_type' => 'Personal',
            'balance' => 0,
            'service_type' => 'Hotspot',
            'auto_renewal' => false,
            'status' => 'Active',
        ]);
    }

    protected function resolveAvailableCustomerUsername(string $baseUsername): string
    {
        if (! Customer::query()->where('username', $baseUsername)->exists()) {
            return $baseUsername;
        }

        $suffix = 2;

        do {
            $candidate = $baseUsername.'-'.$suffix;
            $suffix++;
        } while (Customer::query()->where('username', $candidate)->exists());

        return $candidate;
    }

    protected function normalizeNigerianPhone(string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($normalized, '234')) {
            $normalized = substr($normalized, 3);
        }

        if (! str_starts_with($normalized, '0')) {
            $normalized = '0'.$normalized;
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    protected function phoneLookupVariants(string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '' || strlen($digits) < 8) {
            return [$value];
        }

        $variants = [$value, $digits];

        if (str_starts_with($digits, '234')) {
            $local = substr($digits, 3);
            $variants[] = $local;
            $variants[] = '0'.$local;
            $variants[] = '+'.$digits;
        } elseif (str_starts_with($digits, '0')) {
            $withoutZero = substr($digits, 1);
            $variants[] = '234'.$withoutZero;
            $variants[] = '+234'.$withoutZero;
        }

        return array_values(array_unique(array_filter($variants)));
    }

    protected function calculatePlanExpiry(int $planType): ?Carbon
    {
        $durationMap = (array) config('services.mikrotik.plan_duration_hours', []);

        $durationHours = null;

        if (array_key_exists((string) $planType, $durationMap)) {
            $durationHours = (int) $durationMap[(string) $planType];
        } elseif (array_key_exists($planType, $durationMap)) {
            $durationHours = (int) $durationMap[$planType];
        }

        if ($durationHours === null || $durationHours <= 0) {
            return null;
        }

        return Carbon::now()->addHours($durationHours);
    }
}
