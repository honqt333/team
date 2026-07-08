<?php

namespace App\Services\Developer\Contracts;

use App\Models\Developer\AuditSnapshot;

interface ScannerInterface
{
    /**
     * Get the descriptive name of the scanner.
     */
    public function getName(): string;

    /**
     * Get the category of the scanner (e.g. 'security', 'architecture', etc.).
     */
    public function getCategory(): string;

    /**
     * Execute the scanner diagnostics and return an array of findings.
     *
     * Each finding should contain:
     * - 'severity': 'low'|'medium'|'high'|'critical'
     * - 'file_path': string (absolute or relative path)
     * - 'line_number': int|null
     * - 'violation_code': string
     * - 'description': string
     * - 'metadata': array (optional)
     */
    public function run(array $context = []): array;
}
