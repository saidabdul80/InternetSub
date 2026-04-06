<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Support\Facades\Cookie as CookieFacade;
use Symfony\Component\HttpFoundation\Cookie;

class MemberRemember
{
    public const COOKIE_NAME = 'member_remember';

    public static function cookieName(): string
    {
        return self::COOKIE_NAME;
    }

    public static function minutes(): int
    {
        return 60 * 24 * 3;
    }

    public static function value(Customer $customer): string
    {
        return $customer->id.'|'.sha1((string) $customer->password);
    }

    public static function cookie(Customer $customer, bool $secure = false): Cookie
    {
        return cookie(
            self::COOKIE_NAME,
            self::value($customer),
            self::minutes(),
            null,
            null,
            $secure,
            true,
            false,
            'lax'
        );
    }

    public static function forget(): Cookie
    {
        return CookieFacade::forget(self::COOKIE_NAME);
    }

    public static function customerIdFromValue(?string $value): ?int
    {
        if (! is_string($value) || $value === '' || ! str_contains($value, '|')) {
            return null;
        }

        [$customerId] = explode('|', $value, 2);

        if (! ctype_digit($customerId)) {
            return null;
        }

        return (int) $customerId;
    }

    public static function matches(Customer $customer, ?string $value): bool
    {
        return hash_equals(self::value($customer), (string) $value);
    }
}
