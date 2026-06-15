<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_order_item_technician', function (Blueprint $table) {
            if (!Schema::hasColumn('work_order_item_technician', 'share')) {
                $table->decimal('share', 5, 2)->default(100.00)->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_item_technician', function (Blueprint $table) {
            if (Schema::hasColumn('work_order_item_technician', 'share')) {
                $table->dropColumn('share');
            }
        });
    }
};
