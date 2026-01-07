<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TamaraGateway implements PaymentGatewayInterface
{
    protected array $config;
    protected string $baseUrl;

    public function __construct()
    {
        $this->config = config('payment.gateways.tamara', []);
        $this->baseUrl = $this->config['base_url'] ?? 'https://api.tamara.co';
    }

    public function getName(): string
    {
        return 'tamara';
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_token']);
    }

    /**
     * Create a Tamara checkout session.
     */
    public function initiate(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_token'],
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/checkout", [
                'order_reference_id' => $data['reference'] ?? uniqid('sub_'),
                'total_amount' => [
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'SAR',
                ],
                'description' => $data['description'] ?? 'Subscription Payment',
                'country_code' => 'SA',
                'payment_type' => 'PAY_BY_INSTALMENTS',
                'instalments' => 3, // Tamara typically offers 3 installments
                'locale' => 'ar_SA',
                'items' => [
                    [
                        'reference_id' => $data['reference'] ?? uniqid('item_'),
                        'type' => 'Digital',
                        'name' => $data['description'] ?? 'Subscription',
                        'quantity' => 1,
                        'total_amount' => [
                            'amount' => $data['amount'],
                            'currency' => $data['currency'] ?? 'SAR',
                        ],
                    ],
                ],
                'consumer' => [
                    'first_name' => $data['customer_name'] ?? 'Customer',
                    'last_name' => '',
                    'phone_number' => $data['customer_phone'] ?? '+966500000000',
                    'email' => $data['customer_email'] ?? 'customer@example.com',
                ],
                'billing_address' => [
                    'first_name' => $data['customer_name'] ?? 'Customer',
                    'last_name' => '',
                    'phone_number' => $data['customer_phone'] ?? '+966500000000',
                    'city' => 'Riyadh',
                    'country_code' => 'SA',
                ],
                'shipping_address' => [
                    'first_name' => $data['customer_name'] ?? 'Customer',
                    'last_name' => '',
                    'phone_number' => $data['customer_phone'] ?? '+966500000000',
                    'city' => 'Riyadh',
                    'country_code' => 'SA',
                ],
                'merchant_url' => [
                    'success' => $data['callback_url'] ?? url(config('payment.callback_url')),
                    'failure' => $data['failure_url'] ?? url(config('payment.failed_url')),
                    'cancel' => $data['cancel_url'] ?? url(config('payment.failed_url')),
                    'notification' => $data['webhook_url'] ?? url('/api/webhooks/tamara'),
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'gateway' => 'tamara',
                    'order_id' => $result['order_id'] ?? null,
                    'checkout_id' => $result['checkout_id'] ?? null,
                    'payment_url' => $result['checkout_url'] ?? null,
                    'status' => 'created',
                ];
            }

            Log::error('Tamara initiate failed', ['response' => $response->json()]);
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to create Tamara session',
            ];
        } catch (\Exception $e) {
            Log::error('Tamara initiate exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment status.
     */
    public function verify(array $data): array
    {
        try {
            $orderId = $data['order_id'] ?? $data['id'] ?? null;
            
            if (!$orderId) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Order ID not provided',
                ];
            }

            $order = $this->getPayment($orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Order not found',
                ];
            }

            $status = strtolower($order['status'] ?? 'failed');
            
            return [
                'success' => in_array($status, ['approved', 'captured', 'fully_captured']),
                'payment_id' => $orderId,
                'status' => $status,
                'amount' => $order['total_amount']['amount'] ?? 0,
                'currency' => $order['total_amount']['currency'] ?? 'SAR',
                'payment_method' => 'tamara',
                'raw_response' => $order,
            ];
        } catch (\Exception $e) {
            Log::error('Tamara verification failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get order details.
     */
    public function getPayment(string $orderId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_token'],
            ])->get("{$this->baseUrl}/orders/{$orderId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Tamara getPayment exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Refund order.
     */
    public function refund(string $orderId, ?float $amount = null): array
    {
        try {
            $order = $this->getPayment($orderId);
            $refundAmount = $amount ?? ($order['total_amount']['amount'] ?? 0);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_token'],
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/orders/{$orderId}/refunds", [
                'total_amount' => [
                    'amount' => $refundAmount,
                    'currency' => $order['total_amount']['currency'] ?? 'SAR',
                ],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json('refund_id'),
                    'message' => 'Refund processed successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Refund failed',
            ];
        } catch (\Exception $e) {
            Log::error('Tamara refund failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
