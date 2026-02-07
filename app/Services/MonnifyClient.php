<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MonnifyClient implements GatewayInterface
{
    public function initializeTransaction(array $payload): array
    {
        $token = $this->authenticate();

        $amount = (float) data_get($payload, 'amount', 0);
        if ($amount > 0 && $amount == (int) $amount) {
            $amount = $amount / 100;
        }
        //Log::error($payload);
        $requestPayload = [
            'amount' => $amount,
            'customerEmail' => data_get($payload, 'email', config('services.paystack.default_email')),
            'paymentReference' => data_get($payload, 'reference'),
            'paymentDescription' => data_get($payload, 'description', 'Voucher purchase'),
            'currencyCode' => data_get($payload, 'currency', 'NGN'),
            'contractCode' => config('services.monnify.contract_code'),
            'redirectUrl' => data_get($payload, 'callback_url'),
            'paymentMethods' => [
                'CARD',
                'ACCOUNT_TRANSFER',
                'USSD',
                'PHONE_NUMBER',
            ],
            //'metadata' => data_get($payload, 'metadata', []),
        ];
        //Log::info('data',$requestPayload);
        $response = Http::withToken($token)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->post('/api/v1/merchant/transactions/init-transaction', $requestPayload);

        if (! $response->successful()) {
            throw new RuntimeException('Monnify initialization failed.');
            }
            
            $data = $response->json();
            
            if (! data_get($data, 'requestSuccessful')) {
                $message = (string) data_get($data, 'responseMessage', 'Monnify returned an unsuccessful response.');
                throw new RuntimeException($message);
                }
                
        $body = data_get($data, 'responseBody', []);
        Log::info(data_get($body, 'checkoutUrl'));

        return [
            'reference' => data_get($body, 'transactionReference'),
            'authorization_url' => data_get($body, 'checkoutUrl'),
            'status' => 'success',
            'raw' => $body,
        ];
    }

    public function verifyTransaction(string $transactionReference): array
    {
        $token = $this->authenticate();

        $response = Http::withToken($token)
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->get('/api/v2/transactions/'.rawurlencode($transactionReference));

        if (! $response->successful()) {
            throw new RuntimeException('Monnify verification failed.');
        }

        $data = $response->json();

        if (! data_get($data, 'requestSuccessful')) {
            $message = (string) data_get($data, 'responseMessage', 'Monnify returned an unsuccessful response.');
            throw new RuntimeException($message);
        }

        $body = data_get($data, 'responseBody', []);
        $paymentStatus = strtoupper((string) data_get($body, 'paymentStatus'));
        $status = $paymentStatus === 'PAID' ? 'success' : 'failed';

        return [
            'reference' => data_get($body, 'transactionReference'),
            'status' => $status,
            'raw' => $body,
        ];
    }

    protected function authenticate(): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$this->authToken(),
        ])
            ->acceptJson()
            ->baseUrl($this->baseUrl())
            ->post('/api/v1/auth/login');

        if (! $response->successful()) {
            throw new RuntimeException('Monnify authentication failed.');
        }

        $data = $response->json();

        if (! data_get($data, 'requestSuccessful')) {
            $message = (string) data_get($data, 'responseMessage', 'Monnify returned an unsuccessful response.');
            throw new RuntimeException($message);
        }

        $token = (string) data_get($data, 'responseBody.accessToken');

        if ($token === '') {
            throw new RuntimeException('Monnify did not return an access token.');
        }
        return $token;
    }

    protected function baseUrl(): string
    {
        return (string) config('services.monnify.base_url', 'https://sandbox.monnify.com');
    }

    protected function authToken(): string
    {
        //'Basic base64(apiKey:secretKey)'.
        $code =   base64_encode(config('services.monnify.public_key').':'.config('services.monnify.secret_key'));
        return (string) $code;
    }
}
