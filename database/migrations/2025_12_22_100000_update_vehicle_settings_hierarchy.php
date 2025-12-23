<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Vehicle Colors (Make tenant/center nullable)
        Schema::table('vehicle_colors', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->change();
            $table->foreignId('center_id')->nullable()->change();
            $table->string('source')->default('center')->after('id'); // system, tenant, center
        });

        // 2. Update Vehicle Makes (Add source)
        Schema::table('vehicle_makes', function (Blueprint $table) {
            $table->string('source')->default('center')->after('id');
        });

        // 3. Update Vehicle Models (Add source)
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->string('source')->default('center')->after('id');
        });

        // 4. Add Constraints (Optional: We can add unique indexes to prevent duplication per scope)
        // Since MySQL unique index treats NULLs as distinct, standard unique(tenant_id, center_id, name) works well 
        // for tenant/center levels, but for system (where both are null) it allows duplicates in some DBs or config.
        // However, we will enforce uniqueness mainly via application logic for now to avoid complexity with existing data
        // and just add the columns structure requested.
    }

    public function down(): void
    {
        Schema::table('vehicle_colors', function (Blueprint $table) {
            $table->dropColumn('source');
            // Reverting nullable is risky if data exists with nulls, so we skip strictly enforcing it back to NOT NULL
        });

        Schema::table('vehicle_makes', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
