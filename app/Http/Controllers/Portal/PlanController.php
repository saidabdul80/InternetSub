<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('price_kobo')
            ->get();

        $activeSubscription = null;

        if (Auth::check()) {
            $activeSubscription = Subscription::query()
                ->with('plan')
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->latest('starts_at')
                ->first();
        }

        return Inertia::render('portal/Plans', [
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    public function checkPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^(?:\\+234|0)(7|8|9)\\d{9}$/'],
        ]);

        $exists = User::query()
            ->where('phone', $validated['phone'])
            ->exists();

        return response()->json([
            'exists' => $exists,
        ]);
    }
}
