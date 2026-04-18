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
        Schema::table('quote_parts', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(true)->after('total');
            $table->decimal('tax_rate_snapshot', 10, 2)->default(0)->after('is_taxable');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate_snapshot');
            $table->decimal('total_excl_tax', 10, 2)->default(0)->after('tax_amount');
            $table->decimal('total_incl_tax', 10, 2)->default(0)->after('total_excl_tax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_parts', function (Blueprint $table) {
            $table->dropColumn([
                'is_taxable',
                'tax_rate_snapshot',
                'tax_amount',
                'total_excl_tax',
                'total_incl_tax'
            ]);
        });
    }
};
