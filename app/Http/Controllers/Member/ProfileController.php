<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        /** @var Customer $customer */
        $customer = request()->attributes->get('customer');

        return Inertia::render('Member/Profile', [
            'customer' => [
                'id' => $customer->id,
                'username' => $customer->username,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'address' => $customer->address,
                'city' => $customer->city,
                'state' => $customer->state,
                'district' => $customer->district,
                'zip' => $customer->zip,
                'account_type' => $customer->account_type,
                'service_type' => $customer->service_type,
                'auto_renewal' => $customer->auto_renewal,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email,'.$customer->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'auto_renewal' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $payload = [
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?: null,
            'phone_number' => ! empty($data['phone_number']) ? $this->normalizeNigerianPhone($data['phone_number']) : null,
            'address' => $data['address'] ?: null,
            'city' => $data['city'] ?: null,
            'state' => $data['state'] ?: null,
            'district' => $data['district'] ?: null,
            'zip' => $data['zip'] ?: null,
            'auto_renewal' => $request->boolean('auto_renewal'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $customer->update($payload);

        return back()->with('success', 'Profile updated.');
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
