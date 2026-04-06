<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Support\MemberRemember;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $customerId = $request->session()->get('customer_id');
        $rememberValue = $request->cookie(MemberRemember::cookieName());
        $customer = null;

        if ($customerId) {
            $customer = Customer::query()->find($customerId);
        }

        if (! $customer) {
            $rememberCustomerId = MemberRemember::customerIdFromValue($rememberValue);

            if ($rememberCustomerId) {
                $rememberCustomer = Customer::query()->find($rememberCustomerId);

                if ($rememberCustomer && MemberRemember::matches($rememberCustomer, $rememberValue)) {
                    $request->session()->regenerate();
                    $request->session()->put('customer_id', $rememberCustomer->id);
                    $customer = $rememberCustomer;
                } else {
                    Cookie::queue(MemberRemember::forget());
                }
            }
        }

        if (! $customer) {
            $request->session()->forget('customer_id');
            Cookie::queue(MemberRemember::forget());

            return redirect()->route('member.login')->with(
                'error',
                $rememberValue ? 'Your session has expired.' : 'Please sign in to continue.'
            );
        }

        if ($customer->status !== 'Active') {
            $request->session()->forget('customer_id');
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Cookie::queue(MemberRemember::forget());

            return redirect()->route('member.login')->with('error', 'This account is not active.');
        }

        $request->attributes->set('customer', $customer);
        Cookie::queue(MemberRemember::cookie($customer, $request->isSecure()));

        return $next($request);
    }
}
