<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\MoyasarGateway;
use App\Services\Payment\Gateways\TapGateway;
use App\Services\Payment\Gateways\TabbyGateway;
use App\Services\Payment\Gateways\TamaraGateway;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Available gateway instances.
     */
    protected array $gateways = [];

    /**
     * Get a gateway instance.
     */
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?? config('payment.default');

        if (!isset($this->gateways[$name])) {
            $this->gateways[$name] = $this->resolveGateway($name);
        }

        return $this->gateways[$name];
    }

    /**
     * Resolve a gateway by name.
     */
    protected function resolveGateway(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'moyasar' => new MoyasarGateway(),
            'tap' => new TapGateway(),
            'tabby' => new TabbyGateway(),
            'tamara' => new TamaraGateway(),
            default => throw new InvalidArgumentException("Payment gateway [{$name}] is not supported."),
        };
    }

    /**
     * Get all available gateways.
     */
    public function getAvailableGateways(): array
    {
        $available = [];
        
        foreach (['moyasar', 'tap', 'tabby', 'tamara'] as $name) {
            try {
                $gateway = $this->gateway($name);
                if ($gateway->isConfigured()) {
                    $available[$name] = [
                        'name' => $name,
                        'configured' => true,
                    ];
                }
            } catch (\Exception $e) {
                // Gateway not available
            }
        }

        return $available;
    }

    /**
     * Initiate a payment.
     */
    public function initiate(array $data, ?string $gateway = null): array
    {
        return $this->gateway($gateway)->initiate($data);
    }

    /**
     * Verify a payment.
     */
    public function verify(array $data, ?string $gateway = null): array
    {
        return $this->gateway($gateway)->verify($data);
    }

    /**
     * Get payment details.
     */
    public function getPayment(string $paymentId, ?string $gateway = null): ?array
    {
        return $this->gateway($gateway)->getPayment($paymentId);
    }

    /**
     * Refund a payment.
     */
    public function refund(string $paymentId, ?float $amount = null, ?string $gateway = null): array
    {
        return $this->gateway($gateway)->refund($paymentId, $amount);
    }
}
