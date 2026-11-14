<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employee_offboardings', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_employee_offboardings', 'manual_salary_reason')) {
                $table->string('manual_salary_reason', 500)->nullable()->after('use_manual_salary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_employee_offboardings', function (Blueprint $table) {
            if (Schema::hasColumn('hr_employee_offboardings', 'manual_salary_reason')) {
                $table->dropColumn('manual_salary_reason');
            }
        });
    }
};
