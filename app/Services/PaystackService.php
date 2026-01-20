<?php

namespace App\Services;

use Illuminate\Http\Request;
use Yabacon\Paystack;

class PaystackService
{
    public function __construct(
        private readonly ?string $secretKey = null,
        private readonly ?string $webhookSecret = null,
    ) {
    }

    /**
     * @return array{status: bool, data: array<string, mixed>|null}
     */
    public function verifyTransaction(string $reference): array
    {
        $paystack = new Paystack($this->secretKey ?? config('hotspot.paystack.secret_key'));
        $response = $paystack->transaction->verify([
            'reference' => $reference,
        ]);

        return [
            'status' => (bool) ($response->status ?? false),
            'data' => isset($response->data) ? (array) $response->data : null,
        ];
    }

    /**
     * @return array{status: bool, data: array<string, mixed>|null}
     */
    public function initializeTransaction(
        string $email,
        int $amountKobo,
        string $reference,
        string $callbackUrl,
    ): array {
        $paystack = new Paystack($this->secretKey ?? config('hotspot.paystack.secret_key'));

        $response = $paystack->transaction->initialize([
            'email' => $email,
            'amount' => $amountKobo,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
        ]);

        return [
            'status' => (bool) ($response->status ?? false),
            'data' => isset($response->data) ? (array) $response->data : null,
        ];
    }

    private function resolveEmail(string $email, string $reference): string
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            return $email;
        }

        $domain = 'hotspot.com';
        $safeReference = preg_replace('/[^a-z0-9]+/i', '', $reference) ?: 'guest';

        return strtolower($safeReference).'@'.$domain;
    }

    public function isSignatureValid(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');

        if ($signature === null) {
            return false;
        }

        $secret = $this->webhookSecret ?? config('hotspot.paystack.webhook_secret');
        $payload = $request->getContent();
        $hash = hash_hmac('sha512', $payload, (string) $secret);

        return hash_equals($hash, $signature);
    }
}
