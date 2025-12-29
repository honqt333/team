<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_item_parts', function (Blueprint $table) {
            // Add missing work_order_id if not exists
            if (!Schema::hasColumn('work_order_item_parts', 'work_order_id')) {
                $table->foreignId('work_order_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            
            // Add part_id for linking to parts catalog (nullable for external parts)
            if (!Schema::hasColumn('work_order_item_parts', 'part_id')) {
                $table->foreignId('part_id')->nullable()->after('center_id')->constrained()->nullOnDelete();
            }
            
            // Add warehouse_id for issue tracking
            if (!Schema::hasColumn('work_order_item_parts', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('part_id')->constrained()->nullOnDelete();
            }
            
            // Inventory move tracking (for auto-issue)
            if (!Schema::hasColumn('work_order_item_parts', 'inventory_move_id')) {
                $table->foreignId('inventory_move_id')->nullable()->after('notes')->constrained()->nullOnDelete();
            }
            
            // Cost snapshot at time of issue
            if (!Schema::hasColumn('work_order_item_parts', 'cost_snapshot')) {
                $table->decimal('cost_snapshot', 12, 4)->nullable()->after('inventory_move_id');
            }
            
            // Issue timestamp
            if (!Schema::hasColumn('work_order_item_parts', 'issued_at')) {
                $table->timestamp('issued_at')->nullable()->after('cost_snapshot');
            }
            
            // Status for tracking issued/reversed/cancelled
            if (!Schema::hasColumn('work_order_item_parts', 'status')) {
                $table->enum('status', ['pending', 'issued', 'reversed', 'cancelled'])->default('pending')->after('issued_at');
            }
            
            // Reversal tracking
            if (!Schema::hasColumn('work_order_item_parts', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('work_order_item_parts', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('work_order_item_parts', 'reversal_move_id')) {
                $table->foreignId('reversal_move_id')->nullable()->after('reversed_by')->constrained('inventory_moves')->nullOnDelete();
            }
            
            // Soft deletes
            if (!Schema::hasColumn('work_order_item_parts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_order_item_parts', function (Blueprint $table) {
            $columns = [
                'part_id', 'warehouse_id', 'inventory_move_id', 'cost_snapshot',
                'issued_at', 'status', 'reversed_at', 'reversed_by', 'reversal_move_id', 'deleted_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('work_order_item_parts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
