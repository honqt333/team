<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10 hot-fix — appointments_message_deliveries.
 *
 * The original migration created a unique index on
 * (appointment_id, message_type). That index was
 * the dedup guarantee for "we sent this message
 * type for this appointment exactly once". It is
 * wrong for the reschedule case: a reschedule
 * changes the appointment's scheduled_at, and the
 * customer should receive ANOTHER "your appointment
 * has been moved" notification. The old index
 * prevents that. The reminder job is also hurt by
 * it: a confirmation row from booking time blocks
 * a 24h reminder for the same appointment, because
 * the index says "this message type is already
 * done".
 *
 * The right identity for a delivery row is
 * (appointment, event-kind, scheduled_at_snapshot).
 * A reschedule to time T1 produces one
 * booking_rescheduled row with snapshot=T1. A
 * reschedule to time T2 produces ANOTHER row with
 * snapshot=T2. The reminder for T1 is a different
 * row from the reminder for T2, so each can fire
 * once and the customer gets accurate messages.
 *
 * Why a snapshot and not a row per reschedule
 * counter: the snapshot is the value the customer
 * sees in the message ("your appointment on
 * 2026-08-15 14:00 has been moved to 2026-08-15
 * 16:00"). The job needs that snapshot to render
 * the body, and to detect that the appointment has
 * since been moved again (skip the message).
 *
 * The migration does THREE things:
 *   1. Add scheduled_at_snapshot as a nullable
 *      timestamp. Nullable so the unique index
 *      behaves correctly for old rows that have no
 *      snapshot (treated as a single "any-time"
 *      bucket — sufficient for one-off transactional
 *      messages like OTP that don't carry a time).
 *   2. Drop the old unique index on
 *      (appointment_id, message_type).
 *   3. Add the new unique index on
 *      (appointment_id, message_type, scheduled_at_snapshot).
 *      SQLite stores NULL as distinct in unique
 *      indexes, so multiple rows with NULL snapshots
 *      for the same (appt, type) are allowed —
 *      which is fine: a NULL snapshot means
 *      "no time-bound event".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_message_deliveries', function (Blueprint $table) {
            $table->timestamp('scheduled_at_snapshot')->nullable()->after('idempotency_key');

            $table->dropUnique('appt_msg_delivery_appt_type_unique');

            $table->unique(
                ['appointment_id', 'message_type', 'scheduled_at_snapshot'],
                'appt_msg_delivery_event_unique'
            );

            // The reminder scan's hot path is now
            // "find rows for (appt, type, snapshot)
            // where status != sent/failed". The
            // existing (tenant_id, message_type,
            // status) index covers the cross-tenant
            // reminder dispatcher; we add an
            // (appointment_id, message_type,
            // scheduled_at_snapshot) helper for
            // the job's "have I sent this exact
            // event?" check.
            $table->index(
                ['appointment_id', 'message_type', 'scheduled_at_snapshot'],
                'appt_msg_delivery_appt_type_snap_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('appointment_message_deliveries', function (Blueprint $table) {
            $table->dropIndex('appt_msg_delivery_appt_type_snap_idx');
            $table->dropUnique('appt_msg_delivery_event_unique');
            $table->dropColumn('scheduled_at_snapshot');

            $table->unique(
                ['appointment_id', 'message_type'],
                'appt_msg_delivery_appt_type_unique'
            );
        });
    }
};
