<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $status = trim($request->string('status')->toString());

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('username', 'like', '%'.$search.'%')
                        ->orWhere('full_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone_number', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'username' => $customer->username,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'service_type' => $customer->service_type,
                'account_type' => $customer->account_type,
                'status' => $customer->status,
                'balance' => (string) $customer->balance,
                'auto_renewal' => $customer->auto_renewal,
                'address' => $customer->address,
                'created_at' => $customer->created_at,
                'last_login_at' => $customer->last_login_at,
            ]);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'total' => Customer::query()->count(),
                'active' => Customer::query()->where('status', 'Active')->count(),
                'suspended' => Customer::query()->where('status', 'Suspended')->count(),
                'hotspot' => Customer::query()->where('service_type', 'Hotspot')->count(),
            ],
        ]);
    }

    public function show(Customer $customer): Response
    {
        $recentRecharges = $customer->recharges()
            ->latest('recharged_at')
            ->limit(8)
            ->get()
            ->map(fn ($recharge) => [
                'id' => $recharge->id,
                'plan_name' => $recharge->plan_name,
                'router_name' => $recharge->router_name,
                'method' => $recharge->method,
                'status' => $recharge->status,
                'recharged_at' => $recharge->recharged_at,
                'expires_at' => $recharge->expires_at,
            ]);

        $recentTransactions = $customer->transactions()
            ->latest('recharged_at')
            ->limit(8)
            ->get()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'invoice' => $transaction->invoice,
                'plan_name' => $transaction->plan_name,
                'price' => (string) $transaction->price,
                'method' => $transaction->method,
                'router_name' => $transaction->router_name,
                'service_type' => $transaction->service_type,
                'recharged_at' => $transaction->recharged_at,
                'expires_at' => $transaction->expires_at,
                'note' => $transaction->note,
            ]);

        $messages = $customer->messages()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'subject' => $message->subject,
                'body' => $message->body,
                'from_type' => $message->from_type,
                'read_at' => $message->read_at,
                'created_at' => $message->created_at,
            ]);

        $paymentSnapshot = collect();
        if ($customer->phone_number) {
            $paymentSnapshot = Payment::query()
                ->where('phone_number', $customer->phone_number)
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'gateway' => $payment->gateway,
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at,
                ]);
        }

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'username' => $customer->username,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'service_type' => $customer->service_type,
                'account_type' => $customer->account_type,
                'status' => $customer->status,
                'balance' => (string) $customer->balance,
                'auto_renewal' => $customer->auto_renewal,
                'address' => $customer->address,
                'city' => $customer->city,
                'district' => $customer->district,
                'state' => $customer->state,
                'zip' => $customer->zip,
                'created_at' => $customer->created_at,
                'last_login_at' => $customer->last_login_at,
            ],
            'stats' => [
                'recharge_count' => $customer->recharges()->count(),
                'active_recharge_count' => $customer->recharges()->where('status', 'on')->count(),
                'transaction_count' => $customer->transactions()->count(),
                'transaction_total' => (string) ($customer->transactions()->sum('price') ?? 0),
                'message_count' => $customer->messages()->count(),
                'unread_message_count' => $customer->messages()->whereNull('read_at')->count(),
            ],
            'recent_recharges' => $recentRecharges,
            'recent_transactions' => $recentTransactions,
            'messages' => $messages,
            'payments' => $paymentSnapshot,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:45', 'unique:customers,username'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
            'address' => ['nullable', 'string'],
            'account_type' => ['required', 'in:Business,Personal'],
            'service_type' => ['required', 'in:Hotspot,PPPoE,Others'],
            'status' => ['required', 'in:Active,Banned,Disabled,Inactive,Limited,Suspended'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'auto_renewal' => ['nullable', 'boolean'],
        ]);

        Customer::query()->create([
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?: null,
            'phone_number' => ! empty($data['phone_number']) ? $this->normalizeNigerianPhone($data['phone_number']) : null,
            'password' => Hash::make($data['password']),
            'address' => $data['address'] ?: null,
            'account_type' => $data['account_type'],
            'service_type' => $data['service_type'],
            'status' => $data['status'],
            'balance' => $data['balance'] ?? 0,
            'auto_renewal' => $request->boolean('auto_renewal'),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Customer created.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:45', 'unique:customers,username,'.$customer->id],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email,'.$customer->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:6'],
            'address' => ['nullable', 'string'],
            'account_type' => ['required', 'in:Business,Personal'],
            'service_type' => ['required', 'in:Hotspot,PPPoE,Others'],
            'status' => ['required', 'in:Active,Banned,Disabled,Inactive,Limited,Suspended'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'auto_renewal' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?: null,
            'phone_number' => ! empty($data['phone_number']) ? $this->normalizeNigerianPhone($data['phone_number']) : null,
            'address' => $data['address'] ?: null,
            'account_type' => $data['account_type'],
            'service_type' => $data['service_type'],
            'status' => $data['status'],
            'balance' => $data['balance'] ?? 0,
            'auto_renewal' => $request->boolean('auto_renewal'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $customer->update($payload);

        return back()->with('success', 'Customer updated.');
    }

    protected function normalizeNigerianPhone(string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($normalized, '234')) {
            $normalized = substr($normalized, 3);
        }

        if ($normalized !== '' && ! str_starts_with($normalized, '0')) {
            $normalized = '0'.$normalized;
        }

        return $normalized;
    }
}
