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
        Schema::table('departments', function (Blueprint $table) {
            // Add a regular index on center_id to support the foreign key constraint
            // This is needed because MySQL was likely using the unique index for the FK
            $table->index('center_id');
            
            // Drop the unique index that prevents soft deletes from working correctly with same names
            $table->dropUnique('departments_center_id_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // Re-add the unique index
            $table->unique(['center_id', 'name_ar'], 'departments_center_id_name_unique');
            
            // Drop the regular index we added
            $table->dropIndex(['center_id']);
        });
    }
};
