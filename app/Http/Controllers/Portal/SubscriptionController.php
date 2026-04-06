<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartSubscriptionRequest;
use App\Models\Customer;
use App\Models\MikrotikUser;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\GatewayFactory;
use App\Services\MikrotikService;
use App\Support\HotspotLoginUrl;
use App\Support\MemberRemember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class SubscriptionController extends Controller
{
    public function index(Request $request, GatewayFactory $gatewayFactory): Response
    {
        $plans = Plan::query()
            ->orderBy('plan_type')
            ->get(['id', 'plan_type', 'name', 'amount', 'currency']);

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
            'gateways' => $gatewayFactory->getRegisteredGateways(),
            'hotspot' => [
                'return_url' => $request->string('hotspot_return')->toString(),
                'dst' => $request->string('hotspot_dst')->toString(),
                'phone' => $request->string('phone')->toString(),
            ],
        ]);
    }

    public function start(
        StartSubscriptionRequest $request,
        GatewayFactory $gatewayFactory,
        MikrotikService $mikrotikService
    ): \Symfony\Component\HttpFoundation\Response|RedirectResponse {
        $planType = $request->integer('plan_type');
        $plan = Plan::query()->where('plan_type', $planType)->firstOrFail();
        $gateway = $request->string('gateway')->toString();
        $hotspotReturn = $request->string('hotspot_return')->trim()->toString();
        $hotspotDst = $request->string('hotspot_dst')->trim()->toString();
        $phoneNumber = $this->normalizeNigerianPhone($request->string('phone_number')->toString());
        $isRenewal = $request->boolean('renew');
        $callbackUrl = route('portal.callback', ['gateway' => $gateway]);
        $redirectToMemberDashboard = $this->shouldRedirectToMemberDashboard($hotspotReturn);

        $activeUser = $this->findActiveMikrotikUser($phoneNumber, $mikrotikService);

        if ($activeUser && ! $isRenewal) {
            $customer = $this->ensureMemberCustomerForPhone($phoneNumber);
            $this->signInMemberCustomer($request, $customer);

            if ($redirectToMemberDashboard) {
                return redirect()
                    ->route('member.dashboard')
                    ->with('success', 'This phone number already has an active plan. Use Connect Internet to go online.');
            }

            return $this->redirectToExternal(
                $request,
                $this->buildHotspotLoginUrl($hotspotReturn, $hotspotDst, $phoneNumber, $phoneNumber)
            );
        }

        $verifiedPendingPayment = $this->verifyLatestPendingPayments($gatewayFactory, $phoneNumber, $planType);

        if ($verifiedPendingPayment) {
            $verifiedPendingPayment->update([
                'access_point' => $hotspotReturn,
                'hotspot_dst' => $hotspotDst === '' ? null : $hotspotDst,
                'callback_url' => $callbackUrl,
            ]);

            try {
                ['login_url' => $loginUrl, 'customer' => $customer] = $this->finalizePaymentAndCreateHotspotUser($verifiedPendingPayment, $mikrotikService);
            } catch (Throwable $exception) {
                report($exception);

                return back()->with('error', 'Payment was found but provisioning failed. Please contact support.');
            }
            try {
                $this->signInMemberCustomer($request, $customer);
            } catch (\Exception $e) {
                Log::error($e);
            }

            return $this->redirectAfterPurchase($request, $loginUrl, $redirectToMemberDashboard);
        }

        $payment = Payment::query()->create([
            'plan_id' => $plan->id,
            'plan_type' => $plan->plan_type,
            'reference' => Str::upper(Str::random(16)),
            'amount' => $plan->amount,
            'currency' => $plan->currency,
            'access_point' => $hotspotReturn,
            'hotspot_dst' => $hotspotDst === '' ? null : $hotspotDst,
            'callback_url' => $callbackUrl,
            'phone_number' => $phoneNumber,
            'status' => 'pending',
            'gateway' => $gateway,
        ]);

        try {
            $gatewayClient = $gatewayFactory->create($gateway);
            $response = $gatewayClient->initializeTransaction([
                'amount' => $plan->amount,
                'currency' => $plan->currency,
                'description' => $plan->name.' hotspot plan',
                'email' => config('services.paystack.default_email'),
                'reference' => $payment->reference,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'plan_type' => $plan->plan_type,
                    'phone_number' => $phoneNumber,
                    'hotspot_return' => $hotspotReturn,
                    'hotspot_dst' => $hotspotDst,
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $payment->update([
                'status' => 'failed',
            ]);

            return back()->with('error', 'Unable to initialize payment. Please try again.');
        }

        $gatewayReference = data_get($response, 'reference', $payment->reference);
        $authorizationUrl = (string) data_get($response, 'authorization_url', '');

        $payment->update([
            'paystack_reference' => $gatewayReference,
        ]);

        if ($authorizationUrl === '') {
            return back()->with('error', 'Payment gateway did not return an authorization URL.');
        }

        return $this->redirectToExternal($request, $authorizationUrl);
    }

    public function callback(
        Request $request,
        GatewayFactory $gatewayFactory,
        MikrotikService $mikrotikService,
        ?string $gateway = null
    ): RedirectResponse {
        $reference = $request->string('reference')->toString();

        if ($reference === '') {
            $reference = $request->string('paymentReference')->toString();
        }

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

        $gatewayName = $payment->gateway ?: ($gateway ?: 'paystack');
        $verificationReference = $payment->paystack_reference ?: $payment->reference;

        try {
            $gatewayClient = $gatewayFactory->create($gatewayName);
            $verification = $gatewayClient->verifyTransaction($verificationReference);
        } catch (Throwable $exception) {
            report($exception);

            $payment->update([
                'status' => 'failed',
            ]);

            return $this->redirectWithErrorToSubscriptionPage($payment, 'Unable to verify payment.');
        }

        if (data_get($verification, 'status') !== 'success') {
            $payment->update([
                'status' => 'failed',
            ]);

            return $this->redirectWithErrorToSubscriptionPage($payment, 'Payment was not successful.');
        }

        try {
            ['login_url' => $loginUrl, 'customer' => $customer] = $this->finalizePaymentAndCreateHotspotUser($payment, $mikrotikService);
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectWithErrorToSubscriptionPage($payment, 'Payment succeeded but provisioning failed. Please contact support.');
        }

        $this->signInMemberCustomer($request, $customer);

        return $this->redirectAfterPurchase(
            $request,
            $loginUrl,
            $this->shouldRedirectToMemberDashboard((string) $payment->access_point)
        );
    }

    protected function finalizePaymentAndCreateHotspotUser(Payment $payment, MikrotikService $mikrotikService): array
    {
        $phoneNumber = $this->normalizeNigerianPhone((string) $payment->phone_number);
        $profile = $mikrotikService->profileForPlan((int) $payment->plan_type);
        $expiresAt = $this->calculatePlanExpiry((int) $payment->plan_type);
        $customer = null;
  
        DB::transaction(function () use ($payment, $phoneNumber, $profile, $expiresAt, $mikrotikService, &$customer): void {
            try {
                $customer = $this->ensureMemberCustomerForPhone($phoneNumber);
            } catch (\Exception $e) {
                Log::error($e);
            }

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

    protected function verifyLatestPendingPayments(
        GatewayFactory $gatewayFactory,
        string $phoneNumber,
        int $planType
    ): ?Payment {
        $pendingPayments = Payment::query()
            ->where('phone_number', $phoneNumber)
            ->where('plan_type', $planType)
            ->where('status', 'pending')
            ->latest()
            ->limit(3)
            ->get();

        foreach ($pendingPayments as $payment) {
            $gateway = $payment->gateway ?: 'paystack';
            $reference = $payment->paystack_reference ?: $payment->reference;

            try {
                $gatewayClient = $gatewayFactory->create($gateway);
                $verification = $gatewayClient->verifyTransaction($reference);
            } catch (RuntimeException) {
                continue;
            } catch (Throwable) {
                continue;
            }

            if (data_get($verification, 'status') !== 'success') {
                continue;
            }

            $payment->update([
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            return $payment;
        }

        return null;
    }

    protected function findActiveMikrotikUser(string $phoneNumber, MikrotikService $mikrotikService): ?MikrotikUser
    {
        $mikrotikUser = MikrotikUser::query()
            ->where('phone_number', $phoneNumber)
            ->latest()
            ->first();

        if (! $mikrotikUser) {
            return null;
        }

        try {
            $routerStatus = $mikrotikService->getHotspotUserUsageStatus($phoneNumber);

            if ($routerStatus !== null) {
                $isActive = (bool) ($routerStatus['exists'] ?? false) && (bool) ($routerStatus['active'] ?? false);

                $mikrotikUser->forceFill([
                    'status' => $isActive ? 'active' : 'inactive',
                    'last_synced_at' => now(),
                ])->save();

                return $isActive ? $mikrotikUser : null;
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to confirm MikroTik hotspot user status from router.', [
                'phone_number' => $phoneNumber,
                'message' => $exception->getMessage(),
            ]);
        }

        if ($mikrotikUser->status !== 'active') {
            return null;
        }

        if ($mikrotikUser->expires_at !== null && $mikrotikUser->expires_at->isPast()) {
            return null;
        }

        return $mikrotikUser;
    }

    protected function buildHotspotLoginUrl(string $hotspotReturn, string $hotspotDst, string $username, string $password): string
    {
        $baseUrl = trim($hotspotReturn) !== ''
            ? $hotspotReturn
            : (string) config('services.mikrotik.login_url');

        return HotspotLoginUrl::build($baseUrl, $hotspotDst, $username, $password);
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

    protected function redirectWithErrorToSubscriptionPage(Payment $payment, string $message): RedirectResponse
    {
        return redirect()
            ->route('portal.plans', [
                'hotspot_return' => $payment->access_point !== '' ? $payment->access_point : null,
                'hotspot_dst' => $payment->hotspot_dst,
                'phone' => $payment->phone_number,
            ])
            ->with('error', $message);
    }

    protected function redirectToExternal(
        Request $request,
        string $url
    ): \Symfony\Component\HttpFoundation\Response|RedirectResponse {
        if ($request->header('X-Inertia')) {
            return Inertia::location($url);
        }

        return redirect()->away($url);
    }

    protected function ensureMemberCustomerForPhone(string $phoneNumber): Customer
    {
        $customer = Customer::query()
            ->where('phone_number', $phoneNumber)
            ->first();

        if ($customer) {
            $customer->fill([
                'phone_number' => $phoneNumber,
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

    protected function signInMemberCustomer(Request $request, Customer $customer): void
    {
        $request->session()->regenerate();
        $request->session()->put('customer_id', $customer->id);
        $customer->update(['last_login_at' => now()]);
        Cookie::queue(MemberRemember::cookie($customer, $request->isSecure()));
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

    protected function shouldRedirectToMemberDashboard(string $hotspotReturn): bool
    {
        return trim($hotspotReturn) === '';
    }

    protected function redirectAfterPurchase(
        Request $request,
        string $loginUrl,
        bool $redirectToMemberDashboard
    ): \Symfony\Component\HttpFoundation\Response|RedirectResponse {
        if ($redirectToMemberDashboard) {
            return redirect()
                ->route('member.dashboard')
                ->with('success', 'Plan activated. Open Connect Internet when you want to go online.');
        }

        return $this->redirectToExternal($request, $loginUrl);
    }
}
