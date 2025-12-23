<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Pricing fields
            $table->decimal('min_price', 10, 2)->default(0)->after('base_price');
            
            // Default discount settings
            $table->enum('default_discount_type', ['none', 'percentage', 'fixed'])->default('none')->after('min_price');
            $table->decimal('default_discount_value', 10, 2)->nullable()->after('default_discount_type');
            
            // Price override permission
            $table->boolean('allow_price_override')->default(false)->after('default_discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'min_price',
                'default_discount_type',
                'default_discount_value',
                'allow_price_override',
            ]);
        });
    }
};
