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
        Schema::table('tenant_tax_settings', function (Blueprint $table) {
            // New separate VAT rates for services and parts
            $table->decimal('services_vat_rate', 5, 2)->default(15.00)->after('vat_rate');
            $table->decimal('parts_vat_rate', 5, 2)->default(15.00)->after('services_vat_rate');
            
            // Inclusive/Exclusive toggles separate for services and parts
            $table->boolean('services_inclusive')->default(false)->after('parts_vat_rate');
            $table->boolean('parts_inclusive')->default(false)->after('services_inclusive');
            
            // Show amount before VAT option
            $table->boolean('show_amount_before_vat')->default(true)->after('parts_inclusive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_tax_settings', function (Blueprint $table) {
            $table->dropColumn([
                'services_vat_rate',
                'parts_vat_rate',
                'services_inclusive',
                'parts_inclusive',
                'show_amount_before_vat',
            ]);
        });
    }
};
