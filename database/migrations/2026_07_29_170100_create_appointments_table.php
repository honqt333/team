<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('service_bay_id')->nullable()->constrained('service_bays')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('scheduled_at');
            $table->timestamp('estimated_end_at');

            // scheduled | confirmed | checked_in | no_show | cancelled | converted
            $table->string('status', 20);

            $table->text('notes')->nullable();
            $table->string('source', 32)->default('internal');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Idempotency: at most one work order per appointment.
            // nullOnDelete so removing a work order doesn't cascade
            // away the appointment history.
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();

            $table->timestamps();

            // Conflict-detection hot paths:
            //   1. "what appointments does this bay have today?"
            //   2. "what appointments does this tech have today?"
            //   3. "what appointments does this vehicle have?"
            //   4. day-bucket view for the calendar.
            $table->index(['center_id', 'status', 'scheduled_at'], 'appt_center_status_time_idx');
            $table->index(['service_bay_id', 'status', 'scheduled_at'], 'appt_bay_status_time_idx');
            $table->index(['assigned_user_id', 'status', 'scheduled_at'], 'appt_user_status_time_idx');
            $table->index(['vehicle_id', 'status', 'scheduled_at'], 'appt_vehicle_status_time_idx');
            // Phase 4 P1: a work order can be linked to at most
            // one appointment. Combined with the FK above, this
            // means `Appointment::work_order_id` is effectively
            // unique across the whole table.
            $table->unique('work_order_id', 'appt_work_order_unique');
        });

        // Pivot: an appointment can have multiple services.
        Schema::create('appointment_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->timestamps();

            $table->unique(
                ['appointment_id', 'service_id'],
                'appt_service_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
        Schema::dropIfExists('appointments');
    }
};
