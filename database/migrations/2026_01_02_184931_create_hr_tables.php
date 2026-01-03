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
        // أنواع الموظفين
        Schema::create('hr_employee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // مسميات الوظائف
        Schema::create('hr_job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // العلاوات
        Schema::create('hr_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // الحسومات
        Schema::create('hr_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // الموظفين
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // البيانات الأساسية
            $table->string('employee_number')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('national_id')->nullable();
            
            // البيانات الوظيفية
            $table->foreignId('job_title_id')->nullable()->constrained('hr_job_titles')->nullOnDelete();
            $table->foreignId('employee_type_id')->nullable()->constrained('hr_employee_types')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->date('hire_date')->nullable();
            $table->date('contract_end_date')->nullable();
            
            // البيانات المالية
            $table->decimal('base_salary', 10, 2)->default(0);
            
            // العمولات (جاهز للربط لاحقاً)
            $table->boolean('commission_enabled')->default(false);
            $table->enum('commission_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('commission_rate', 10, 2)->nullable();
            
            // الحالة
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
        });

        // علاوات الموظف
        Schema::create('hr_employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('allowance_id')->constrained('hr_allowances')->cascadeOnDelete();
            $table->decimal('custom_amount', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'allowance_id']);
        });

        // حسومات الموظف
        Schema::create('hr_employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('deduction_id')->constrained('hr_deductions')->cascadeOnDelete();
            $table->decimal('custom_amount', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'deduction_id']);
        });

        // سجل الحضور
        Schema::create('hr_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'leave', 'holiday'])->default('present');
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('device_id')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
        });

        // كشوف الرواتب
        Schema::create('hr_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period'); // 2026-01
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->decimal('total_salaries', 12, 2)->default(0);
            $table->decimal('total_allowances', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'center_id', 'period']);
        });

        // تفاصيل رواتب الموظفين
        Schema::create('hr_payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('hr_payrolls')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            
            $table->decimal('base_salary', 10, 2)->default(0);
            $table->decimal('total_allowances', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('overtime_amount', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            
            $table->integer('working_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->decimal('absent_deduction', 10, 2)->default(0);
            
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // المدفوعات الأخرى (سلف، مكافآت، خصومات استثنائية)
        Schema::create('hr_other_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->enum('type', ['advance', 'bonus', 'deduction', 'reimbursement'])->default('advance');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_other_payments');
        Schema::dropIfExists('hr_payroll_items');
        Schema::dropIfExists('hr_payrolls');
        Schema::dropIfExists('hr_attendance');
        Schema::dropIfExists('hr_employee_deductions');
        Schema::dropIfExists('hr_employee_allowances');
        Schema::dropIfExists('hr_employees');
        Schema::dropIfExists('hr_deductions');
        Schema::dropIfExists('hr_allowances');
        Schema::dropIfExists('hr_job_titles');
        Schema::dropIfExists('hr_employee_types');
    }
};
