<?php

namespace App\Services;

interface GatewayInterface
{
    /**
     * Initialize a transaction and return a normalized response.
     *
     * Expected keys: reference, authorization_url, status, raw
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function initializeTransaction(array $payload): array;

    /**
     * Verify a transaction and return a normalized response.
     *
     * Expected keys: reference, status, raw
     *
     * @param string $reference
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array;
}
