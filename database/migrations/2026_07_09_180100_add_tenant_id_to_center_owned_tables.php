<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Track E — Phase 1 (part 2)
 * Add tenant_id to tables that already carry center_id but lack tenant_id:
 *   center_addresses, center_working_hours, warehouses, hr_attendance_settings.
 * The tenant_id is backfilled from centers.tenant_id.
 *
 * Cross-driver note:
 *   MySQL supports UPDATE … JOIN; SQLite uses UPDATE … FROM (or sub-select).
 *   We emit the appropriate statement per driver.
 */
return new class extends Migration
{
    private function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    private function backfillFromCenter(string $table): void
    {
        if ($this->driver() === 'mysql') {
            DB::statement("UPDATE {$table} t
                JOIN centers c ON c.id = t.center_id
                SET t.tenant_id = c.tenant_id");
        } else {
            DB::statement("UPDATE {$table}
                SET tenant_id = (SELECT c.tenant_id FROM centers c WHERE c.id = {$table}.center_id)
                WHERE tenant_id IS NULL");
        }
    }

    public function up(): void
    {
        $tables = ['center_addresses', 'center_working_hours', 'warehouses', 'hr_attendance_settings'];

        foreach ($tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('center_id')->constrained()->nullOnDelete();
                $table->index(['tenant_id', 'center_id']);
            });
            $this->backfillFromCenter($t);
            Schema::table($t, function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['center_addresses', 'center_working_hours', 'warehouses', 'hr_attendance_settings'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                try {
                    $table->dropForeign(['tenant_id']);
                } catch (Throwable $e) {
                }
                $table->dropColumn('tenant_id');
            });
        }
    }
};
