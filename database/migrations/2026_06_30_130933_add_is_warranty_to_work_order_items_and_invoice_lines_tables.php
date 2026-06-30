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
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->boolean('is_warranty')->default(false)->after('warranty_unit_snapshot');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->boolean('is_warranty')->default(false)->after('line_total_incl_tax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropColumn('is_warranty');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn('is_warranty');
        });
    }
};
