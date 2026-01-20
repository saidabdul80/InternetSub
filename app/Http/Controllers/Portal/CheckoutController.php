<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function store(
        StoreCheckoutRequest $request,
        Plan $plan,
        PaystackService $paystackService,
    ): RedirectResponse
    {
        $user = $request->user() ?? $this->resolveCheckoutUser($request);
        $paystackEmail = $this->resolvePaystackEmail($user);
  
        $payment = DB::transaction(function () use ($plan, $user): Payment {
            $payment = Payment::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'reference' => (string) Str::uuid(),
                'amount_kobo' => $plan->price_kobo,
                'currency' => 'NGN',
                'status' => 'pending',
                'gateway' => 'paystack',
            ]);

            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'status' => 'pending',
            ]);

            return $payment;
        });

        $callbackUrl = route('checkout.return', $payment, absolute: true);
        $initialization = $paystackService->initializeTransaction(
            $paystackEmail,
            $payment->amount_kobo,
            $payment->reference,
            $callbackUrl,
        );

        $authorizationUrl = $initialization['data']['authorization_url'] ?? null;

        if (! $initialization['status'] || $authorizationUrl === null) {
            return redirect()->route('plans.index')->with(
                'error',
                'Unable to start payment right now. Please try again.',
            );
        }

        $payment->update([
            'payload' => [
                'authorization_url' => $authorizationUrl,
                'access_code' => $initialization['data']['access_code'] ?? null,
            ],
        ]);

        return redirect()->route('checkout.show', $payment);
    }

    public function show(Payment $payment): Response
    {
        abort_unless($payment->user_id === auth()->id(), 403);

        $payment->load('plan');

        return Inertia::render('portal/Checkout', [
            'payment' => $payment,
            'authorizationUrl' => $payment->payload['authorization_url'] ?? null,
        ]);
    }

    private function resolveCheckoutUser(StoreCheckoutRequest $request): User
    {
        $phone = $request->string('login')->trim()->toString();

        $userQuery = User::query();
        $userQuery->where('phone', $phone);

        $user = $userQuery->first();

        if ($user === null) {
            $displayName = $phone;

            $user = User::create([
                'name' => $displayName,
                'email' => null,
                'phone' => $phone,
                'password' => Hash::make((string) $request->input('password')),
            ]);
        }

        Auth::login($user);

        return $user;
    }

    private function resolvePaystackEmail(User $user): string
    {
        if ($user->phone !== null) {
            $domain = (string) config('hotspot.paystack.fallback_email_domain', 'hotspot.com');
            return $user->phone.'@'.$domain;
        }

        $domain = (string) config('hotspot.paystack.fallback_email_domain', 'hotspot.com');
        return 'guest@'.$domain;
    }
}
