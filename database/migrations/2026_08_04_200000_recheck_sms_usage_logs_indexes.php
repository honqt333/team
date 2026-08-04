<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 13 follow-up — re-create the
 * `sms_usage_logs.provider_message_id` index AND
 * the `sms_usage_logs.(tenant_id, created_at)`
 * compound index if either is missing.
 *
 * Why a second migration
 * ----------------------
 * Migration `2026_08_04_194500_restore_sms_usage_
 * logs_provider_message_id_index.php` already
 * restores the single-column index, and migration
 * `2026_08_04_190000_add_reserved_to_sms_usage_
 * logs_status.php` rebuilds the table on SQLite
 * (with both indexes re-created in its `up()`).
 *
 * The user review caught a class of edge cases the
 * two prior migrations do not cover:
 *
 *   1. An environment where migration 194500 ran
 *      but the row that drives the rebuild was
 *      applied AFTER, so the index it created
 *      pointed at a table that was later rebuilt
 *      and lost the index anyway.
 *
 *   2. An environment where a third party (a
 *      one-off DB cleanup script, a developer
 *      running a manual `DROP INDEX`) removed the
 *      index after the fact.
 *
 *   3. A SQLite-in-memory test environment that
 *      does not run the migrations through the
 *      framework's `RefreshDatabase` and therefore
 *      queries a connection without schema.
 *
 * This follow-up is intentionally a separate
 * migration so we do NOT edit the executed ones
 * (the user spec: "ولا تعدّل الترقية المنفذة").
 * It is fully idempotent on every supported
 * driver.
 *
 * Driver strategy
 * ---------------
 * We use Laravel's portable `Schema::hasIndex()`
 * for the single-column index (it accepts the
 * index name; we know it).
 *
 * For the compound `(tenant_id, created_at)`
 * index we walk `Schema::getIndexes($table)`,
 * which returns a `list<array>` of
 * `{name, columns, type, unique, primary}` on
 * every supported driver (MySQL, PostgreSQL,
 * SQLite). We compare the column list
 * order-sensitively. This is the API documented
 * for Laravel 12+; the older
 * `getDoctrineSchemaManager()->listTableIndexes()`
 * path is NOT exposed in this project's Laravel
 * version, so we do not use it.
 */
return new class extends Migration
{
    /** @var string */
    private const PROVIDER_MESSAGE_ID_INDEX = 'sms_usage_logs_provider_message_id_index';

    /** @var string */
    private const TENANT_CREATED_AT_INDEX = 'sms_usage_logs_tenant_id_created_at_index';

    public function up(): void
    {
        $this->ensureProviderMessageIdIndex();
        $this->ensureTenantCreatedAtIndex();
    }

    public function down(): void
    {
        // Idempotent. We do NOT drop on down() —
        // dropping an index that the framework
        // also expects to exist on the next
        // `migrate` is the wrong default. The
        // migration is purely additive.
    }

    private function ensureProviderMessageIdIndex(): void
    {
        // The canonical Laravel index name for
        // `$table->index('provider_message_id')`
        // on `sms_usage_logs` is
        // `sms_usage_logs_provider_message_id_index`.
        // Schema::hasIndex() matches by name and
        // works on every supported driver in
        // Laravel 12.
        if (Schema::hasIndex('sms_usage_logs', self::PROVIDER_MESSAGE_ID_INDEX)) {
            return;
        }

        Schema::table('sms_usage_logs', function ($table) {
            $table->index('provider_message_id', self::PROVIDER_MESSAGE_ID_INDEX);
        });
    }

    private function ensureTenantCreatedAtIndex(): void
    {
        // hasIndex() matches by name, not by
        // column list. We need the compound
        // check, so we walk getIndexes() — which
        // is the Laravel 12 portable API for
        // "list all indexes with their column
        // lists" and replaces the old
        // Doctrine `listTableIndexes()` path.
        $expected = ['tenant_id', 'created_at'];

        foreach ($this->indexesOf('sms_usage_logs') as $index) {
            $cols = $this->normaliseColumns($index['columns'] ?? []);

            if ($cols === array_map('strtolower', $expected)) {
                return;
            }
        }

        Schema::table('sms_usage_logs', function ($table) {
            $table->index(['tenant_id', 'created_at'], self::TENANT_CREATED_AT_INDEX);
        });
    }

    /**
     * Portable wrapper around
     * `Schema::getIndexes($table)`. The Schema
     * builder is available on every connection
     * and returns the same shape across MySQL,
     * PostgreSQL, and SQLite.
     *
     * @return list<array{name: string, columns: list<string>, type: string, unique: bool, primary: bool}>
     */
    private function indexesOf(string $table): array
    {
        return Schema::getConnection()
            ->getSchemaBuilder()
            ->getIndexes($table);
    }

    /**
     * Lowercase + trimmed column list. Laravel's
     * `getIndexes()` returns the columns in the
     * table's declared case, which differs
     * between drivers (MySQL keeps the
     * declaration case, SQLite normalises to
     * lower case depending on the
     * connection setting). For comparison
     * purposes we lowercase everything.
     *
     * @param list<string> $columns
     * @return list<string>
     */
    private function normaliseColumns(array $columns): array
    {
        return array_values(array_map(
            fn ($c) => strtolower(trim((string) $c, " \t\n\r\0\x0B\"'")),
            $columns
        ));
    }
};
