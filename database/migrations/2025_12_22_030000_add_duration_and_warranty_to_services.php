<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Rename estimated_minutes to duration_value for flexibility
            $table->renameColumn('estimated_minutes', 'duration_value');
        });

        Schema::table('services', function (Blueprint $table) {
            // Duration unit: minutes, hours, days, weeks
            $table->string('duration_unit')->default('minutes')->after('duration_value');
            
            // Warranty fields
            $table->integer('warranty_value')->nullable()->after('duration_unit');
            $table->string('warranty_unit')->nullable()->after('warranty_value'); // days, weeks, months, years
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['duration_unit', 'warranty_value', 'warranty_unit']);
            $table->renameColumn('duration_value', 'estimated_minutes');
        });
    }
};
