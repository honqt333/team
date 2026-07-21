<?php

declare(strict_types=1);

namespace App\Services\Developer\Scanners;

use App\Services\Developer\Contracts\ScannerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class TestScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Test Suite & Coverage Scanner';
    }

    public function getCategory(): string
    {
        return 'testing';
    }

    public function run(array $context = []): array
    {
        $findings = [];
        $unitTestsCount = 0;
        $featureTestsCount = 0;

        $testsPath = base_path('tests');

        if (is_dir($testsPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Test.php')) {
                    $relativePath = str_replace(base_path(), '', $file->getPathname());

                    if (str_contains($relativePath, '/Unit/')) {
                        $unitTestsCount++;
                    } elseif (str_contains($relativePath, '/Feature/')) {
                        $featureTestsCount++;
                    }
                }
            }
        }

        // Check if the total test files count is low for an enterprise platform
        $totalTests = $unitTestsCount + $featureTestsCount;

        if ($totalTests < 15) {
            $findings[] = [
                'severity' => 'high',
                'file_path' => 'tests/',
                'line_number' => null,
                'violation_code' => 'insufficient_test_suite_coverage',
                'description' => "The project has only [{$totalTests}] test files. Test coverage is insufficient for an enterprise-grade ERP system.",
            ];
        }

        // Read test results from phpunit output if run was recently executed
        $junitPath = base_path('storage/app/developer/junit.xml');

        if (file_exists($junitPath)) {
            try {
                $xml = simplexml_load_file($junitPath);

                if ($xml && isset($xml->testsuite)) {
                    $failures = (int) $xml->testsuite['failures'];
                    $errors = (int) $xml->testsuite['errors'];

                    if ($failures > 0 || $errors > 0) {
                        $findings[] = [
                            'severity' => 'critical',
                            'file_path' => 'tests/phpunit',
                            'line_number' => null,
                            'violation_code' => 'phpunit_test_failures',
                            'description' => "Last test execution had [{$failures}] failures and [{$errors}] errors. Fix tests immediately before deploying!",
                        ];
                    }
                }
            } catch (Throwable $e) {
                // Ignore XML parsing errors
            }
        }

        return $findings;
    }
}
