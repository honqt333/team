<?php

namespace App\Services\Payment\Contracts;

/**
 * Payment Gateway Interface
 * 
 * All payment gateways must implement this interface.
 */
interface PaymentGatewayInterface
{
    /**
     * Initialize a payment session.
     *
     * @param array $data Payment data (amount, currency, description, metadata)
     * @return array Contains 'payment_url' or 'form_data' for redirecting user
     */
    public function initiate(array $data): array;

    /**
     * Verify/complete a payment after callback.
     *
     * @param array $data Callback data from gateway
     * @return array Contains 'success', 'payment_id', 'status', 'message'
     */
    public function verify(array $data): array;

    /**
     * Get payment details by ID.
     *
     * @param string $paymentId
     * @return array|null
     */
    public function getPayment(string $paymentId): ?array;

    /**
     * Refund a payment.
     *
     * @param string $paymentId
     * @param float|null $amount Partial refund amount, null for full refund
     * @return array Contains 'success', 'refund_id', 'message'
     */
    public function refund(string $paymentId, ?float $amount = null): array;

    /**
     * Get the gateway name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if gateway is properly configured.
     *
     * @return bool
     */
    public function isConfigured(): bool;
}
