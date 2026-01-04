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
        Schema::create('hr_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->onDelete('cascade');
            
            // Grace period (فترة السماح)
            $table->integer('grace_period_minutes')->default(10);
            
            // Deductions (الخصومات)
            $table->decimal('late_deduction_per_minute', 8, 2)->default(0);
            $table->decimal('absence_deduction_per_day', 10, 2)->default(0);
            
            // Overtime (العمل الإضافي)
            $table->decimal('overtime_rate_per_hour', 8, 2)->default(0);
            $table->boolean('overtime_enabled')->default(true);
            
            // Auto-marking settings
            $table->boolean('auto_mark_absent')->default(false);
            $table->time('absence_cutoff_time')->nullable(); // إذا لم يحضر قبل هذا الوقت يعتبر غائب
            
            // Working days (أيام العمل)
            $table->json('working_days')->nullable(); // [0,1,2,3,4] = أحد-خميس
            
            $table->timestamps();
            
            // Ensure one settings per center
            $table->unique('center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_settings');
    }
};
