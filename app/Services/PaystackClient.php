<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackClient implements GatewayInterface
{
    public function initializeTransaction(array $payload): array
    {
        $payload = $this->formatInitializePayload($payload);

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

    protected function formatInitializePayload(array $payload): array
    {
        $channels = $this->resolveChannels(data_get($payload, 'channels'));

        if ($channels === []) {
            unset($payload['channels']);
        } else {
            $payload['channels'] = $channels;
        }

        $metadata = data_get($payload, 'metadata');

        if (is_array($metadata) && ! array_key_exists('custom_fields', $metadata)) {
            $customFields = $this->buildCustomFields($payload, $metadata);

            if ($customFields !== []) {
                $metadata['custom_fields'] = $customFields;
            }
        }

        if ($metadata === null || $metadata === []) {
            unset($payload['metadata']);
        } elseif (is_array($metadata)) {
            $payload['metadata'] = $metadata;
        }

        return $payload;
    }

    protected function resolveChannels(mixed $channels): array
    {
        $source = is_array($channels) ? $channels : config('services.paystack.channels', []);

        if (! is_array($source)) {
            return [];
        }

        $normalized = array_map(
            static fn ($channel): string => strtolower(trim((string) $channel)),
            $source
        );

        return array_values(array_unique(array_filter($normalized)));
    }

    protected function buildCustomFields(array $payload, array $metadata): array
    {
        $phoneNumber = trim((string) data_get($metadata, 'phone_number', data_get($payload, 'phone_number', '')));
        $planType = trim((string) data_get($metadata, 'plan_type', ''));
        $description = trim((string) data_get($payload, 'description', ''));

        $fields = [];

        if ($phoneNumber !== '') {
            $fields[] = [
                'display_name' => 'Phone Number',
                'variable_name' => 'phone_number',
                'value' => $phoneNumber,
            ];
        }

        if ($planType !== '') {
            $fields[] = [
                'display_name' => 'Plan Type',
                'variable_name' => 'plan_type',
                'value' => $planType,
            ];
        }

        if ($description !== '') {
            $fields[] = [
                'display_name' => 'Plan Name',
                'variable_name' => 'plan_name',
                'value' => $description,
            ];
        }

        return $fields;
    }
}
