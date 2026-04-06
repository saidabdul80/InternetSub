<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $transactions = Transaction::query()
            ->with(['customer', 'router', 'admin'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('invoice', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('plan_name', 'like', '%'.$search.'%')
                        ->orWhere('router_name', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'invoice' => $transaction->invoice,
                'username' => $transaction->username,
                'plan_name' => $transaction->plan_name,
                'price' => (string) $transaction->price,
                'method' => $transaction->method,
                'router_name' => $transaction->router_name,
                'service_type' => $transaction->service_type,
                'note' => $transaction->note,
                'recharged_at' => $transaction->recharged_at,
                'expires_at' => $transaction->expires_at,
                'customer' => $transaction->customer?->full_name,
                'admin' => $transaction->admin?->name,
            ]);

        return Inertia::render('Admin/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'total' => Transaction::query()->count(),
                'revenue' => (string) Transaction::query()->sum('price'),
            ],
        ]);
    }
}
