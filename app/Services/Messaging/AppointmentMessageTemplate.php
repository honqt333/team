<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\Appointment;
use App\Models\AppointmentMessageDelivery;
use App\Models\Center;
use App\Models\TenantWebsite;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Phase 10 — render the SMS body for an appointment.
 *
 * Pure formatter. No DB writes, no provider calls.
 * Returns a struct with the body + the values used
 * (so the dispatcher can stash the rendered body in
 * the delivery row for audit if we ever need it; we
 * currently do NOT persist the body because it would
 * double-store the customer's PII through the phone
 * number on a different surface).
 *
 * Site name resolution
 * --------------------
 * - First choice: the tenant's published
 *   `TenantWebsite` `display_name` (which already
 *   prefers the localized name).
 * - Fallback: the `Tenant` name (still localized via
 *   the `app()->getLocale()` selection).
 * - Final fallback: the i18n string
 *   `customer_portal.sms.site_name_fallback`.
 *
 * Branch name
 * -----------
 * The center's localized name (display_name handles
 * the locale). Null if the appointment somehow has
 * no center (defensive — every appointment should
 * have one, but the portal does not crash if not).
 *
 * Reference
 * ---------
 * A short, customer-friendly code:
 * `KHDM-{appointment_id}` (a stable, short string
 * the customer can quote at the desk). We never
 * expose the appointment's internal sequence or
 * the work-order id; both are internal.
 *
 * Date / time formatting
 * ----------------------
 * Uses the app locale's date + time formats via
 * Carbon's localization. The site name is the
 * customer's only anchor, so we keep this short.
 */
class AppointmentMessageTemplate
{
    /**
     * Build the SMS body + a "what to display in the
     * delivery row's idempotency_key" stamp.
     *
     * @return array{body: string, idempotency_key: string}
     */
    public function render(Appointment $appointment, string $messageType): array
    {
        $siteName = $this->resolveSiteName($appointment);
        $branchName = $this->resolveBranchName($appointment);
        $date = $this->formatDate($appointment);
        $time = $this->formatTime($appointment);
        $ref = 'KHDM-'.$appointment->id;

        $body = match ($messageType) {
            AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION => __(
                'customer_portal.sms.booking_confirmation',
                [
                    'site_name' => $siteName,
                    'branch_name' => $branchName,
                    'date' => $date,
                    'time' => $time,
                    'ref' => $ref,
                ]
            ),
            AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED => __(
                'customer_portal.sms.booking_rescheduled',
                [
                    'site_name' => $siteName,
                    'branch_name' => $branchName,
                    'date' => $date,
                    'time' => $time,
                    'ref' => $ref,
                ]
            ),
            AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED => __(
                'customer_portal.sms.booking_cancelled',
                [
                    'site_name' => $siteName,
                    'branch_name' => $branchName,
                    'date' => $date,
                    'time' => $time,
                    'ref' => $ref,
                ]
            ),
            AppointmentMessageDelivery::TYPE_REMINDER_24H => __(
                'customer_portal.sms.reminder_24h',
                [
                    'site_name' => $siteName,
                    'branch_name' => $branchName,
                    'date' => $date,
                    'time' => $time,
                    'ref' => $ref,
                ]
            ),
            AppointmentMessageDelivery::TYPE_REMINDER_2H => __(
                'customer_portal.sms.reminder_2h',
                [
                    'site_name' => $siteName,
                    'branch_name' => $branchName,
                    'date' => $date,
                    'time' => $time,
                    'ref' => $ref,
                ]
            ),
            default => throw new InvalidArgumentException("Unknown message type: {$messageType}"),
        };

        // The idempotency key is a function of the
        // (type, appointment, scheduled_at) tuple. A
        // reschedule changes the scheduled_at, which
        // changes the key — so a fresh
        // booking_rescheduled row inserts cleanly even
        // though the appointment_id + message_type
        // pair would otherwise collide.
        //
        // We use a SHA-256 prefix for log lines (a
        // longer string is too noisy and a shorter one
        // could theoretically collide). The first 32
        // hex chars (128 bits) is overkill but free.
        $stamp = $appointment->scheduled_at?->toIso8601String() ?? 'unscheduled';
        $idempotencyKey = substr(
            hash('sha256', sprintf('%s:%d:%s', $messageType, $appointment->id, $stamp)),
            0,
            32
        );

        return [
            'body' => $body,
            'idempotency_key' => $idempotencyKey,
        ];
    }

    /**
     * Build a hash of the destination phone. We store
     * the hash on the delivery row, not the phone.
     * The phone itself is fetched by the dispatcher
     * directly from the customer row.
     */
    public function hashPhone(string $phone): string
    {
        return hash('sha256', $phone);
    }

    private function resolveSiteName(Appointment $appointment): string
    {
        $website = TenantWebsite::where('tenant_id', $appointment->tenant_id)->first();

        if ($website !== null && trim((string) $website->display_name) !== '') {
            return (string) $website->display_name;
        }

        $tenant = $appointment->tenant;

        if ($tenant !== null) {
            // Tenant model does not localize on its own;
            // we pick the field matching the current app
            // locale. The `name` column is the
            // canonical value.
            return (string) $tenant->name;
        }

        Log::warning('appointment_message.site_name_fallback', [
            'appointment_id' => $appointment->id,
            'tenant_id' => $appointment->tenant_id,
        ]);

        return (string) __('customer_portal.sms.site_name_fallback');
    }

    private function resolveBranchName(Appointment $appointment): string
    {
        $center = $appointment->center;

        if (! $center instanceof Center) {
            // Eager-load lazily if the caller didn't.
            $center = Center::query()
                ->where('tenant_id', $appointment->tenant_id)
                ->whereKey($appointment->center_id)
                ->first();
        }

        if ($center !== null) {
            return (string) $center->display_name;
        }

        return '—';
    }

    private function formatDate(Appointment $appointment): string
    {
        $scheduled = $appointment->scheduled_at;

        if (! $scheduled instanceof Carbon) {
            return '';
        }

        return $scheduled->translatedFormat('D j M Y');
    }

    private function formatTime(Appointment $appointment): string
    {
        $scheduled = $appointment->scheduled_at;

        if (! $scheduled instanceof Carbon) {
            return '';
        }

        return $scheduled->translatedFormat('g:i A');
    }
}
