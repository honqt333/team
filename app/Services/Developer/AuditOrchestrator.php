<?php

declare(strict_types=1);

namespace App\Services\Developer;

use App\Models\Developer\AuditSnapshot;
use App\Models\Developer\AuditViolation;
use App\Models\Developer\ComponentStat;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class AuditOrchestrator
{
    /**
     * Run the complete codebase audit suite.
     */
    public function runAudit(?int $createdBy = null): AuditSnapshot
    {
        Log::info('Developer Center: Audit requested, initializing scanners...');

        // Resolve registered scanners
        $scannerClasses = Config::get('developer_center.scanners', []);
        $allViolations = [];
        $uiStats = [];
        $filesCount = 0;

        foreach ($scannerClasses as $scannerClass) {
            try {
                if (class_exists($scannerClass)) {
                    $scanner = new $scannerClass;
                    Log::info('Running scanner: '.$scanner->getName());

                    $findings = $scanner->run();

                    foreach ($findings as $finding) {
                        if (isset($finding['is_stat']) && $finding['is_stat'] === true) {
                            $uiStats[] = $finding;
                        } else {
                            $allViolations[] = array_merge($finding, [
                                'category' => $scanner->getCategory(),
                            ]);
                        }
                    }
                }
            } catch (Throwable $e) {
                Log::error("Developer Center: Scanner [{$scannerClass}] failed: ".$e->getMessage());
            }
        }

        // Count source files dynamically
        $filesCount = $this->countSourceFiles();

        // Group violations by category to calculate scores
        $grouped = collect($allViolations)->groupBy('category');

        $scores = [
            'architecture' => 100.0,
            'security' => 100.0,
            'performance' => 100.0,
            'testing' => 100.0,
            'ui' => 100.0,
            'documentation' => 100.0,
        ];

        foreach ($scores as $category => $defaultScore) {
            $categoryViolations = $grouped->get($category, collect());
            $deduction = 0.0;

            foreach ($categoryViolations as $violation) {
                switch ($violation['severity']) {
                    case 'critical':
                        $deduction += 10.0;
                        break;
                    case 'high':
                        $deduction += 4.0;
                        break;
                    case 'medium':
                        $deduction += 1.0;
                        break;
                    case 'low':
                        $deduction += 0.2;
                        break;
                }
            }
            $scores[$category] = max(0.0, 100.0 - $deduction);
        }

        // Compute weighted overall score
        // 25% Security, 25% Testing, 20% Architecture, 15% Performance, 15% UI/Docs
        $scoreOverall = (0.25 * $scores['security']) +
                        (0.25 * $scores['testing']) +
                        (0.20 * $scores['architecture']) +
                        (0.15 * $scores['performance']) +
                        (0.15 * (($scores['ui'] + $scores['documentation']) / 2));

        // Create the snapshot record
        $snapshot = AuditSnapshot::create([
            'score_overall' => $scoreOverall,
            'score_architecture' => $scores['architecture'],
            'score_security' => $scores['security'],
            'score_performance' => $scores['performance'],
            'score_testing' => $scores['testing'],
            'score_ui' => $scores['ui'],
            'score_documentation' => $scores['documentation'],
            'files_analyzed_count' => $filesCount,
            'violations_count' => count($allViolations),
            'created_by' => $createdBy,
        ]);

        // Save violations logs
        foreach ($allViolations as $violation) {
            AuditViolation::create([
                'snapshot_id' => $snapshot->id,
                'category' => $violation['category'],
                'severity' => $violation['severity'],
                'file_path' => $violation['file_path'],
                'line_number' => $violation['line_number'] ?? null,
                'violation_code' => $violation['violation_code'],
                'description' => $violation['description'],
            ]);
        }

        // Save UI stats logs
        foreach ($uiStats as $stat) {
            ComponentStat::create([
                'snapshot_id' => $snapshot->id,
                'component_name' => $stat['component_name'],
                'count_compliant' => $stat['count_compliant'],
                'count_violations' => $stat['count_violations'],
            ]);
        }

        Log::info("Developer Center: Audit complete. Snapshot ID [{$snapshot->id}], Score: [{$scoreOverall}%]");

        return $snapshot;
    }

    /**
     * Counts the total PHP and Vue source files in the project.
     */
    private function countSourceFiles(): int
    {
        $count = 0;
        $directories = [
            base_path('app'),
            base_path('routes'),
            base_path('resources/js/Pages'),
            base_path('resources/js/Components'),
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

                foreach ($iterator as $file) {
                    if ($file->isFile() && in_array($file->getExtension(), ['php', 'vue', 'js'])) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
