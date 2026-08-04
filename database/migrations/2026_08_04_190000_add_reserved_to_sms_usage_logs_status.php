<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 13 — allow the new `reserved` status on
 * sms_usage_logs.
 *
 * Why
 * ---
 * The Authentica customer-portal OTP channel now
 * reserves an SMS credit atomically BEFORE calling
 * the provider. The reservation is persisted as a
 * SmsUsageLog row with status='reserved' inside the
 * same transaction as the balance decrement, so the
 * deduction and the audit are atomic. The success
 * path promotes the row to 'sent'; the failure path
 * promotes it to 'failed' and refunds the credit.
 *
 * The original Phase 12 migration declared the
 * status column as an enum with the values
 * ('sent', 'delivered', 'failed', 'pending').
 * SQLite stores enums as TEXT with a CHECK
 * constraint (not a real enum), so we have to
 * update the constraint to accept 'reserved'.
 *
 * The migration is safe to run on MySQL too:
 *   - SQLite: drop and recreate the table's
 *     check constraint
 *   - MySQL: ALTER TABLE … MODIFY COLUMN to the
 *     new enum value list
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite stores enum() as TEXT with a
            // CHECK constraint. We rebuild the
            // table to swap the constraint.
            //
            // 1. Read the existing rows.
            $rows = DB::table('sms_usage_logs')->get();

            // 2. Drop the old table.
            Schema::drop('sms_usage_logs');

            // 3. Recreate with the new constraint.
            Schema::create('sms_usage_logs', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
                $table->string('phone_number');
                $table->string('message_type');
                $table->integer('credits_used')->default(1);
                $table->string('provider')->nullable();
                $table->string('provider_message_id')->nullable();
                $table->string('status')->default('pending');
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
            });

            // 4. Restore rows (the status stays the
            //    same; the new column is more
            //    permissive).
            foreach ($rows as $row) {
                DB::table('sms_usage_logs')->insert((array) $row);
            }

            return;
        }

        // MySQL / MariaDB: re-declare the column
        // with the new value list. The existing
        // rows are converted implicitly; any value
        // not in the new list would block the
        // ALTER, but every existing value is in
        // the new list so this is safe.
        DB::statement(
            "ALTER TABLE sms_usage_logs
             MODIFY COLUMN status ENUM('sent','delivered','failed','pending','reserved')
             NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Rolling back requires collapsing any
            // 'reserved' rows. We mark them as
            // 'pending' (the closest neutral state
            // — the operator can grep the log for
            // the audit trail).
            DB::table('sms_usage_logs')
                ->where('status', 'reserved')
                ->update(['status' => 'pending']);

            $rows = DB::table('sms_usage_logs')->get();

            Schema::drop('sms_usage_logs');

            Schema::create('sms_usage_logs', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
                $table->string('phone_number');
                $table->string('message_type');
                $table->integer('credits_used')->default(1);
                $table->string('provider')->nullable();
                $table->string('provider_message_id')->nullable();
                $table->string('status')->default('pending');
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'created_at']);
            });

            foreach ($rows as $row) {
                DB::table('sms_usage_logs')->insert((array) $row);
            }

            return;
        }

        DB::table('sms_usage_logs')
            ->where('status', 'reserved')
            ->update(['status' => 'pending']);

        DB::statement(
            "ALTER TABLE sms_usage_logs
             MODIFY COLUMN status ENUM('sent','delivered','failed','pending')
             NOT NULL DEFAULT 'pending'"
        );
    }
};
