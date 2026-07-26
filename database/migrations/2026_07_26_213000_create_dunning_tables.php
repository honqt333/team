<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dunning automation — Phase 6
 *
 * Two tables:
 *
 * 1. `dunning_configs` — one row per tenant (with a global default at
 *    tenant_id = NULL). Stores which tiers are active, which channels
 *    each tier uses, and the friendly message template. The admin
 *    edits this from /system/settings/dunning.
 *
 * 2. `dunning_reminder_logs` — every reminder that goes out (or
 *    fails) gets a row. This is the audit trail AND the idempotency
 *    guard: a unique key on (subscription_invoice_id, tier_key)
 *    prevents the same tier from being sent twice for the same
 *    invoice. If a tenant pays and then the next tier would still
 *    fire, the invoice is `paid` and the dispatcher skips it
 *    entirely.
 *
 * Why a separate log table (not just InternalNotification)?
 * InternalNotification is for in-app bell-icon notifications. The
 * dunning log needs to track channel, delivery status, error
 * messages, and the tier that was sent — that's structured data
 * that doesn't fit a polymorphic notification table. Plus, the
 * tenant's billing team may want to see their full dunning history
 * per invoice without the noise of other in-app events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dunning_configs', function (Blueprint $table) {
            $table->id();
            // NULL = global default. A real tenant_id = per-tenant
            // override. The DunningService falls back to the global
            // config when a tenant has no override row.
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->boolean('enabled')->default(true);
            // Comma-separated list of tier keys that are active, in
            // order. e.g. "tier_1,tier_2,tier_3,tier_4". We use a
            // string instead of a JSON column so admins can edit it
            // from a simple <input> without needing a JSON editor.
            $table->string('active_tiers', 100)->default('tier_1,tier_2,tier_3,tier_4');
            // Per-tier channel matrix stored as JSON. Shape:
            //   { "tier_1": ["email"], "tier_2": ["email", "in_app"],
            //     "tier_3": ["email", "in_app", "sms"],
            //     "tier_4": ["email", "in_app", "whatsapp"] }
            // Stored as text on SQLite (no native JSON), as JSON on
            // MySQL/Postgres. Cast to array in the model.
            $table->json('channel_matrix')->nullable();
            // Custom message template per tier (overrides the default
            // email body). Optional — null = use built-in template.
            $table->json('message_templates')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });

        Schema::create('dunning_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_invoice_id');
            // tier_1 (3d), tier_2 (7d), tier_3 (14d), tier_4 (30d).
            // Stored as string for forward-compatibility (we can add
            // tier_5 without a migration).
            $table->string('tier_key', 20);
            $table->string('channel', 30);
            // "sent" = delivered to the channel provider. "failed" =
            // provider rejected it (logged with error_message).
            // "skipped" = dispatcher chose not to send (opt-out,
            // already paid, duplicate detection). For "skipped" the
            // row still exists for the audit trail.
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            // IDEMPOTENCY GUARD: at most one row per
            // (invoice, tier, channel). The dispatcher catches
            // QueryException with code 23000 and treats it as
            // "already sent, skip" — this means a tier 1 reminder
            // for invoice 42 sent by email can never be sent twice
            // even if the cron runs 3x in a day.
            $table->unique(
                ['subscription_invoice_id', 'tier_key', 'channel'],
                'dunning_log_unique_per_invoice_tier_channel',
            );
            $table->index(['tenant_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_reminder_logs');
        Schema::dropIfExists('dunning_configs');
    }
};
