<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 11 — appointment_activities.
 *
 * The work-order-activities table exists and
 * stores per-tenant, per-work-order audit rows
 * for the staff UI. We mirror that shape for
 * the appointment module so the Phase 11
 * communications dashboard can record "this
 * delivery row was retried by user X at time T"
 * in a structured, queryable way.
 *
 * Why a separate table from spatie/activitylog:
 * the project does not use spatie/laravel-
 * activitylog. The existing work-order pattern
 * is a simple per-resource activity table with
 * the action + description + changes columns.
 * Mirroring that pattern keeps the read
 * queries simple and avoids pulling in a
 * dependency for one new screen.
 *
 * Privacy contract for this table:
 *   - `description` is operator-facing text and
 *     MUST NOT contain the customer phone, the
 *     SMS body, or any provider error body. The
 *     controllers that write here are
 *     responsible for that contract.
 *   - `changes` is a JSON column for structured
 *     metadata (e.g. the row id and message
 *     type that was retried). It must not carry
 *     the phone or the body either.
 *   - `user_id` carries the FK to users so the
 *     audit log can answer "who did this?". We
 *     use `set null` on user delete so the
 *     audit trail survives a staff departure.
 *
 * The cascade on tenant_id means a tenant
 * deletion wipes the audit log with it; the
 * audit log is not retained past the tenant's
 * lifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // e.g. 'message_retried'
            $table->text('description')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'appointment_id', 'created_at']);
            $table->index(['tenant_id', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_activities');
    }
};
