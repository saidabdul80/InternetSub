<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackClient
{
    public function initializeTransaction(array $payload): array
    {
        $response = Http::withToken($this->secretKey())
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->post('/transaction/initialize', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Paystack initialization failed.');
        }

        $data = $response->json();

        if (! data_get($data, 'status')) {
            throw new RuntimeException('Paystack returned an unsuccessful response.');
        }

        return data_get($data, 'data', []);
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey())
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->get('/transaction/verify/'.$reference);

        if (! $response->successful()) {
            throw new RuntimeException('Paystack verification failed.');
        }

        $data = $response->json();

        if (! data_get($data, 'status')) {
            throw new RuntimeException('Paystack returned an unsuccessful response.');
        }

        return data_get($data, 'data', []);
    }

    protected function secretKey(): string
    {
        return (string) config('services.paystack.secret_key');
    }

    protected function baseUrl(): string
    {
        return (string) config('services.paystack.base_url', 'https://api.paystack.co');
    }
}
