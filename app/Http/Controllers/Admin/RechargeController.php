<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recharge;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RechargeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $status = trim($request->string('status')->toString());

        $recharges = Recharge::query()
            ->with(['customer', 'plan', 'router', 'admin'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('username', 'like', '%'.$search.'%')
                        ->orWhere('plan_name', 'like', '%'.$search.'%')
                        ->orWhere('router_name', 'like', '%'.$search.'%')
                        ->orWhere('method', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Recharge $recharge) => [
                'id' => $recharge->id,
                'username' => $recharge->username,
                'customer' => $recharge->customer?->full_name,
                'plan_name' => $recharge->plan_name,
                'router_name' => $recharge->router_name,
                'method' => $recharge->method,
                'service_type' => $recharge->service_type,
                'status' => $recharge->status,
                'recharged_at' => $recharge->recharged_at,
                'expires_at' => $recharge->expires_at,
                'admin' => $recharge->admin?->name,
            ]);

        return Inertia::render('Admin/Recharges/Index', [
            'recharges' => $recharges,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'total' => Recharge::query()->count(),
                'active' => Recharge::query()->where('status', 'on')->count(),
                'inactive' => Recharge::query()->where('status', '!=', 'on')->count(),
                'expiring_soon' => Recharge::query()
                    ->where('status', 'on')
                    ->whereBetween('expires_at', [now(), now()->addDays(3)])
                    ->count(),
            ],
        ]);
    }
}
