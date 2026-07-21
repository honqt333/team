<?php

declare(strict_types=1);

namespace App\Services\Developer\Scanners;

use App\Models\Developer\SlowQueryLog;
use App\Services\Developer\Contracts\ScannerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PerformanceScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Performance & Profiler Scanner';
    }

    public function getCategory(): string
    {
        return 'performance';
    }

    public function run(array $context = []): array
    {
        $findings = [];

        // 1. Audit slow query logs from database
        try {
            $slowQueries = SlowQueryLog::where('execution_time_ms', '>', 100.0)
                ->orderBy('execution_time_ms', 'desc')
                ->limit(5)
                ->get();

            foreach ($slowQueries as $query) {
                $findings[] = [
                    'severity' => $query->execution_time_ms > 500.0 ? 'high' : 'medium',
                    'file_path' => $query->caller,
                    'line_number' => $query->caller_line,
                    'violation_code' => 'slow_database_query',
                    'description' => "Slow database query detected: execution time [{$query->execution_time_ms}ms]. Query: [{$query->sql_statement}]",
                ];
            }

            // 2. Audit N+1 query logs
            $nPlusOneQueries = SlowQueryLog::where('type', 'n_plus_one')
                ->orderBy('occurrences', 'desc')
                ->limit(5)
                ->get();

            foreach ($nPlusOneQueries as $query) {
                $findings[] = [
                    'severity' => 'high',
                    'file_path' => $query->caller,
                    'line_number' => $query->caller_line,
                    'violation_code' => 'n_plus_one_queries',
                    'description' => "Potential N+1 Query Loop detected: executed [{$query->occurrences}] times. Ensure Eager Loading is used.",
                ];
            }
        } catch (Throwable $e) {
            // Log table might be empty or missing during initial migrations runs
        }

        // 3. Audit Failed Jobs Queue
        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedJobsCount = DB::table('failed_jobs')->count();

                if ($failedJobsCount > 0) {
                    $findings[] = [
                        'severity' => 'high',
                        'file_path' => 'database/failed_jobs',
                        'line_number' => null,
                        'violation_code' => 'failed_jobs_in_queue',
                        'description' => "There are [{$failedJobsCount}] failed jobs present in the queue database log. Action needed to inspect queue handlers.",
                    ];
                }
            }
        } catch (Throwable $e) {
            // Ignore if failed_jobs doesn't exist
        }

        return $findings;
    }
}
