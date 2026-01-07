<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TapGateway implements PaymentGatewayInterface
{
    protected array $config;
    protected string $baseUrl;

    public function __construct()
    {
        $this->config = config('payment.gateways.tap');
        $this->baseUrl = $this->config['base_url'] ?? 'https://api.tap.company/v2';
    }

    public function getName(): string
    {
        return 'tap';
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['secret_key']);
    }

    /**
     * Initiate a payment charge with Tap.
     */
    public function initiate(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['secret_key'],
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/charges", [
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'customer_initiated' => true,
                'threeDSecure' => true,
                'save_card' => false,
                'description' => $data['description'] ?? 'Subscription Payment',
                'metadata' => $data['metadata'] ?? [],
                'reference' => [
                    'transaction' => $data['reference'] ?? uniqid('sub_'),
                    'order' => $data['order_id'] ?? null,
                ],
                'receipt' => [
                    'email' => true,
                    'sms' => true,
                ],
                'customer' => [
                    'first_name' => $data['customer_name'] ?? 'Customer',
                    'email' => $data['customer_email'] ?? null,
                    'phone' => [
                        'country_code' => '966',
                        'number' => $data['customer_phone'] ?? null,
                    ],
                ],
                'source' => ['id' => 'src_all'],
                'redirect' => [
                    'url' => $data['callback_url'] ?? url(config('payment.callback_url')),
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'gateway' => 'tap',
                    'payment_id' => $result['id'],
                    'payment_url' => $result['transaction']['url'] ?? null,
                    'status' => $result['status'],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('errors.0.description') ?? 'Failed to initiate payment',
            ];
        } catch (\Exception $e) {
            Log::error('Tap initiate failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment after callback.
     */
    public function verify(array $data): array
    {
        try {
            $chargeId = $data['tap_id'] ?? $data['id'] ?? null;
            
            if (!$chargeId) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Charge ID not provided',
                ];
            }

            $payment = $this->getPayment($chargeId);
            
            if (!$payment) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Payment not found',
                ];
            }

            $status = $payment['status'] ?? 'failed';
            
            return [
                'success' => $status === 'CAPTURED',
                'payment_id' => $chargeId,
                'status' => strtolower($status),
                'amount' => $payment['amount'] ?? 0,
                'currency' => $payment['currency'] ?? 'SAR',
                'message' => $payment['response']['message'] ?? null,
                'payment_method' => $payment['source']['payment_method'] ?? null,
                'raw_response' => $payment,
            ];
        } catch (\Exception $e) {
            Log::error('Tap verification failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get charge details from Tap API.
     */
    public function getPayment(string $paymentId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['secret_key'],
            ])->get("{$this->baseUrl}/charges/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Tap getPayment exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Refund a charge.
     */
    public function refund(string $paymentId, ?float $amount = null): array
    {
        try {
            $payload = ['charge_id' => $paymentId];
            if ($amount !== null) {
                $payload['amount'] = $amount;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['secret_key'],
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/refunds", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json('id'),
                    'message' => 'Refund processed successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('errors.0.description') ?? 'Refund failed',
            ];
        } catch (\Exception $e) {
            Log::error('Tap refund failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
