<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartSubscriptionRequest;
use App\Models\MikrotikUser;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\GatewayFactory;
use App\Services\MikrotikService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        if ($hotspotReturn === '') {
            return back()->with('error', 'Missing hotspot return URL.');
        }

        $activeUser = $this->findActiveMikrotikUser($phoneNumber);

        if ($activeUser && ! $isRenewal) {
            $loginUrl = $this->buildHotspotLoginUrl($hotspotReturn, $hotspotDst, $phoneNumber, $phoneNumber);

            return $this->redirectToExternal($request, $loginUrl);
        }

        $verifiedPendingPayment = $this->verifyLatestPendingPayments($gatewayFactory, $phoneNumber, $planType);

        if ($verifiedPendingPayment) {
            $verifiedPendingPayment->update([
                'access_point' => $hotspotReturn,
                'hotspot_dst' => $hotspotDst === '' ? null : $hotspotDst,
                'callback_url' => $callbackUrl,
            ]);

            try {
                $loginUrl = $this->finalizePaymentAndCreateHotspotUser($verifiedPendingPayment, $mikrotikService);
            } catch (Throwable $exception) {
                report($exception);

                return back()->with('error', 'Payment was found but provisioning failed. Please contact support.');
            }

            return $this->redirectToExternal($request, $loginUrl);
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
            $loginUrl = $this->finalizePaymentAndCreateHotspotUser($payment, $mikrotikService);
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectWithErrorToSubscriptionPage($payment, 'Payment succeeded but provisioning failed. Please contact support.');
        }

        return redirect()->away($loginUrl);
    }

    protected function finalizePaymentAndCreateHotspotUser(Payment $payment, MikrotikService $mikrotikService): string
    {
        $phoneNumber = $this->normalizeNigerianPhone((string) $payment->phone_number);
        $profile = $mikrotikService->profileForPlan((int) $payment->plan_type);
        $expiresAt = $this->calculatePlanExpiry((int) $payment->plan_type);
  
        DB::transaction(function () use ($payment, $phoneNumber, $profile, $expiresAt, $mikrotikService): void {
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

        return $this->buildHotspotLoginUrl(
            (string) $payment->access_point,
            (string) ($payment->hotspot_dst ?? ''),
            $phoneNumber,
            $phoneNumber
        );
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

    protected function findActiveMikrotikUser(string $phoneNumber): ?MikrotikUser
    {
        return MikrotikUser::query()
            ->where('phone_number', $phoneNumber)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    protected function buildHotspotLoginUrl(string $hotspotReturn, string $hotspotDst, string $username, string $password): string
    {
        $params = [
            'username' => $username,
            'phone' => $username,
            'password' => $password,
            'autologin' => 1,
        ];

        if ($hotspotDst !== '') {
            $params['dst'] = $hotspotDst;
        }

        $separator = str_contains($hotspotReturn, '?') ? '&' : '?';

        return $hotspotReturn.$separator.http_build_query($params);
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
                'hotspot_return' => $payment->access_point,
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
}
