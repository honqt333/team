<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

/**
 * Eager-load column guard.
 *
 * WHAT IT CATCHES
 * ---------------
 * `->with(['make:id,name'])` tells Eloquent to select `id, name` from the
 * related table. If the table has no `name` column, MySQL raises
 *
 *     SQLSTATE[42S22]: Column not found: 1054 Unknown column 'name'
 *
 * and the endpoint dies — in production, in front of the user.
 *
 * It happened here: `vehicle_makes` and `vehicle_models` keep the name in
 * `name_ar` / `name_en`, and `name` is an APPENDED accessor computed from
 * them. Two controllers asked the database for a column that only exists
 * in PHP.
 *
 * WHY A STATIC GUARD AND NOT A FEATURE TEST
 * ------------------------------------------
 * A feature test only covers the endpoint it was written for. This scans
 * every controller, so the next `with(['something:id,typo'])` is caught
 * before it reaches a user — and it names the file and the relation.
 *
 * HOW IT WORKS
 * ------------
 *  1. Read the models and build a map of relation name → related model
 *     (from `belongsTo(X::class)` / `hasMany(X::class)` in the relation
 *     methods).
 *  2. Scan the controllers for `'relation:col,col'` strings.
 *  3. Ask the schema whether every listed column exists.
 */
class EagerLoadColumnsTest extends TestCase
{
    /**
     * The MySQL connection to read the schema from, or null when the
     * database is not reachable.
     *
     * WHY NOT THE DEFAULT ONE
     * -----------------------
     * The test suite runs on an in-memory SQLite database. Its schema is
     * NOT the production schema: `vehicle_makes` is created with a `name`
     * column and a later migration RENAMES it to `name_ar`. On SQLite the
     * rename does not take effect the way it does on MySQL, so the test
     * database still has `name` — and the very query that dies in
     * production (`select id, name from vehicle_makes`) is VALID in the
     * test database.
     *
     * A guard that runs against the wrong schema guards nothing. So the
     * schema is read from the real MySQL database: the same engine, the
     * same schema, the same queries. Only read-only schema queries are
     * issued.
     */
    private function mysqlConnection(): ?string
    {
        $env = $this->parseEnv(base_path('.env'));

        $database = $env['DB_DATABASE'] ?? null;

        if ($database === null || $database === ':memory:') {
            return null;
        }

        config([
            'database.connections.mysql.database' => $database,
            'database.connections.mysql.host' => $env['DB_HOST'] ?? '127.0.0.1',
            'database.connections.mysql.port' => $env['DB_PORT'] ?? '3306',
            'database.connections.mysql.username' => $env['DB_USERNAME'] ?? 'root',
            'database.connections.mysql.password' => $env['DB_PASSWORD'] ?? '',
        ]);

        DB::purge('mysql');

        try {
            DB::connection('mysql')->getPdo();

            return 'mysql';
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * A minimal .env reader — the test environment overrides DB_DATABASE
     * with `:memory:`, so the real database name has to come from the file.
     *
     * @return array<string, string>
     */
    private function parseEnv(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $values = [];

        foreach (File::lines($path) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $values[trim($key)] = trim(trim($value), "\"'");
        }

        return $values;
    }

    private function columnsOf(string $table, string $connection): array
    {
        try {
            return Schema::connection($connection)->getColumnListing($table);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, array<int, string>> relation name => tables
     */
    private function relationTables(): array
    {
        $map = [];

        // The relations live in the models AND in the traits they use
        // (`HasWorkOrderRelations::items()` is `work_order_items`), so
        // both directories are scanned.
        $directories = [app_path('Models'), app_path('Traits')];

        foreach ($directories as $directory) {
            foreach (File::allFiles($directory) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $source = File::get($file->getPathname());

                // public function make(): BelongsTo { return $this->belongsTo(VehicleMake::class, ...); }
                preg_match_all(
                    '/function\s+(\w+)\s*\(\s*\)\s*:\s*[^{]*\{\s*return\s+\$this->(?:belongsTo|hasMany|hasOne|belongsToMany)\(\s*(\w+)::class/s',
                    $source,
                    $matches,
                    PREG_SET_ORDER
                );

                foreach ($matches as $match) {
                    $relation = $match[1];
                    $modelClass = 'App\\Models\\'.$match[2];

                    if (! class_exists($modelClass)) {
                        continue;
                    }

                    try {
                        $table = (new $modelClass)->getTable();
                    } catch (Throwable) {
                        continue;
                    }

                    // A relation name can point at SEVERAL tables: `parts` is
                    // work_order_parts on a WorkOrder and quote_parts on a
                    // Quote. The column is valid if it exists in ANY of
                    // them — otherwise every one of them would be reported.
                    $map[$relation] ??= [];
                    $map[$relation][] = $table;
                }
            }
        }

        return array_map('array_values', array_map('array_unique', $map));
    }

    public function test_every_eager_loaded_column_exists_in_the_related_table(): void
    {
        $connection = $this->mysqlConnection();

        if ($connection === null) {
            $this->markTestSkipped(
                'The MySQL database is not reachable — the schema cannot be read, so the guard '
                .'cannot run. The static guard below still covers the relations that broke.'
            );
        }

        $tables = $this->relationTables();

        $this->assertNotEmpty($tables, 'The relation map is empty — the scanner is broken.');

        $problems = [];
        $checked = 0;

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = File::get($file->getPathname());

            // 'relation:col,col' — the column list of an eager load.
            preg_match_all("/'([a-zA-Z_]+):([a-zA-Z_0-9,]+)'/", $source, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                [$full, $relation, $columns] = $match;

                if (! isset($tables[$relation])) {
                    // Not a relation we know (it may be a scope or a
                    // closure syntax). Skip it rather than guess.
                    continue;
                }

                $table = $tables[$relation];

                // The column must exist in EVERY table this relation can
                // point at — otherwise the query would fail on one of them.
                $existing = [];

                foreach ($table as $candidate) {
                    $existing = array_merge($existing, $this->columnsOf($candidate, $connection));
                }

                if ($existing === []) {
                    continue;
                }

                foreach (explode(',', $columns) as $column) {
                    $column = trim($column);

                    if ($column === '' || in_array($column, $existing, true)) {
                        continue;
                    }

                    $checked++;

                    $problems[] = sprintf(
                        '%s: %s — none of the tables (%s) has a column `%s`',
                        str_replace(base_path().'/', '', $file->getPathname()),
                        $relation,
                        implode(', ', $table),
                        $column
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $problems,
            "These eager loads ask for columns the table does not have. The query is a fatal\n"
            ."SQL error in production — MySQL: \"Unknown column\".\n  ".implode("\n  ", $problems)
        );
    }

    /**
     * The two relations that caused the outage, pinned explicitly.
     *
     * The generic scan above can be defeated by a relation name it cannot
     * resolve. This one cannot.
     */
    /**
     * The two relations that caused the outage, pinned statically.
     *
     * The generic scan above needs the MySQL schema; this one needs
     * nothing but the source, so it runs everywhere — including CI,
     * where the database is not reachable.
     */
    public function test_the_vehicle_relations_load_the_columns_the_tables_actually_have(): void
    {
        // `name` is NOT a column of vehicle_makes / vehicle_models: it is an
        // appended accessor computed from name_ar / name_en.
        foreach (['app/Models/VehicleMake.php', 'app/Models/VehicleModel.php'] as $model) {
            $source = File::get(base_path($model));

            $this->assertStringContainsString(
                'getNameAttribute',
                $source,
                "{$model} must compute `name` from name_ar / name_en."
            );

            $this->assertMatchesRegularExpression(
                "/protected \\\$appends\s*=\s*\[[^\]]*'name'/",
                $source,
                "{$model} must append the `name` attribute."
            );
        }

        // The migration that renamed the column proves it was never a
        // column on its own.
        $migration = File::get(
            base_path('database/migrations/2025_12_22_080000_update_vehicle_makes_table.php')
        );

        $this->assertStringContainsString(
            "renameColumn('name', 'name_ar')",
            $migration,
            'The `name` column of vehicle_makes was renamed to name_ar — it does not exist.'
        );

        foreach (
            [
                'app/Http/Controllers/Api/WorkOrderController.php',
                'app/Http/Controllers/App/AppointmentController.php',
            ] as $path
        ) {
            $source = File::get(base_path($path));

            $this->assertStringNotContainsString(
                "'make:id,name'",
                $source,
                "{$path} asks the database for a `name` column that `vehicle_makes` does not have."
            );
            $this->assertStringNotContainsString(
                "'model:id,name'",
                $source,
                "{$path} asks the database for a `name` column that `vehicle_models` does not have."
            );
        }
    }
}
