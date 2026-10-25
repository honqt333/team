<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an `admin_id` column to `internal_notifications` so system
     * admins (managed in admin_users, not users) can also receive
     * notifications. The `user_id` and `tenant_id` columns stay
     * required for tenant-side notifications; admin notifications
     * leave them NULL and identify the recipient via `admin_id`.
     *
     * We use a polymorphic-like "one of these is set" pattern at the
     * application layer:
     *   - Tenant user notification: user_id set, tenant_id set, admin_id NULL
     *   - Admin user notification:    user_id NULL, tenant_id NULL, admin_id set
     *
     * The unique-id UUID on this table makes a polymorphic relation
     * overkill for two recipient types. A `recipient_type` check is
     * easier to query in the listener layer.
     */
    public function up(): void
    {
        // 1. Drop the original tenant_id + user_id FK constraints. We
        //    do this first so the columns can be made nullable without
        //    the FK complaining about NULLs.
        try {
            Schema::table('internal_notifications', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('internal_notifications', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (Throwable $e) {
        }

        // 2. Make tenant_id + user_id nullable.
        //    SQLite (used in-memory for tests) doesn't support
        //    `->change()` for column mods — use raw SQL for both
        //    backends.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: rebuild the table to change column nullability.
            // internal_notifications is small (a few rows per admin/
            // tenant at most), so this is cheap. The SELECT list
            // excludes admin_id because the temp table predates the
            // new column.
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('CREATE TABLE _internal_notifications_tmp AS SELECT * FROM internal_notifications');
            Schema::drop('internal_notifications');
            Schema::create('internal_notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('actor_id')->nullable();
                // admin_id added via ALTER below.
                $table->string('type');
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('icon')->nullable();
                $table->string('action_url')->nullable();
                $table->json('data')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'read_at']);
                $table->index(['tenant_id', 'user_id', 'created_at']);
            });
            DB::statement(
                'INSERT INTO internal_notifications (id, tenant_id, user_id, actor_id, type, title, body, icon, action_url, data, read_at, created_at, updated_at) SELECT id, tenant_id, user_id, actor_id, type, title, body, icon, action_url, data, read_at, created_at, updated_at FROM _internal_notifications_tmp'
            );
            DB::statement('DROP TABLE _internal_notifications_tmp');
            DB::statement('PRAGMA foreign_keys=ON');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE internal_notifications MODIFY COLUMN tenant_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE internal_notifications MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
        } else {
            DB::statement('ALTER TABLE internal_notifications ALTER COLUMN tenant_id DROP NOT NULL');
            DB::statement('ALTER TABLE internal_notifications ALTER COLUMN user_id DROP NOT NULL');
        }

        // 3. Re-add the tenant_id + user_id FKs (now nullable) + the
        //    new admin_id FK. The user_id FK becomes optional to allow
        //    admin notifications to leave it NULL.
        Schema::table('internal_notifications', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->after('actor_id')
                ->constrained('admin_users')->nullOnDelete();
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('internal_notifications', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['admin_id']);
            $table->dropColumn('admin_id');
        });
        // Re-tighten tenant_id NOT NULL is a destructive change
        // (NULL rows would break). We don't restore it in down() —
        // a subsequent migration could re-tighten if needed.
    }
};
