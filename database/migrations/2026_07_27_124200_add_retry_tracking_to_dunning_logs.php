<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.4 — retry tracking on dunning sends.
 *
 * Two new columns on dunning_reminder_logs:
 *   retry_count (int, default 0)  — how many times we've
 *     re-tried this send. Max 2 (24h + 72h), so cap at 3
 *     total attempts.
 *   next_retry_at (datetime, nullable) — when the dispatcher
 *     should next try this FAILED send. Null means "no
 *     more retries". The dispatcher picks up rows where
 *     status = FAILED AND retry_count < 3 AND next_retry_at
 *     <= now() on the next cron run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dunning_reminder_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('retry_count')->default(0)->after('error_message');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
            $table->index(['status', 'next_retry_at'], 'dunning_log_retry_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('dunning_reminder_logs', function (Blueprint $table) {
            $table->dropIndex('dunning_log_retry_lookup');
            $table->dropColumn(['retry_count', 'next_retry_at']);
        });
    }
};
