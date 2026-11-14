<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_offboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('termination_type', 40)->default('employer_termination');
            $table->date('hire_date');
            $table->date('termination_date');
            $table->unsignedInteger('service_days')->default(0);
            $table->decimal('service_years', 8, 4)->default(0);

            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('allowances_amount', 12, 2)->default(0);
            $table->decimal('calculation_salary', 12, 2)->default(0);

            $table->decimal('eosb_multiplier', 5, 4)->default(1);
            $table->decimal('full_eosb_amount', 12, 2)->default(0);
            $table->decimal('eosb_amount', 12, 2)->default(0);

            $table->decimal('unused_leave_days', 6, 2)->default(0);
            $table->decimal('unused_leave_payout', 12, 2)->default(0);
            $table->decimal('advances_deduction', 12, 2)->default(0);
            $table->decimal('custody_deduction', 12, 2)->default(0);
            $table->decimal('other_additions', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('net_settlement', 12, 2)->default(0);

            $table->boolean('custody_cleared')->default(false);
            $table->boolean('finance_cleared')->default(false);
            $table->boolean('it_access_revoked')->default(false);

            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->longText('employee_signature')->nullable();
            $table->longText('hr_signature')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'center_id', 'employee_id'], 'hr_offboarding_scope_idx');
            $table->index(['employee_id', 'status'], 'hr_offboarding_emp_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_offboardings');
    }
};
