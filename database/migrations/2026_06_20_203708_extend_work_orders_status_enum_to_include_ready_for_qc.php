<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The MySQL enums were missing values that the PHP models emit:
     *   - work_orders.status         needed `ready_for_qc` (auto-set by
     *     WorkOrder::saving once all items complete)
     *   - work_order_items.status    needed `ready_for_qc` (operator can
     *     transition a single service into QC independently of the WO)
     *
     * Every status transition on the LAST service of a work order hit
     * "Data truncated for column 'status'" and silently rolled back, so the
     * user saw a red error toast even though the DB write partially
     * succeeded (the WorkOrderItem row updated, the WorkOrder.status did not).
     *
     * SQLite stores these as TEXT — no enum constraint to widen — so the
     * migration is a no-op there (matches the convention of the original
     * 2025_12_28 enum migrations).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE work_orders
            MODIFY COLUMN status
            ENUM('draft','open','in_progress','on_hold','ready_for_qc','done','cancelled')
            NOT NULL DEFAULT 'draft'
        ");

        DB::statement("
            ALTER TABLE work_order_items
            MODIFY COLUMN status
            ENUM('pending','in_progress','ready_for_qc','on_hold','completed','cancelled')
            NOT NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('work_orders')->where('status', 'ready_for_qc')->update(['status' => 'in_progress']);
        DB::table('work_order_items')->where('status', 'ready_for_qc')->update(['status' => 'in_progress']);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE work_orders
            MODIFY COLUMN status
            ENUM('draft','open','in_progress','on_hold','done','cancelled')
            NOT NULL DEFAULT 'draft'
        ");

        DB::statement("
            ALTER TABLE work_order_items
            MODIFY COLUMN status
            ENUM('pending','in_progress','on_hold','completed','cancelled')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
