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
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->decimal('sale_price', 12, 2)->default(0)->after('wac_cost');
            $table->decimal('min_sale_price', 12, 2)->default(0)->after('sale_price');
            $table->decimal('min_stock', 12, 3)->default(0)->after('qty_on_hand');
            $table->string('storage_location', 50)->nullable()->after('min_stock');
            $table->boolean('allow_price_change')->default(false)->after('storage_location');
            $table->boolean('is_active')->default(true)->after('allow_price_change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropColumn([
                'sale_price',
                'min_sale_price',
                'min_stock',
                'storage_location',
                'allow_price_change',
                'is_active',
            ]);
        });
    }
};