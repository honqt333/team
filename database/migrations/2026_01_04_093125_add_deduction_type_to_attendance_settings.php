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
        Schema::table('hr_attendance_settings', function (Blueprint $table) {
            // Check if column exists before renaming (idempotency)
            if (Schema::hasColumn('hr_attendance_settings', 'absence_deduction_per_day')) {
                $table->renameColumn('absence_deduction_per_day', 'absence_deduction_value');
            }
            if (!Schema::hasColumn('hr_attendance_settings', 'absence_deduction_type')) {
                $table->enum('absence_deduction_type', ['fixed', 'percentage'])->default('fixed')->after('late_deduction_per_minute');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_attendance_settings', function (Blueprint $table) {
            if (Schema::hasColumn('hr_attendance_settings', 'absence_deduction_value')) {
                $table->renameColumn('absence_deduction_value', 'absence_deduction_per_day');
            }
            if (Schema::hasColumn('hr_attendance_settings', 'absence_deduction_type')) {
                $table->dropColumn('absence_deduction_type');
            }
        });
    }
};
