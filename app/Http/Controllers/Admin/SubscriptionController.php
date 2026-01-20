<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManualSubscriptionRequest;
use App\Jobs\ProvisionHotspotAccess;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MikrotikService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(): Response
    {
        $subscriptions = Subscription::query()
            ->with(['user', 'plan', 'payment'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    public function create(): Response
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'phone'])
            ->orderBy('name')
            ->get();

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('price_kobo')
            ->get();

        return Inertia::render('admin/Subscriptions/Create', [
            'users' => $users,
            'plans' => $plans,
        ]);
    }

    public function store(StoreManualSubscriptionRequest $request): RedirectResponse
    {
        $plan = Plan::query()->findOrFail($request->integer('plan_id'));

        $payment = DB::transaction(function () use ($request, $plan): Payment {
            $payment = Payment::create([
                'user_id' => $request->integer('user_id'),
                'plan_id' => $plan->id,
                'reference' => 'manual-'.Str::uuid(),
                'amount_kobo' => $plan->price_kobo,
                'currency' => 'NGN',
                'status' => 'success',
                'gateway' => 'manual',
                'paid_at' => now(),
            ]);

            Subscription::create([
                'user_id' => $request->integer('user_id'),
                'plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'status' => 'pending',
            ]);

            return $payment;
        });

        app(ProvisionHotspotAccess::class, ['paymentId' => $payment->id])
            ->handle(app(MikrotikService::class));

        return redirect()->route('admin.subscriptions.index');
    }
}
