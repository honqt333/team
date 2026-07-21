<?php

declare(strict_types=1);

namespace App\Services\Developer\Scanners;

use App\Services\Developer\Contracts\ScannerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Database Health & Schema Inspector';
    }

    public function getCategory(): string
    {
        return 'database';
    }

    public function run(array $context = []): array
    {
        $findings = [];
        $connectionType = DB::connection()->getDriverName();

        // 1. Audit tables from DB schema
        $tables = $this->getTablesList();

        foreach ($tables as $table) {
            // Check naming standard: Must be plural and snake_case
            if (preg_match('/[A-Z]/', $table)) {
                $findings[] = [
                    'severity' => 'medium',
                    'file_path' => "database/schema/{$table}",
                    'line_number' => null,
                    'violation_code' => 'database_naming_violation_pascal',
                    'description' => "Table [{$table}] violates naming conventions. Table names must use snake_case and be plural.",
                ];
            }

            // Fetch columns for the table
            try {
                $columns = Schema::getColumnListing($table);

                // 2. Check soft deletes columns presence on critical business tables
                $criticalTables = ['customers', 'vehicles', 'work_orders', 'invoices', 'parts', 'suppliers', 'hr_employees'];

                if (in_array($table, $criticalTables)) {
                    if (! in_array('deleted_at', $columns)) {
                        $findings[] = [
                            'severity' => 'high',
                            'file_path' => "database/schema/{$table}",
                            'line_number' => null,
                            'violation_code' => 'missing_soft_deletes',
                            'description' => "Table [{$table}] represents a core business entity but is missing the 'deleted_at' soft deletes column.",
                        ];
                    }
                }

                // 3. Find relationship columns (_id) lacking indexes (when MySQL)
                if ($connectionType === 'mysql') {
                    foreach ($columns as $column) {
                        if (str_ends_with($column, '_id') && $column !== 'id') {
                            $indexExists = $this->checkIndexExists($table, $column);

                            if (! $indexExists) {
                                $findings[] = [
                                    'severity' => 'high',
                                    'file_path' => "database/schema/{$table}",
                                    'line_number' => null,
                                    'violation_code' => 'missing_foreign_index',
                                    'description' => "Relationship column [{$column}] in table [{$table}] is missing a database index. Query slowdown risk!",
                                ];
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                // Fail silently for virtual tables
            }
        }

        return $findings;
    }

    /**
     * Helper to list all database tables.
     */
    private function getTablesList(): array
    {
        $driver = DB::connection()->getDriverName();
        $tables = [];

        if ($driver === 'sqlite') {
            $results = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");

            foreach ($results as $row) {
                $tables[] = $row->name;
            }
        } else {
            // MySQL, PostgreSQL, SQL Server fallback
            $results = DB::select('SHOW TABLES');

            foreach ($results as $row) {
                $vars = get_object_vars($row);
                $tables[] = reset($vars);
            }
        }

        return $tables;
    }

    /**
     * Check if index exists on a column in MySQL.
     */
    private function checkIndexExists(string $table, string $column): bool
    {
        try {
            $databaseName = DB::connection()->getDatabaseName();
            $results = DB::select('
                SELECT INDEX_NAME 
                FROM information_schema.statistics 
                WHERE table_schema = ? 
                AND table_name = ? 
                AND column_name = ?
            ', [$databaseName, $table, $column]);

            return count($results) > 0;
        } catch (Throwable $e) {
            return true; // Fallback to safe state if queries fail
        }
    }
}
