<?php

declare(strict_types=1);

use App\Services\Developer\Scanners\ArchitectureScanner;
use App\Services\Developer\Scanners\BusinessLogicScanner;
use App\Services\Developer\Scanners\DatabaseScanner;
use App\Services\Developer\Scanners\PerformanceScanner;
use App\Services\Developer\Scanners\SecurityScanner;
use App\Services\Developer\Scanners\TestScanner;
use App\Services\Developer\Scanners\UiScanner;

return [
    /*
    |--------------------------------------------------------------------------
    | Developer Center Scanners
    |--------------------------------------------------------------------------
    |
    | Registers the static, runtime, and database inspectors executed by
    | the central AuditOrchestrator. All scanners must implement the
    | App\Services\Developer\Contracts\ScannerInterface.
    |
    */
    'scanners' => [
        ArchitectureScanner::class,
        SecurityScanner::class,
        UiScanner::class,
        DatabaseScanner::class,
        PerformanceScanner::class,
        TestScanner::class,
        BusinessLogicScanner::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Threshold Configurations & Release Gates
    |--------------------------------------------------------------------------
    |
    | Deployment and staging blockers. Modifying these impacts release checks.
    |
    */
    'gates' => [
        'min_security_score' => 100.0,
        'min_isolation_score' => 100.0,
        'allow_failed_tests' => false,
        'min_overall_score' => 90.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | God Class Thresholds
    |--------------------------------------------------------------------------
    |
    | Defines maximum line sizes and structural complexities before flagging
    | classes as God components.
    |
    */
    'thresholds' => [
        'controller_max_lines' => 500,
        'controller_max_methods' => 10,
        'model_max_lines' => 300,
        'model_max_relations' => 6,
        'file_max_lines' => 2500,
        'slow_query_time_ms' => 100.0,
    ],
];
