<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('hr_attendances')->nullOnDelete();

            $table->date('date');
            $table->time('requested_check_in')->nullable();
            $table->time('requested_check_out')->nullable();
            $table->text('reason');

            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'center_id', 'status'], 'hr_att_corr_scope_status_idx');
            $table->index(['employee_id', 'date'], 'hr_att_corr_emp_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_correction_requests');
    }
};
