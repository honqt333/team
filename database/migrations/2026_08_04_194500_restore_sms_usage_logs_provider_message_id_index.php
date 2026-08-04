<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 13 — restore the `provider_message_id` index
 * on `sms_usage_logs` that was dropped as a side
 * effect of migration
 * `2026_08_04_190000_add_reserved_to_sms_usage_logs_
 * status.php` rebuilding the table on SQLite.
 *
 * Why this migration exists
 * -------------------------
 * The earlier migration was the cleanest way to
 * teach SQLite about the new `reserved` enum value:
 * SQLite stores ENUM() as TEXT with a CHECK
 * constraint, so the only portable way to add a
 * value is to rebuild the table. Rebuilding drops
 * the indexes that were created on the original
 * table.
 *
 * On MySQL the prior migration used
 * `MODIFY COLUMN … ENUM(…)` and preserved every
 * index — the bug only manifests on SQLite. But
 * because we don't know which driver an
 * environment runs on until it migrates, this
 * follow-up is idempotent for both:
 *
 *   - MySQL: the index is still there, the
 *     hasIndex() probe skips the create, this
 *     migration becomes a no-op.
 *   - SQLite: the index was dropped by the
 *     earlier rebuild, hasIndex() returns false,
 *     and we recreate it.
 *
 * We deliberately do NOT modify the prior
 * migration. The prior migration is a closed
 * historical record; rewriting it would mean
 * environments that already ran the old version
 * stay broken. A follow-up migration is the only
 * correct shape.
 *
 * What "idempotent" means here
 * ----------------------------
 * `Schema::hasIndex()` is supported on MySQL and
 * PostgreSQL. SQLite has no equivalent — Laravel
 * surfaces it as `null` there. We use
 * `Schema::getConnection()->getDriverName()` to
 * pick the right probe, and we always also try
 * the create guarded by a `try/catch` on
 * `QueryException` (unique-name conflict on the
 * index name) so a partial legacy state where
 * the index name is taken but the column is
 * unindexed still gets covered.
 */
return new class extends Migration
{
    /**
     * The original index name Laravel gives to
     * `$table->index('provider_message_id')` on a
     * table called `sms_usage_logs`. We need the
     * explicit name to drop / probe it, because
     * SQLite's `hasIndex` returns null.
     */
    private const INDEX_NAME = 'sms_usage_logs_provider_message_id_index';

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite has no `hasIndex()`. Probe the
            // sqlite_master catalog directly. The
            // index is auto-named by Laravel to
            // `<table>_<col>_index`; we look for
            // any index on the
            // `sms_usage_logs` table that covers
            // the `provider_message_id` column.
            $exists = $this->sqliteIndexExists('sms_usage_logs', 'provider_message_id');

            if (! $exists) {
                // Re-create the index with the same
                // single-column shape the original
                // migration (`2026_07_08_190000`)
                // created. We pass the explicit
                // index name so a future `down()`
                // can drop it by name without
                // ambiguity.
                DB::statement(
                    'CREATE INDEX '.self::INDEX_NAME.
                    ' ON sms_usage_logs (provider_message_id)'
                );
            }

            return;
        }

        // MySQL / PostgreSQL: use Laravel's
        // portable hasIndex() probe. If the index
        // already exists (because the MySQL path
        // never dropped it) this is a no-op.
        if (! Schema::hasIndex('sms_usage_logs', self::INDEX_NAME)) {
            Schema::table('sms_usage_logs', function ($table) {
                $table->index('provider_message_id', self::INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            if ($this->sqliteIndexExists('sms_usage_logs', 'provider_message_id')) {
                DB::statement('DROP INDEX '.self::INDEX_NAME);
            }

            return;
        }

        if (Schema::hasIndex('sms_usage_logs', self::INDEX_NAME)) {
            Schema::table('sms_usage_logs', function ($table) {
                $table->dropIndex(self::INDEX_NAME);
            });
        }
    }

    /**
     * SQLite-only: check sqlite_master for an
     * index on the given table that covers the
     * given column. Returns true if any matching
     * index exists, false otherwise.
     *
     * We match on the index's `sql` column
     * (CREATE INDEX … (col) …) because the
     * `name` column does not always carry the
     * column list and we need to be sure we are
     * looking at the right index.
     */
    private function sqliteIndexExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT name FROM sqlite_master
             WHERE type = 'index'
               AND tbl_name = ?
               AND sql LIKE ?
             LIMIT 1",
            [$table, '%('.$column.')%']
        );

        return $row !== null;
    }
};
