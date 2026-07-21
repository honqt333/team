<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\PaymentSettings;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\MoyasarGateway;
use App\Services\Payment\Gateways\TabbyGateway;
use App\Services\Payment\Gateways\TamaraGateway;
use App\Services\Payment\Gateways\TapGateway;
use Exception;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Available gateway instances.
     */
    protected array $gateways = [];

    /**
     * Get a gateway instance.
     *
     * NOTE: We don't cache instances here. PaymentSettings is admin-managed
     * and can change between requests (e.g. admin toggles a gateway in
     * /system/settings/payment then tenant reloads checkout). Caching
     * instances would freeze the gateway's enabled/keys state and ignore
     * the latest DB values. The cost of building a fresh instance is
     * negligible (one DB read) and the simplicity is worth it.
     */
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        // Prefer DB-stored default_gateway (admin-managed), fall back to env/config.
        // This lets the admin change the default gateway in /system/settings/payment
        // without restarting the app or editing .env.
        if ($name === null) {
            $name = PaymentSettings::getSettings()->default_gateway
                ?? config('payment.default');
        }

        return $this->resolveGateway($name);
    }

    /**
     * Resolve a gateway by name.
     */
    protected function resolveGateway(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'moyasar' => new MoyasarGateway,
            'tap' => new TapGateway,
            'tabby' => new TabbyGateway,
            'tamara' => new TamaraGateway,
            // bank_transfer has no gateway class — it's a manual flow handled
            // by the subscription controller directly. Throw early so callers
            // (e.g. subscribe) don't try to hit a non-existent API.
            'bank_transfer' => throw new InvalidArgumentException(
                'Bank transfer is handled directly by the subscription controller, not via PaymentManager.'
            ),
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
            } catch (Exception $e) {
                // Gateway not available
            }
        }

        // Bank transfer isn't a gateway class but it's a valid payment method.
        // Surface it as available so the tenant sees it in checkout when enabled.
        $settings = PaymentSettings::getSettings();

        if ($settings->bank_transfer_enabled) {
            $available['bank_transfer'] = [
                'name' => 'bank_transfer',
                'configured' => true,
                'bank_accounts' => $settings->bank_accounts ?? [],
            ];
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
