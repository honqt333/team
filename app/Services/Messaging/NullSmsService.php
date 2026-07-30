<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\Credits\TenantSmsBalance;
use App\Models\Integration\Integration;

/**
 * Phase 10 — test / no-op SmsService.
 *
 * The real SmsService talks to a configured
 * Integration (Unifonic / Twilio / Msegat) and burns
 * tenant SMS credits. In tests we never want a
 * network call AND we never want a missing-integration
 * exception to break the controller path.
 *
 * The AppServiceProvider binds THIS implementation
 * in APP_ENV=testing. Tests that want to assert on
 * the SmsService call itself rebind the interface
 * via `$this->app->instance(SmsService::class, $mock)`.
 *
 * The Null implementation:
 *   - Returns a successful result with a fake
 *     provider_message_id.
 *   - Does NOT write to SmsUsageLog or
 *     TenantSmsBalance (no test pollution).
 *   - Does NOT throw on a missing Integration (the
 *     Phase 9 controller path triggers a send from
 *     every cancel/reschedule confirmation, and a
 *     missing-integration 500 would break the entire
 *     Phase 9 test suite).
 *
 * The shape of the returned array matches SmsService
 * exactly so the SendAppointmentMessage job reads the
 * same fields.
 */
class NullSmsService extends SmsService
{
    public function __construct()
    {
        // Intentionally do NOT call parent::__construct()
        // — we never want to resolve the default
        // Integration, because in test environments
        // there isn't one.
    }

    public function send(string $to, string $message, ?int $tenantId = null): array
    {
        return [
            'success' => true,
            'message_id' => 'null-'.bin2hex(random_bytes(4)),
            'provider' => 'null',
        ];
    }

    public function isReady(): bool
    {
        return true;
    }

    public function setIntegration(?Integration $integration): void
    {
        // No-op.
    }
}
