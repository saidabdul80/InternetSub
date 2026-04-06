<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NetworkRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RouterController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $status = trim($request->string('status')->toString());

        $routers = NetworkRouter::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('ip_address', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (NetworkRouter $router) => [
                'id' => $router->id,
                'name' => $router->name,
                'ip_address' => $router->ip_address,
                'username' => $router->username,
                'description' => $router->description,
                'coordinates' => $router->coordinates,
                'status' => $router->status,
                'coverage' => $router->coverage,
                'enabled' => $router->enabled,
                'last_seen_at' => $router->last_seen_at,
                'created_at' => $router->created_at,
            ]);

        return Inertia::render('Admin/Routers/Index', [
            'routers' => $routers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'total' => NetworkRouter::query()->count(),
                'online' => NetworkRouter::query()->where('status', 'Online')->count(),
                'offline' => NetworkRouter::query()->where('status', 'Offline')->count(),
                'enabled' => NetworkRouter::query()->where('enabled', true)->count(),
            ],
        ]);
    }

    public function show(NetworkRouter $router): Response
    {
        $recentRecharges = $router->recharges()
            ->with('customer')
            ->latest('recharged_at')
            ->limit(10)
            ->get()
            ->map(fn ($recharge) => [
                'id' => $recharge->id,
                'username' => $recharge->username,
                'customer' => $recharge->customer?->full_name,
                'plan_name' => $recharge->plan_name,
                'method' => $recharge->method,
                'status' => $recharge->status,
                'recharged_at' => $recharge->recharged_at,
                'expires_at' => $recharge->expires_at,
            ]);

        $recentTransactions = $router->transactions()
            ->with('customer')
            ->latest('recharged_at')
            ->limit(10)
            ->get()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'invoice' => $transaction->invoice,
                'username' => $transaction->username,
                'customer' => $transaction->customer?->full_name,
                'plan_name' => $transaction->plan_name,
                'price' => (string) $transaction->price,
                'method' => $transaction->method,
                'service_type' => $transaction->service_type,
                'recharged_at' => $transaction->recharged_at,
                'expires_at' => $transaction->expires_at,
            ]);

        return Inertia::render('Admin/Routers/Show', [
            'router' => [
                'id' => $router->id,
                'name' => $router->name,
                'ip_address' => $router->ip_address,
                'username' => $router->username,
                'description' => $router->description,
                'coordinates' => $router->coordinates,
                'status' => $router->status,
                'coverage' => $router->coverage,
                'enabled' => $router->enabled,
                'last_seen_at' => $router->last_seen_at,
                'created_at' => $router->created_at,
            ],
            'stats' => [
                'recharge_count' => $router->recharges()->count(),
                'active_recharge_count' => $router->recharges()->where('status', 'on')->count(),
                'transaction_count' => $router->transactions()->count(),
                'transaction_total' => (string) ($router->transactions()->sum('price') ?? 0),
            ],
            'recent_recharges' => $recentRecharges,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:network_routers,name'],
            'ip_address' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'coordinates' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:Online,Offline'],
            'coverage' => ['nullable', 'string', 'max:16'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        NetworkRouter::query()->create([
            ...$data,
            'coordinates' => $data['coordinates'] ?? '',
            'coverage' => $data['coverage'] ?? '0',
            'enabled' => $request->boolean('enabled', true),
        ]);

        return back()->with('success', 'Router created.');
    }

    public function update(Request $request, NetworkRouter $router): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:network_routers,name,'.$router->id],
            'ip_address' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'coordinates' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:Online,Offline'],
            'coverage' => ['nullable', 'string', 'max:16'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $payload = [
            ...$data,
            'coordinates' => $data['coordinates'] ?? '',
            'coverage' => $data['coverage'] ?? '0',
            'enabled' => $request->boolean('enabled'),
        ];

        if (empty($data['password'])) {
            unset($payload['password']);
        }

        $router->update($payload);

        return back()->with('success', 'Router updated.');
    }
}
