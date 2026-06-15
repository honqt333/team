<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_order_item_parts', function (Blueprint $table) {
            if (!Schema::hasColumn('work_order_item_parts', 'issued_qty')) {
                $table->decimal('issued_qty', 10, 2)->default(0.00)->after('qty');
            }
            if (!Schema::hasColumn('work_order_item_parts', 'returned_qty')) {
                $table->decimal('returned_qty', 10, 2)->default(0.00)->after('issued_qty');
            }
        });

        // Seed existing data: set issued_qty = qty
        DB::table('work_order_item_parts')
            ->where('issued_qty', 0)
            ->update([
                'issued_qty' => DB::raw('qty')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_item_parts', function (Blueprint $table) {
            $columns = ['issued_qty', 'returned_qty'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('work_order_item_parts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
