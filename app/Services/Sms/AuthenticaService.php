<?php

namespace App\Services\Sms;

use App\Models\Integration\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthenticaService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.authentica.sa';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Create instance from Integration.
     */
    public static function fromIntegration(Integration $integration): self
    {
        return new self($integration->config['api_key'] ?? '');
    }

    /**
     * Send OTP via SMS.
     *
     * @param string $phone Phone number with country code (e.g., +966512345678)
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function sendOtp(string $phone): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Authorization' => $this->apiKey,
            ])->post("{$this->baseUrl}/api/v2/send-otp", [
                'method' => 'sms',
                'phone' => $phone,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'data' => $response->json(),
                ];
            }

            Log::error('Authentica sendOtp failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to send OTP',
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Authentica sendOtp exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Verify OTP.
     *
     * @param string $phone Phone number with country code
     * @param string $otp OTP code entered by user
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function verifyOtp(string $phone, string $otp): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Authorization' => $this->apiKey,
            ])->post("{$this->baseUrl}/api/v2/verify-otp", [
                'phone' => $phone,
                'otp' => $otp,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Authentica returns 'status' not 'valid' or 'success'
                $isValid = ($data['status'] ?? false) || ($data['valid'] ?? false) || ($data['success'] ?? false);
                
                return [
                    'success' => $isValid,
                    'message' => $isValid ? 'OTP verified successfully' : ($data['message'] ?? 'Invalid OTP'),
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Verification failed',
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Authentica verifyOtp exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Send OTP via WhatsApp.
     *
     * @param string $phone Phone number with country code
     * @return array
     */
    public function sendOtpWhatsApp(string $phone): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Authorization' => $this->apiKey,
            ])->post("{$this->baseUrl}/api/v2/send-otp", [
                'method' => 'whatsapp',
                'phone' => $phone,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'OTP sent via WhatsApp',
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to send OTP',
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get account balance from Authentica.
     *
     * @return array ['success' => bool, 'balance' => int|null, 'message' => string, 'data' => array]
     */
    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Authorization' => $this->apiKey,
            ])->get("{$this->baseUrl}/api/v2/balance");

            if ($response->successful()) {
                $data = $response->json();
                // Authentica returns balance in data.balance
                $balance = $data['data']['balance'] ?? $data['balance'] ?? $data['credits'] ?? $data['remaining'] ?? null;
                return [
                    'success' => true,
                    'balance' => $balance,
                    'message' => 'Balance retrieved successfully',
                    'data' => $data['data'] ?? $data,
                ];
            }

            Log::error('Authentica getBalance failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'balance' => null,
                'message' => $response->json('message') ?? 'Failed to get balance',
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Authentica getBalance exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'balance' => null,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }
}
