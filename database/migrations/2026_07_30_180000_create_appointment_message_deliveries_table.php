<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10 — appointment message deliveries.
 *
 * One row per outbound transactional SMS dispatched
 * to a customer about an appointment. The table is the
 * SOLE source of truth for:
 *
 *   1. Idempotency: a UNIQUE (appointment_id, message_type)
 *      constraint stops a duplicate job from sending the
 *      same message twice. Reminder jobs rely on this
 *      for the "once per (appointment, type) per day"
 *      guarantee.
 *
 *   2. Audit: the controller and the operator can see
 *      what was sent, when, and what the provider's
 *      message id was. No PII is stored beyond the
 *      customer id (the phone number lives in
 *      customers.phone, not in this table).
 *
 *   3. Privacy: the `phone_hash` column carries only
 *      a sha256 of the destination phone, never the
 *      raw phone. Operators with DB access can cross-
 *      reference a phone_hash with the otps table but
 *      cannot read the actual phone from the deliveries
 *      table.
 *
 *   4. Tenant isolation: every query MUST filter on
 *      tenant_id. The table is created WITHOUT a
 *      `TenantScoped` global scope so the cross-tenant
 *      job scanner can see all rows; the responsibility
 *      for tenant filtering is on every query in
 *      `AppointmentMessageDelivery::*` and the queue
 *      worker. The migration does not put a CHECK
 *      constraint on tenant_id because the FK to
 *      tenants already covers referential integrity.
 *
 *   5. Lifecycle: `attempted_at` is the first try,
 *      `sent_at` is the success marker, `failed_at`
 *      is the final-fail marker. The `attempts`
 *      counter is incremented on each retry.
 *
 * Why the `idempotency_key` column
 * --------------------------------
 * Reminder jobs scan appointments whose scheduled_at is
 * inside a 24h / 2h window. The scan can run twice
 * inside that window (e.g. if the cron skips a tick
 * and the next run races to catch up). The unique key
 * (appointment_id, message_type) prevents a second
 * insert from succeeding. `idempotency_key` is a
 * SHA-prefixed string for log lines (logs never carry
 * the appointment id alone because the appointment
 * contains customer PII via the relationship).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_message_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            // booking_confirmation | booking_rescheduled | booking_cancelled
            // reminder_24h | reminder_2h
            $table->string('message_type', 32);

            // For dedup + audit. We compute this as
            // hash('sha256', "$type:$appointment_id:$scheduled_at_iso")
            // at insert time. Two reminders for the same
            // appointment + the same planned-scheduled_at
            // collide on this key, which is what we want.
            // (After a reschedule the scheduled_at changes
            // and the key changes too — the old reminder
            // is harmless because we also filter on
            // status = scheduled | confirmed | checked_in
            // at dispatch time, so a moved appointment's
            // old reminder is dropped before send.)
            $table->string('idempotency_key', 64);

            // sha256 of the destination phone. Operators
            // can join this with customer_portal_otps
            // .phone_hash; the actual phone is never
            // written here.
            $table->string('phone_hash', 64)->nullable();

            // queued | sending | sent | failed | skipped
            $table->string('status', 16)->default('queued');

            // Provider name from the Integration table
            // (unifonic / twilio / msegat). Nullable
            // because the row exists before the job runs.
            $table->string('provider', 32)->nullable();

            // Provider-assigned message id, when known.
            $table->string('provider_message_id', 128)->nullable();

            // Number of times the job has tried to send.
            $table->unsignedTinyInteger('attempts')->default(0);

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Sanitized failure reason. Must NEVER carry
            // the raw phone or the message body. Free-text
            // but bounded to 500 chars to bound the log
            // size of the error column.
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // The single most important index: at most
            // one row per (appointment, message_type).
            // The reminder job relies on this to skip
            // rows that were already dispatched.
            $table->unique(
                ['appointment_id', 'message_type'],
                'appt_msg_delivery_appt_type_unique'
            );

            // Reminder-window scan: scheduler asks
            // "what appointments are about to start and
            // haven't received a 24h/2h reminder yet".
            $table->index(
                ['tenant_id', 'message_type', 'status'],
                'appt_msg_delivery_tenant_type_status_idx'
            );
            $table->index(
                ['appointment_id', 'status'],
                'appt_msg_delivery_appt_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_message_deliveries');
    }
};
