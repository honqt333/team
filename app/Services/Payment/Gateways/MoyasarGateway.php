<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Models\PaymentSettings;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarGateway implements PaymentGatewayInterface
{
    protected array $config;

    protected string $baseUrl;

    public function __construct()
    {
        // Prefer DB-stored PaymentSettings (admin-managed), fall back to env/config.
        // This lets the admin enable Moyasar in /system/settings/payment
        // without restarting the app or editing .env.
        // CRITICAL: keep boolean false values (e.g. enabled=false) — array_filter
        // without an explicit callback drops ALL falsy values including false.
        $dbConfig = PaymentSettings::first()?->moyasar ?? [];
        $envConfig = config('payment.gateways.moyasar') ?? [];
        $filtered = [];

        foreach ($dbConfig as $k => $v) {
            if ($v !== null && $v !== '') {
                $filtered[$k] = $v;
            }
        }
        $this->config = array_merge($envConfig, $filtered);
        $this->baseUrl = $this->config['base_url'] ?? 'https://api.moyasar.com/v1';
    }

    public function getName(): string
    {
        return 'moyasar';
    }

    public function isConfigured(): bool
    {
        // Admin must explicitly enable the gateway AND provide valid keys.
        // Without this check, the gateway stays "configured" even when the
        // admin toggled it off in /system/settings/payment, which leaks the
        // gateway into the tenant's checkout list.
        return ($this->config['enabled'] ?? false)
            && ! empty($this->config['publishable_key'])
            && ! empty($this->config['secret_key']);
    }

    /**
     * Initiate a payment using Moyasar's Payment Form.
     */
    public function initiate(array $data): array
    {
        // Moyasar uses client-side form, return data for form rendering
        return [
            'gateway' => 'moyasar',
            'publishable_key' => $this->config['publishable_key'],
            'amount' => (int) ($data['amount'] * 100), // Moyasar expects amount in halalas
            'currency' => $data['currency'] ?? 'SAR',
            'description' => $data['description'] ?? 'Subscription Payment',
            'callback_url' => $data['callback_url'] ?? url(config('payment.callback_url')),
            'metadata' => $data['metadata'] ?? [],
        ];
    }

    /**
     * Verify payment after callback.
     */
    public function verify(array $data): array
    {
        try {
            $paymentId = $data['id'] ?? $data['payment_id'] ?? null;

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

            $status = $payment['status'] ?? 'failed';

            return [
                'success' => $status === 'paid',
                'payment_id' => $paymentId,
                'status' => $status,
                'amount' => ($payment['amount'] ?? 0) / 100, // Convert back to riyals
                'currency' => $payment['currency'] ?? 'SAR',
                'message' => $payment['source']['message'] ?? null,
                'payment_method' => $payment['source']['type'] ?? null,
                'raw_response' => $payment,
            ];
        } catch (Exception $e) {
            Log::error('Moyasar verification failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment details from Moyasar API.
     */
    public function getPayment(string $paymentId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->config['secret_key'], '')
                ->get("{$this->baseUrl}/payments/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Moyasar getPayment failed', ['response' => $response->body()]);

            return null;
        } catch (Exception $e) {
            Log::error('Moyasar getPayment exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Refund a payment.
     */
    public function refund(string $paymentId, ?float $amount = null): array
    {
        try {
            $payload = [];

            if ($amount !== null) {
                $payload['amount'] = (int) ($amount * 100);
            }

            $response = Http::withBasicAuth($this->config['secret_key'], '')
                ->post("{$this->baseUrl}/payments/{$paymentId}/refund", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json('id'),
                    'message' => 'Refund processed successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Refund failed',
            ];
        } catch (Exception $e) {
            Log::error('Moyasar refund failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
