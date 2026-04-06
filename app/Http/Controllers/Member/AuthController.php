<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\MemberRemember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->session()->has('customer_id')) {
            return redirect()->route('member.dashboard');
        }

        return Inertia::render('Member/Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);
        $phoneVariants = $this->phoneLookupVariants($login);

        $customer = Customer::query()
            ->where(function ($query) use ($login, $phoneVariants): void {
                $query->where('username', $login)
                    ->orWhere('email', $login);

                if ($phoneVariants !== []) {
                    $query->orWhereIn('phone_number', $phoneVariants);
                }
            })
            ->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password)) {
            return back()->withErrors([
                'login' => 'Invalid customer credentials.',
            ]);
        }

        if ($customer->status !== 'Active') {
            return back()->withErrors([
                'login' => 'This account is not active.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('customer_id', $customer->id);
        $customer->update(['last_login_at' => now()]);

        return redirect()
            ->route('member.dashboard')
            ->withCookie(MemberRemember::cookie($customer, $request->isSecure()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('customer_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('member.login')
            ->with('success', 'You have been signed out.')
            ->withCookie(MemberRemember::forget());
    }

    protected function phoneLookupVariants(string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '' || strlen($digits) < 8) {
            return [];
        }

        $variants = [$value, $digits];

        if (str_starts_with($digits, '234')) {
            $local = substr($digits, 3);
            $variants[] = $local;
            $variants[] = '0'.$local;
            $variants[] = '+'.$digits;
        } elseif (str_starts_with($digits, '0')) {
            $withoutZero = substr($digits, 1);
            $variants[] = '234'.$withoutZero;
            $variants[] = '+234'.$withoutZero;
        }

        return array_values(array_unique(array_filter($variants)));
    }
}
