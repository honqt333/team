<?php

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
        App\Services\Developer\Scanners\ArchitectureScanner::class,
        App\Services\Developer\Scanners\SecurityScanner::class,
        App\Services\Developer\Scanners\UiScanner::class,
        App\Services\Developer\Scanners\DatabaseScanner::class,
        App\Services\Developer\Scanners\PerformanceScanner::class,
        App\Services\Developer\Scanners\TestScanner::class,
        App\Services\Developer\Scanners\BusinessLogicScanner::class,
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
