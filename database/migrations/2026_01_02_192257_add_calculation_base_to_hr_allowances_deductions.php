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
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->string('calculation_base')->default('base_salary')->after('amount'); // base_salary, monthly_contribution
        });

        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->string('calculation_base')->default('base_salary')->after('amount'); // base_salary, monthly_contribution
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->dropColumn('calculation_base');
        });

        Schema::table('hr_deductions', function (Blueprint $table) {
            $table->dropColumn('calculation_base');
        });
    }
};
