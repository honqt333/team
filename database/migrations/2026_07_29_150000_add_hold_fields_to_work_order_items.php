<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds per-item hold metadata so each service can be suspended
     * and resumed with its own reason + actor + timestamp. The
     * existing `suspended_status` column continues to carry the
     * pre-hold status snapshot, so we don't change that here.
     */
    public function up(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->text('hold_reason')->nullable()->after('suspended_status');
            $table->timestamp('held_at')->nullable()->after('hold_reason');
            $table->foreignId('held_by_user_id')
                ->nullable()
                ->after('held_at')
                ->constrained('users')
                ->nullOnDelete();

            // Lookup: "show me everything currently on hold in this
            // center" — used by future board filters / reports.
            $table->index(['tenant_id', 'center_id', 'status'], 'woi_tenant_center_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropIndex('woi_tenant_center_status_idx');
            $table->dropConstrainedForeignId('held_by_user_id');
            $table->dropColumn(['hold_reason', 'held_at']);
        });
    }
};
