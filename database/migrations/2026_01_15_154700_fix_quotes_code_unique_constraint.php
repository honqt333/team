<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix: Change quotes code unique constraint from global to per-center.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Drop the global unique constraint on code
            $table->dropUnique(['code']);
            
            // Add composite unique constraint per tenant and center
            $table->unique(['tenant_id', 'center_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'center_id', 'code']);
            $table->unique(['code']);
        });
    }
};
