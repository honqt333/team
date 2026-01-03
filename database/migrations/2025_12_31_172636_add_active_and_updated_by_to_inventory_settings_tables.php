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
        Schema::table('inventory_units', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_units', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name_en');
            }
            if (!Schema::hasColumn('inventory_units', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('inventory_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name_en');
            }
            if (!Schema::hasColumn('inventory_categories', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['is_active', 'updated_by']);
        });

        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['is_active', 'updated_by']);
        });
    }
};
