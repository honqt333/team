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
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('late_minutes')->default(0)->after('check_out');
            $table->unsignedSmallInteger('early_leave_minutes')->default(0)->after('late_minutes');
            $table->unsignedSmallInteger('overtime_minutes')->default(0)->after('early_leave_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'early_leave_minutes', 'overtime_minutes']);
        });
    }
};
