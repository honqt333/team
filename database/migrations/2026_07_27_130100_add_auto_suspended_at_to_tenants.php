<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 7.7: Track when (and why) a tenant was auto-suspended by
     * the dunning system, so we don't re-suspend and so the admin UI
     * can show the reason distinctly from manual suspensions.
     *
     * `auto_suspended_at` is set ONLY by DunningAutoSuspend command.
     * Manual admin suspension still uses `suspended_at`.
     * `suspension_reason` is reused (existing column) — we use the
     * sentinel value 'auto_suspend_dunning_tier_4' to distinguish.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('auto_suspended_at')
                ->nullable()
                ->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('auto_suspended_at');
        });
    }
};
