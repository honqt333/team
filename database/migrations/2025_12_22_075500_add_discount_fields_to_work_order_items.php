<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            // Price snapshots (preserve original prices at time of adding)
            $table->decimal('base_price_snapshot', 10, 2)->default(0)->after('unit_price');
            $table->decimal('min_price_snapshot', 10, 2)->default(0)->after('base_price_snapshot');
            
            // Discount fields
            $table->enum('discount_type', ['none', 'percentage', 'fixed'])->default('none')->after('min_price_snapshot');
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_value');
            
            // Computed fields
            $table->decimal('final_unit_price', 10, 2)->default(0)->after('discount_amount');
            $table->decimal('line_total', 10, 2)->default(0)->after('final_unit_price');
            
            // Flags
            $table->boolean('price_locked')->default(false)->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'base_price_snapshot',
                'min_price_snapshot',
                'discount_type',
                'discount_value',
                'discount_amount',
                'final_unit_price',
                'line_total',
                'price_locked',
            ]);
        });
    }
};
