<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The `settings` table is a global key/value store (SMTP credentials,
 * platform flags, website copy). The inspection preference was written
 * into it, which meant one tenant's toggle changed the inspection steps
 * for every tenant on the install.
 *
 * A nullable `tenant_id` makes a row tenant-scoped without breaking the
 * global rows: `tenant_id = null` keeps the system-wide behaviour, and a
 * row with a tenant id belongs to that tenant alone.
 *
 * The table's only unique index was on `key` alone, which made a
 * per-tenant row impossible: the same key can now exist once per tenant
 * plus one platform-wide row. It is replaced by a unique index on
 * `(tenant_id, key)`.
 *
 * Note on NULL: MySQL and SQLite treat NULL values as distinct inside a
 * unique index, so the platform-wide row is not enforced unique by the
 * database. Every read and write of a platform-wide value goes through
 * `Setting::get()` / `Setting::set()`, which target `tenant_id IS NULL`
 * explicitly, so a second platform-wide row is never created.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'tenant_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }

        // One key per platform is no longer enough.
        $this->dropIndexIfExists('settings_key_unique');

        // The plain (tenant_id, key) index is redundant next to the
        // unique one below.
        $this->dropIndexIfExists('settings_tenant_id_key_index');

        if (! $this->indexExists('settings_tenant_id_key_unique')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unique(['tenant_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        // The old schema allows exactly one row per key, so the
        // tenant-specific preference rows cannot survive the rollback.
        // The platform-wide row is kept.
        DB::table('settings')->whereNotNull('tenant_id')->delete();

        $this->dropIndexIfExists('settings_tenant_id_key_unique');
        $this->dropIndexIfExists('settings_tenant_id_key_index');
        $this->dropIndexIfExists('settings_tenant_id_index');

        if (! $this->indexExists('settings_key_unique')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->unique('key');
            });
        }

        if (Schema::hasColumn('settings', 'tenant_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }

    private function indexExists(string $index): bool
    {
        foreach (Schema::getIndexes('settings') as $existing) {
            if (($existing['name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }

    private function dropIndexIfExists(string $index): void
    {
        if (! $this->indexExists($index)) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) use ($index) {
            $table->dropIndex($index);
        });
    }
};
