<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(): Response
    {
        /** @var Customer $customer */
        $customer = request()->attributes->get('customer');

        return Inertia::render('Member/Packages', [
            'customer' => [
                'id' => $customer->id,
                'username' => $customer->username,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'balance' => (string) $customer->balance,
                'status' => $customer->status,
            ],
            'packages' => $customer->recharges()
                ->latest()
                ->get()
                ->map(fn ($recharge) => [
                    'id' => $recharge->id,
                    'plan_name' => $recharge->plan_name,
                    'router_name' => $recharge->router_name,
                    'method' => $recharge->method,
                    'service_type' => $recharge->service_type,
                    'status' => $recharge->status,
                    'recharged_at' => $recharge->recharged_at,
                    'expires_at' => $recharge->expires_at,
                ]),
        ]);
    }
}
