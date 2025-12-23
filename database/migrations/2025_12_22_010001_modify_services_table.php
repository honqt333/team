<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Add new columns
            $table->foreignId('department_id')->nullable()->after('center_id')->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable()->after('name');
            $table->integer('estimated_minutes')->nullable()->after('default_price');
            $table->string('type')->default('internal')->after('estimated_minutes'); // internal, external
            $table->boolean('requires_approval')->default(false)->after('type');
            $table->softDeletes();
            
            // Rename column
            $table->renameColumn('default_price', 'base_price');
            
            // Add unique constraint
            $table->unique(['center_id', 'name']);
        });

        // Drop category column in separate statement (SQLite compatibility)
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->index('category');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['center_id', 'name']);
            $table->renameColumn('base_price', 'default_price');
            $table->dropSoftDeletes();
            $table->dropColumn(['department_id', 'description', 'estimated_minutes', 'type', 'requires_approval']);
        });
    }
};
