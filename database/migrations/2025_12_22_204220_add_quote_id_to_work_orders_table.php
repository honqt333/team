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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('quote_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained()
                ->nullOnDelete();
            
            // Unique constraint: one work order per quote
            $table->unique('quote_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropUnique(['quote_id']);
            $table->dropColumn('quote_id');
        });
    }
};
