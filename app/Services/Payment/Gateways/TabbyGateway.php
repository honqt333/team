<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentSettings;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Support\CountryCurrencyRegistry;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TabbyGateway implements PaymentGatewayInterface
{
    protected array $config;

    protected string $baseUrl;

    public function __construct()
    {
        // CRITICAL: keep boolean false values (e.g. enabled=false) — array_filter
        // without an explicit callback drops ALL falsy values including false.
        $dbConfig = PaymentSettings::first()?->tabby ?? [];
        $envConfig = config('payment.gateways.tabby', []) ?? [];
        $filtered = [];

        foreach ($dbConfig as $k => $v) {
            if ($v !== null && $v !== '') {
                $filtered[$k] = $v;
            }
        }
        $this->config = array_merge($envConfig, $filtered);
        $this->baseUrl = $this->config['base_url'] ?? 'https://api.tabby.ai/api/v2';
    }

    public function getName(): string
    {
        return 'tabby';
    }

    public function isConfigured(): bool
    {
        return ($this->config['enabled'] ?? false)
            && ! empty($this->config['public_key'])
            && ! empty($this->config['secret_key']);
    }

    /**
     * Create a Tabby checkout session.
     */
    public function initiate(array $data): array
    {
        try {
            $currency = (string) ($data['currency'] ?? PaymentSettings::getPlatformCurrency());
            $countryCode = strtoupper((string) ($data['country_code'] ?? CountryCurrencyRegistry::DEFAULT_COUNTRY));
            $phoneFallback = (string) (CountryCurrencyRegistry::countryMeta($countryCode)['phone_example'] ?? '+966500000000');
            $countryName = (string) (CountryCurrencyRegistry::countryMeta($countryCode)['name_en'] ?? 'Saudi Arabia');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['secret_key'],
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/checkout", [
                'payment' => [
                    'amount' => (string) $data['amount'],
                    'currency' => $currency,
                    'description' => $data['description'] ?? 'Subscription Payment',
                    'buyer' => [
                        'phone' => $data['customer_phone'] ?? $phoneFallback,
                        'email' => $data['customer_email'] ?? 'customer@example.com',
                        'name' => $data['customer_name'] ?? 'Customer',
                    ],
                    'buyer_history' => [
                        'registered_since' => now()->subMonths(6)->toIso8601String(),
                        'loyalty_level' => 0,
                    ],
                    'order' => [
                        'reference_id' => $data['reference'] ?? uniqid('sub_'),
                        'items' => [
                            [
                                'title' => $data['description'] ?? 'Subscription',
                                'quantity' => 1,
                                'unit_price' => (string) $data['amount'],
                                'category' => 'subscription',
                            ],
                        ],
                    ],
                    'shipping_address' => [
                        'city' => $data['city'] ?? ($countryCode === 'SA' ? 'Riyadh' : 'Dubai'),
                        'address' => $data['address'] ?? $countryName,
                        'zip' => $data['zip'] ?? '12345',
                    ],
                ],
                'lang' => $data['lang'] ?? 'ar',
                'merchant_code' => $this->config['merchant_code'] ?? 'default',
                'merchant_urls' => [
                    'success' => $data['callback_url'] ?? url(config('payment.callback_url')),
                    'cancel' => $data['cancel_url'] ?? url(config('payment.failed_url')),
                    'failure' => $data['failure_url'] ?? url(config('payment.failed_url')),
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'gateway' => 'tabby',
                    'session_id' => $result['id'] ?? null,
                    'payment_id' => $result['payment']['id'] ?? null,
                    'payment_url' => $result['configuration']['available_products']['installments'][0]['web_url'] ?? null,
                    'status' => $result['status'] ?? 'created',
                ];
            }

            Log::error('Tabby initiate failed', ['response' => $response->json()]);

            return [
                'success' => false,
                'message' => $response->json('error') ?? 'Failed to create Tabby session',
            ];
        } catch (Exception $e) {
            Log::error('Tabby initiate exception', ['error' => $e->getMessage()]);

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
            $paymentId = $data['payment_id'] ?? $data['id'] ?? null;

            if (! $paymentId) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Payment ID not provided',
                ];
            }

            $payment = $this->getPayment($paymentId);

            if (! $payment) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => 'Payment not found',
                ];
            }

            $status = strtolower($payment['status'] ?? 'failed');

            return [
                'success' => in_array($status, ['authorized', 'closed']),
                'payment_id' => $paymentId,
                'status' => $status,
                'amount' => $payment['amount'] ?? 0,
                'currency' => $payment['currency'] ?? 'SAR',
                'payment_method' => 'tabby',
                'raw_response' => $payment,
            ];
        } catch (Exception $e) {
            Log::error('Tabby verification failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment details.
     */
    public function getPayment(string $paymentId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['secret_key'],
            ])->get("{$this->baseUrl}/payments/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (Exception $e) {
            Log::error('Tabby getPayment exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Refund payment.
     */
    public function refund(string $paymentId, ?float $amount = null): array
    {
        try {
            $payload = [];

            if ($amount !== null) {
                $payload['amount'] = (string) $amount;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['secret_key'],
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/payments/{$paymentId}/refunds", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json('id'),
                    'message' => 'Refund processed successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('error') ?? 'Refund failed',
            ];
        } catch (Exception $e) {
            Log::error('Tabby refund failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
