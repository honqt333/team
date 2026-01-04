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
        Schema::table('hr_employees', function (Blueprint $table) {
            // Drop the global unique index
            $table->dropUnique(['employee_number']);
            // Add scoped unique index
            $table->unique(['tenant_id', 'employee_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'employee_number']);
            $table->unique('employee_number');
        });
    }
};
