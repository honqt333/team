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
        // Update employee_allowances pivot table
        Schema::table('hr_employee_allowances', function (Blueprint $table) {
            $table->enum('period_type', ['one_time', 'fixed_period', 'indefinite'])->default('indefinite')->after('custom_amount');
            $table->date('start_date')->nullable()->after('period_type');
            $table->date('end_date')->nullable()->after('start_date');
            $table->boolean('is_active')->default(true)->after('end_date');
        });

        // Update employee_deductions pivot table
        Schema::table('hr_employee_deductions', function (Blueprint $table) {
            $table->enum('period_type', ['one_time', 'fixed_period', 'indefinite'])->default('indefinite')->after('custom_amount');
            $table->date('start_date')->nullable()->after('period_type');
            $table->date('end_date')->nullable()->after('start_date');
            $table->boolean('is_active')->default(true)->after('end_date');
        });

        // Update allowances table to add flexible option
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->boolean('is_flexible')->default(false)->after('amount');
        });

        // Update deductions table to add flexible option
        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->boolean('is_flexible')->default(false)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_allowances', function (Blueprint $table) {
            $table->dropColumn(['period_type', 'start_date', 'end_date', 'is_active']);
        });

        Schema::table('hr_employee_deductions', function (Blueprint $table) {
            $table->dropColumn(['period_type', 'start_date', 'end_date', 'is_active']);
        });

        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->dropColumn('is_flexible');
        });

        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn('is_flexible');
        });
    }
};
