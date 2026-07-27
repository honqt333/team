<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 7.7: Auto-suspend configuration.
     *
     * `auto_suspend_after_days` = number of days after the LAST tier_4
     * reminder was sent with no payment before the tenant's account
     * is auto-suspended. Default 7. Null disables the feature
     * (admin must suspend manually).
     */
    public function up(): void
    {
        Schema::table('dunning_configs', function (Blueprint $table) {
            $table->unsignedSmallInteger('auto_suspend_after_days')
                ->nullable()
                ->after('quiet_hours_end');
        });
    }

    public function down(): void
    {
        Schema::table('dunning_configs', function (Blueprint $table) {
            $table->dropColumn('auto_suspend_after_days');
        });
    }
};
