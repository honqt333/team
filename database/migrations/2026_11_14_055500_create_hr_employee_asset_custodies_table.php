<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_asset_custodies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('asset_name');
            $table->string('category', 50)->default('tool_kit');
            $table->string('serial_number', 100)->nullable();
            $table->decimal('estimated_value', 12, 2)->default(0);
            $table->string('condition_on_issue', 30)->default('good');
            $table->string('condition_on_return', 30)->nullable();
            $table->date('issued_date');
            $table->date('returned_date')->nullable();
            $table->string('status', 30)->default('issued');
            $table->longText('issue_signature')->nullable();
            $table->longText('return_signature')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'center_id', 'employee_id'], 'idx_custody_tenant_center_emp');
            $table->index('status', 'idx_custody_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_asset_custodies');
    }
};
