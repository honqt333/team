<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_employees', 'housing_allowance')) {
                $table->decimal('housing_allowance', 12, 2)->default(0)->after('base_salary');
            }
        });

        Schema::table('hr_allowances', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_allowances', 'is_housing')) {
                $table->boolean('is_housing')->default(false)->after('amount');
            }
        });

        Schema::table('hr_employee_offboardings', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_employee_offboardings', 'housing_allowance_amount')) {
                $table->decimal('housing_allowance_amount', 12, 2)->default(0)->after('allowances_amount');
            }

            if (! Schema::hasColumn('hr_employee_offboardings', 'repatriation_ticket_amount')) {
                $table->decimal('repatriation_ticket_amount', 12, 2)->default(0)->after('unused_leave_payout');
            }

            if (! Schema::hasColumn('hr_employee_offboardings', 'statutory_compensation_amount')) {
                $table->decimal('statutory_compensation_amount', 12, 2)->default(0)->after('repatriation_ticket_amount');
            }

            if (! Schema::hasColumn('hr_employee_offboardings', 'use_manual_salary')) {
                $table->boolean('use_manual_salary')->default(false)->after('calculation_salary');
            }

            if (! Schema::hasColumn('hr_employee_offboardings', 'calculated_base_salary')) {
                $table->decimal('calculated_base_salary', 12, 2)->nullable()->after('use_manual_salary');
            }

            if (! Schema::hasColumn('hr_employee_offboardings', 'calculated_allowances_amount')) {
                $table->decimal('calculated_allowances_amount', 12, 2)->nullable()->after('calculated_base_salary');
            }

            if (! Schema::hasColumn('hr_employee_offboardings', 'is_article_87')) {
                $table->boolean('is_article_87')->default(false)->after('termination_type');
            }
        });

        Schema::table('hr_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_attendances', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('notes');
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                $table->decimal('accuracy', 8, 2)->nullable()->after('longitude');
                $table->decimal('distance_meters', 10, 2)->nullable()->after('accuracy');
                $table->boolean('geofence_verified')->default(false)->after('distance_meters');
                $table->boolean('low_confidence')->default(false)->after('geofence_verified');
            }
        });

        if (! Schema::hasTable('hr_attendance_geofence_logs')) {
            Schema::create('hr_attendance_geofence_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
                $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
                $table->foreignId('attendance_id')->nullable()->constrained('hr_attendances')->nullOnDelete();
                $table->string('action', 20)->default('clock_in');
                $table->string('status', 20)->default('accepted');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('accuracy', 8, 2)->nullable();
                $table->decimal('distance_meters', 10, 2)->nullable();
                $table->unsignedInteger('radius_meters')->nullable();
                $table->boolean('geofence_verified')->default(false);
                $table->boolean('low_confidence')->default(false);
                $table->string('rejection_reason', 100)->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'center_id', 'employee_id'], 'hr_geo_log_scope_idx');
            });
        }

        Schema::table('hr_payroll_runs', function (Blueprint $table) {
            $table->unique('company_transaction_id', 'hr_payroll_runs_company_transaction_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hr_payroll_runs', function (Blueprint $table) {
            $table->dropUnique('hr_payroll_runs_company_transaction_id_unique');
        });

        Schema::dropIfExists('hr_attendance_geofence_logs');

        Schema::table('hr_attendances', function (Blueprint $table) {
            $columns = array_filter(
                ['latitude', 'longitude', 'accuracy', 'distance_meters', 'geofence_verified', 'low_confidence'],
                fn (string $col) => Schema::hasColumn('hr_attendances', $col)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('hr_employee_offboardings', function (Blueprint $table) {
            $columns = array_filter(
                [
                    'housing_allowance_amount',
                    'repatriation_ticket_amount',
                    'statutory_compensation_amount',
                    'use_manual_salary',
                    'calculated_base_salary',
                    'calculated_allowances_amount',
                    'is_article_87',
                ],
                fn (string $col) => Schema::hasColumn('hr_employee_offboardings', $col)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('hr_allowances', function (Blueprint $table) {
            if (Schema::hasColumn('hr_allowances', 'is_housing')) {
                $table->dropColumn('is_housing');
            }
        });

        Schema::table('hr_employees', function (Blueprint $table) {
            if (Schema::hasColumn('hr_employees', 'housing_allowance')) {
                $table->dropColumn('housing_allowance');
            }
        });
    }
};
