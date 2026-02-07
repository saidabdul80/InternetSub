<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackClient implements GatewayInterface
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

        $body = data_get($data, 'data', []);

        return [
            'reference' => data_get($body, 'reference'),
            'authorization_url' => data_get($body, 'authorization_url'),
            'status' => 'success',
            'raw' => $body,
        ];
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

        $body = data_get($data, 'data', []);

        return [
            'reference' => data_get($body, 'reference'),
            'status' => data_get($body, 'status'),
            'raw' => $body,
        ];
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
