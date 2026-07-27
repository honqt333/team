<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7.3 — quiet hours for dunning.
 *
 * Two new columns on dunning_configs:
 *   quiet_hours_start (time, nullable) — when the "do not send"
 *     window opens. Default 21:00 (9 PM).
 *   quiet_hours_end (time, nullable)   — when the window closes
 *     for the next morning. Default 08:00.
 *
 * Both nullable so admins can opt out of quiet hours entirely
 * (set both to null → the dispatcher never skips). The window
 * crosses midnight, so the dispatcher logic is "if current
 * local time is between start and end (wrapping past 24:00),
 * skip".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dunning_configs', function (Blueprint $table) {
            $table->time('quiet_hours_start')->nullable()->after('channel_matrix');
            $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');
        });
    }

    public function down(): void
    {
        Schema::table('dunning_configs', function (Blueprint $table) {
            $table->dropColumn(['quiet_hours_start', 'quiet_hours_end']);
        });
    }
};
