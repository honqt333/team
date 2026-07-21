<?php

declare(strict_types=1);

namespace App\Services\Developer\Scanners;

use App\Services\Developer\Contracts\ScannerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BusinessLogicScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Business Logic Semantics Scanner';
    }

    public function getCategory(): string
    {
        return 'documentation'; // maps to documentation/compliance score
    }

    public function run(array $context = []): array
    {
        $findings = [];

        // 1. Audit Stock Adjustments for Activity Logging
        $inventoryControllersPath = base_path('app/Http/Controllers/App');

        if (is_dir($inventoryControllersPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($inventoryControllersPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php' && str_contains($file->getFilename(), 'Inventory')) {
                    $content = file_get_contents($file->getPathname());
                    $relativePath = str_replace(base_path(), '', $file->getPathname());

                    // If inventory controller handles writes (store, update, adjust) but lacks logActivity call
                    if (preg_match('/\bfunction\s+(store|update|adjust|receipt|issue)\b/i', $content)) {
                        if (! str_contains($content, 'logActivity')) {
                            $findings[] = [
                                'severity' => 'high',
                                'file_path' => $relativePath,
                                'line_number' => 1,
                                'violation_code' => 'missing_activity_log_inventory',
                                'description' => "Inventory action handles stock adjustments but does not log activity via 'logActivity()'. Audit trail missing!",
                            ];
                        }
                    }
                }
            }
        }

        // 2. Audit double-entry mapping in financial controllers
        foreach (['Invoice', 'Payment'] as $financialEntity) {
            $financialControllersPath = base_path('app/Http/Controllers/App');

            if (is_dir($financialControllersPath)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($financialControllersPath));

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php' && str_contains($file->getFilename(), $financialEntity)) {
                        $content = file_get_contents($file->getPathname());
                        $relativePath = str_replace(base_path(), '', $file->getPathname());

                        if (preg_match('/\bfunction\s+(store|update|complete|pay)\b/i', $content)) {
                            // Verify double-entry creation is hooked (e.g. InvoiceService or ledger transaction entry)
                            $hasLedgerPosting = str_contains($content, 'InvoiceService') || str_contains($content, 'PaymentService') || str_contains($content, 'transactions') || str_contains($content, 'Billing');

                            if (! $hasLedgerPosting) {
                                $findings[] = [
                                    'severity' => 'high',
                                    'file_path' => $relativePath,
                                    'line_number' => 1,
                                    'violation_code' => 'missing_ledger_posting',
                                    'description' => "Financial controller handles [{$financialEntity}] lifecycle but does not trigger double-entry journal ledger mapping service.",
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $findings;
    }
}
