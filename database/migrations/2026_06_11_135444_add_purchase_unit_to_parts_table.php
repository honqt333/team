<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->foreignId('purchase_unit_id')
                  ->nullable()
                  ->after('unit_id')
                  ->constrained('inventory_units')
                  ->nullOnDelete();

            $table->decimal('purchase_conversion_factor', 12, 4)
                  ->default(1)
                  ->after('purchase_unit_id')
                  ->comment('How many stock units = 1 purchase unit. e.g. 1 carton = 12 pieces');
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropForeign(['purchase_unit_id']);
            $table->dropColumn(['purchase_unit_id', 'purchase_conversion_factor']);
        });
    }
};
