<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            // Add tenant and center scoping
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->cascadeOnDelete();
            
            // Rename name to name_ar
            $table->renameColumn('name', 'name_ar');
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name_ar');
            $table->boolean('is_active')->default(true)->after('name_en');
            $table->integer('sort_order')->default(0)->after('is_active');
            $table->softDeletes();
            
            // Add indexes
            $table->index(['tenant_id', 'center_id']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['tenant_id', 'center_id']);
            $table->dropColumn(['name_en', 'is_active', 'sort_order']);
            $table->renameColumn('name_ar', 'name');
            $table->dropForeign(['center_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'center_id']);
        });
    }
};
