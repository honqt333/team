<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_attendance_settings', function (Blueprint $table) {
            $table->decimal('max_deduction_percentage', 5, 2)
                ->default(50.00)
                ->after('geofence_radius_meters');
        });
    }

    public function down(): void
    {
        Schema::table('hr_attendance_settings', function (Blueprint $table) {
            $table->dropColumn('max_deduction_percentage');
        });
    }
};
