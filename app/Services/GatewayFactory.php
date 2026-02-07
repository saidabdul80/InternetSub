<?php

namespace App\Services;

use App\Services\MonnifyClient;
use App\Services\PaystackClient;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class GatewayFactory
{
    protected $gateways = [
        'paystack' => PaystackClient::class,
        'monnify' => MonnifyClient::class,
    ];

    /**
     * Create a gateway instance
     *
     * @param string $gateway
     * @return GatewayInterface
     * @throws InvalidArgumentException
     */
    public function create(string $gateway): GatewayInterface
    {
        try {
            if (!isset($this->gateways[strtolower($gateway)])) {
                throw new InvalidArgumentException("Unsupported payment gateway: {$gateway}");
            }

            $gatewayClass = $this->gateways[strtolower($gateway)];

            if (!class_exists($gatewayClass)) {
                throw new InvalidArgumentException("Gateway class {$gatewayClass} does not exist");
            }

            $instance = app($gatewayClass);
            return $instance;
        } catch (\Throwable  $e) {
            Log::error("Gateway processing error: " . $e->getMessage());
            throw new \Exception($e->getMessage(), 400);
        }
    }

    /**
     * Register a new gateway
     *
     * @param string $name
     * @param string $gatewayClass
     * @return void
     */
    public function register(string $name, string $gatewayClass): void
    {
        $this->gateways[$name] = $gatewayClass;
    }

    /**
     * Get all registered gateways
     *
     * @return array
     */
    public function getRegisteredGateways(): array
    {
        return array_keys($this->gateways);
    }
}
