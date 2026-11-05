<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow `admin_activity_logs.admin_user_id` to be NULL.
 *
 * ## Why
 *
 * The /system panel is reachable through TWO identities:
 *
 *   - an `AdminUser` on the 'admin' guard (the normal case), and
 *   - a tenant `User` flagged `is_system_admin` on the 'web' guard.
 *
 * `admin_activity_logs.admin_user_id` is a NOT NULL foreign key to
 * `admin_users.id`. A tenant-session system admin has NO
 * `admin_users` row, so the audit trail had nowhere to put them:
 *
 *   - writing null violates the NOT NULL constraint,
 *   - writing the tenant user's id points the log at an unrelated
 *     admin who happens to share the id — a false audit record, which
 *     is worse than no record at all.
 *
 * Making the column nullable lets the audit trail record the actor
 * honestly: `admin_user_id` stays set for real AdminUsers, and for a
 * tenant-session admin it is NULL while the actor's identity is kept
 * in the `changes` payload (the SystemAuditService writes an `actor`
 * and `actor_type` entry there).
 *
 * ## Compatibility
 *
 *   - Nullable is strictly more permissive than NOT NULL, so every
 *     existing row and every existing writer keeps working unchanged.
 *   - The FK constraint is left in place, so a non-null id still has
 *     to point at a real `admin_users` row.
 *   - `down()` restores the original NOT NULL constraint. Rows written
 *     with a NULL actor while this migration was live would violate it,
 *     so `down()` deletes exactly those rows first — they cannot be
 *     restored to a valid state, because the actor they name does not
 *     exist in `admin_users`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows recorded by a tenant-session system admin carry a NULL
        // actor id and cannot satisfy the original NOT NULL
        // constraint. They are removed here rather than silently
        // failing the rollback; the `changes.actor` payload was their
        // only record, and there is nowhere to move it to.
        DB::table('admin_activity_logs')
            ->whereNull('admin_user_id')
            ->delete();

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_user_id')->nullable(false)->change();
        });
    }
};
