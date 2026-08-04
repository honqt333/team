<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 13 — schema regression tests for
 * `sms_usage_logs`.
 *
 * Why this file exists
 * --------------------
 * The Phase 13 Authentica billing fix shipped
 * migration `2026_08_04_190000` that adds
 * `reserved` to the `status` enum. On SQLite, that
 * migration rebuilds the table (SQLite stores
 * ENUM() as a CHECK constraint, so the only
 * portable way to add a value is to recreate the
 * table). The rebuild dropped the
 * `provider_message_id` index that an earlier
 * migration (`2026_07_08_190000`) had created.
 *
 * We caught it in review and shipped migration
 * `2026_08_04_194500` to restore the index, plus
 * a defensive recheck migration
 * `2026_08_04_200000` that handles partial legacy
 * states. This test pins the final shape so the
 * next person who edits a SQLite-table-rebuild
 * migration knows exactly which indexes they
 * must re-create.
 *
 * The tests are written against the connection
 * that PHPUnit is currently using (SQLite in
 * memory in CI, MySQL on production). Both must
 * pass.
 *
 * Driver strategy
 * ---------------
 * We use Laravel's portable `Schema::hasIndex()`
 * for the single-column index (it accepts the
 * index name) and `Schema::getIndexes()` to walk
 * the column list for the compound check. These
 * two helpers are the only schema-introspection
 * APIs that work consistently across MySQL,
 * PostgreSQL, and SQLite in Laravel 12. The
 * `Schema::getConnection()->getDoctrineColumn()`
 * and `getDoctrineSchemaManager()` paths are NOT
 * exposed in this project's Laravel version —
 * we deliberately avoid them so the test is not
 * gated to a Doctrine-aware connection.
 *
 * RefreshDatabase is REQUIRED for this test
 * class. Without it the SQLite in-memory
 * connection has no schema, and every assertion
 * reports "no such table".
 */
class SmsUsageLogsSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The two index names that the migrations
     * create. Pinning them here (and in the
     * migrations) means the only failure mode
     * for the assertions below is "the index
     * is missing", which is exactly what the
     * regression is about.
     */
    private const PROVIDER_MESSAGE_ID_INDEX = 'sms_usage_logs_provider_message_id_index';

    private const TENANT_CREATED_AT_INDEX = 'sms_usage_logs_tenant_id_created_at_index';

    public function test_provider_message_id_index_exists(): void
    {
        $this->assertTrue(
            Schema::hasIndex('sms_usage_logs', self::PROVIDER_MESSAGE_ID_INDEX),
            'sms_usage_logs.'.self::PROVIDER_MESSAGE_ID_INDEX.' must exist — the original '
            .'index was dropped as a side effect of migration 2026_08_04_190000 and restored '
            .'by 2026_08_04_194500. The defensive migration 2026_08_04_200000 re-creates it '
            .'if it is missing. If this assertion fails, one of those three migrations is '
            .'broken on the current driver.'
        );
    }

    public function test_tenant_id_and_created_at_compound_index_exists(): void
    {
        $this->assertTrue(
            $this->compoundIndexExists('sms_usage_logs', ['tenant_id', 'created_at']),
            'sms_usage_logs must keep its (tenant_id, created_at) compound index for '
            .'the per-tenant billing report queries in SmsCreditsController. The original '
            .'2026_01_05_123830 migration created it; the Phase 13 rebuild and the '
            .'defensive migration both re-create it.'
        );
    }

    public function test_status_column_accepts_reserved_value(): void
    {
        // Phase 13 — the Authentica customer-portal
        // OTP channel writes a `reserved` row inside
        // the reservation transaction. The column
        // must accept it on every supported driver.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite validates via a CHECK
            // constraint. The insert below MUST
            // succeed; if the migration that adds
            // `reserved` is broken, the insert will
            // throw QueryException.
            //
            // We need a real tenant row because
            // RefreshDatabase keeps the foreign
            // key enforcement on. A bare insert
            // with tenant_id=1 would fail with
            // "FOREIGN KEY constraint failed".
            $tenant = Tenant::factory()->create();

            DB::table('sms_usage_logs')->insert([
                'tenant_id' => $tenant->id,
                'phone_number' => '+966500000000',
                'message_type' => 'otp',
                'credits_used' => 1,
                'provider' => 'authentica',
                'status' => 'reserved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertDatabaseHas('sms_usage_logs', [
                'provider' => 'authentica',
                'status' => 'reserved',
            ]);

            return;
        }

        // MySQL / PostgreSQL: ENUM() encodes the
        // allowed values in the column type. We
        // do NOT use Doctrine's getDoctrineColumn
        // (not exposed in this Laravel version) —
        // we look up the column definition via
        // the portable Schema::getColumns() and
        // assert the `reserved` value is listed.
        //
        // The exact textual format of the ENUM
        // declaration differs between MySQL and
        // PostgreSQL (e.g. "enum('a','b')" vs
        // "varchar(255) CHECK (col IN ('a','b'))"),
        // so we look for the literal string
        // 'reserved' inside the column-type
        // string with optional surrounding quotes.
        $columns = Schema::getConnection()
            ->getSchemaBuilder()
            ->getColumns('sms_usage_logs');

        $statusColumn = null;

        foreach ($columns as $col) {
            if (strtolower((string) ($col['name'] ?? '')) === 'status') {
                $statusColumn = $col;
                break;
            }
        }

        $this->assertNotNull(
            $statusColumn,
            'Schema::getColumns("sms_usage_logs") must include a `status` column'
        );

        $columnType = strtolower((string) ($statusColumn['type'] ?? ''));
        $this->assertNotEmpty(
            $columnType,
            'the status column `type` field must not be empty'
        );

        $this->assertMatchesRegularExpression(
            "/['\"]?reserved['\"]?/",
            $columnType,
            'status column must accept "reserved" as a value (Phase 13 Authentica '
            .'reservation lifecycle). Column type returned: '.$columnType
        );
    }

    /**
     * Does an index exist on the table that
     * covers the given columns in the given
     * order? Strict order-sensitive —
     * `(tenant_id, created_at)` and
     * `(created_at, tenant_id)` are different
     * indexes for query-planner purposes.
     *
     * We use the portable `Schema::getIndexes()`
     * API (Laravel 12+) which returns a uniform
     * shape across every supported driver:
     *   [
     *     ['name' => '...', 'columns' => [...],
     *      'type' => '...', 'unique' => bool,
     *      'primary' => bool],
     *     ...
     *   ]
     *
     * @param list<string> $columns
     */
    private function compoundIndexExists(string $table, array $columns): bool
    {
        $expected = array_map('strtolower', $columns);

        $indexes = Schema::getConnection()
            ->getSchemaBuilder()
            ->getIndexes($table);

        foreach ($indexes as $index) {
            $actual = array_map(
                fn ($c) => strtolower(trim((string) $c, " \t\n\r\0\x0B\"'")),
                $index['columns'] ?? []
            );

            if ($actual === $expected) {
                return true;
            }
        }

        return false;
    }
}
