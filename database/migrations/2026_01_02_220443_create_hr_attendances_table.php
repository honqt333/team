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
        Schema::create('hr_attendances', function (Blueprint $table) {
            $table->id();
            
            // Standard Tenancy Columns
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('center_id')->constrained('centers');
            
            // Employee Relation
            $table->foreignId('employee_id')->constrained('hr_employees');
            
            // Attendance Data
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'leave', 'holiday'])->default('absent');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for improved search performance
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_attendances');
    }
};
