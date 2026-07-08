<?php

namespace App\Services\Developer\Scanners;

use App\Services\Developer\Contracts\ScannerInterface;

class UiScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'UI Standards SFC AST Scanner';
    }

    public function getCategory(): string
    {
        return 'ui';
    }

    public function run(array $context = []): array
    {
        $findings = [];
        $componentStats = [
            '(?:PrimaryButton|SecondaryButton|DangerButton|BackButton)' => ['compliant' => 0, 'violations' => 0],
            'TextInput' => ['compliant' => 0, 'violations' => 0],
            'SelectInput' => ['compliant' => 0, 'violations' => 0],
        ];

        $pagesPath = base_path('resources/js/Pages');
        $componentsPath = base_path('resources/js/Components');

        $vueFiles = [];
        foreach ([$pagesPath, $componentsPath] as $path) {
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'vue') {
                        $vueFiles[] = $file->getPathname();
                    }
                }
            }
        }

        // Limit AST scanning to max 50 files in a single manual run to prevent timeout, or all if quick
        $vueFiles = array_slice($vueFiles, 0, 50);

        foreach ($vueFiles as $filePath) {
            $relativePath = str_replace(base_path(), '', $filePath);
            
            // Execute Node script AST parser
            $escapedPath = escapeshellarg($filePath);
            $command = "node " . base_path('scripts/vue_sfc_parser.js') . " {$escapedPath} 2>&1";
            $output = shell_exec($command);

            if ($output && !str_starts_with($output, 'JSON_ERROR')) {
                $parsed = json_decode($output, true);
                if (json_last_error() === JSON_ERROR_NONE && $parsed) {
                    
                    // 1. Alert if not using script setup (standard rule)
                    if (!$parsed['hasScriptSetup']) {
                        $findings[] = [
                            'severity' => 'medium',
                            'file_path' => $relativePath,
                            'line_number' => 1,
                            'violation_code' => 'missing_script_setup',
                            'description' => "Vue component does not use <script setup>. Options API or legacy syntax is deprecated.",
                        ];
                    }

                    // 2. Alert on raw elements violations and aggregate stats
                    if (isset($parsed['rawElements']) && is_array($parsed['rawElements'])) {
                        foreach ($parsed['rawElements'] as $el) {
                            $comp = $el['component'];
                            if (isset($componentStats[$comp])) {
                                $componentStats[$comp]['compliant'] += $el['compliantCount'];
                                $componentStats[$comp]['violations'] += $el['violationCount'];
                            }

                            if ($el['violationCount'] > 0) {
                                $friendlyComponent = $comp === '(?:PrimaryButton|SecondaryButton|DangerButton|BackButton)' ? 'PrimaryButton' : $comp;
                                $findings[] = [
                                    'severity' => 'medium',
                                    'file_path' => $relativePath,
                                    'line_number' => null,
                                    'violation_code' => 'raw_html_' . $el['name'] . '_used',
                                    'description' => "Raw HTML tag <{$el['name']}> is used {$el['violationCount']} time(s). Replace with standard UI component <{$friendlyComponent}>.",
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Add aggregate stats as virtual findings with is_stat flag
        $displayNames = [
            '(?:PrimaryButton|SecondaryButton|DangerButton|BackButton)' => 'PrimaryButton',
            'TextInput' => 'TextInput',
            'SelectInput' => 'SelectInput',
        ];

        foreach ($componentStats as $compName => $stats) {
            $findings[] = [
                'is_stat' => true,
                'component_name' => $displayNames[$compName] ?? $compName,
                'count_compliant' => $stats['compliant'],
                'count_violations' => $stats['violations'],
            ];
        }

        return $findings;
    }
}
