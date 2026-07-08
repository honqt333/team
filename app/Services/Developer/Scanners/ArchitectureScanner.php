<?php

namespace App\Services\Developer\Scanners;

use App\Services\Developer\Contracts\ScannerInterface;
use Illuminate\Support\Facades\Config;

class ArchitectureScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Architecture Governance Scanner';
    }

    public function getCategory(): string
    {
        return 'architecture';
    }

    public function run(array $context = []): array
    {
        $findings = [];
        $controllerMaxLines = Config::get('developer_center.thresholds.controller_max_lines', 500);
        $controllerMaxMethods = Config::get('developer_center.thresholds.controller_max_methods', 10);
        $modelMaxLines = Config::get('developer_center.thresholds.model_max_lines', 300);
        $fileMaxLines = Config::get('developer_center.thresholds.file_max_lines', 600);

        // Scan App Controllers
        $controllersPath = base_path('app/Http/Controllers');
        if (is_dir($controllersPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllersPath));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    $lineCount = count(explode("\n", $content));
                    $relativePath = str_replace(base_path(), '', $file->getPathname());

                    // Count functions/actions
                    $methodCount = preg_match_all('/\bpublic\s+function\s+\w+\s*\(/i', $content);

                    if ($lineCount > $controllerMaxLines) {
                        $findings[] = [
                            'severity' => $lineCount > 1000 ? 'critical' : 'high',
                            'file_path' => $relativePath,
                            'line_number' => 1,
                            'violation_code' => 'god_controller',
                            'description' => "Controller has too many lines ({$lineCount} lines). Should be split into smaller service classes or Actions.",
                        ];
                    }

                    if ($methodCount > $controllerMaxMethods) {
                        $findings[] = [
                            'severity' => 'medium',
                            'file_path' => $relativePath,
                            'line_number' => 1,
                            'violation_code' => 'fat_controller_methods',
                            'description' => "Controller contains too many public actions ({$methodCount} actions). Limit is {$controllerMaxMethods}.",
                        ];
                    }
                }
            }
        }

        // Scan Models
        $modelsPath = base_path('app/Models');
        if (is_dir($modelsPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modelsPath));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    $lineCount = count(explode("\n", $content));
                    $relativePath = str_replace(base_path(), '', $file->getPathname());

                    // Count relationships (belongsTo, hasMany, belongsToMany, hasOne)
                    $relationCount = preg_match_all('/\b(?:belongsTo|hasMany|belongsToMany|hasOne|morphMany|morphTo)\s*\(/i', $content);

                    if ($lineCount > $modelMaxLines) {
                        $findings[] = [
                            'severity' => 'high',
                            'file_path' => $relativePath,
                            'line_number' => 1,
                            'violation_code' => 'god_model',
                            'description' => "Eloquent Model exceeds line count thresholds ({$lineCount} lines). Relations and custom logic should be refactored into traits or domains.",
                        ];
                    }

                    if ($relationCount > 6) {
                        $findings[] = [
                            'severity' => 'medium',
                            'file_path' => $relativePath,
                            'line_number' => 1,
                            'violation_code' => 'excessive_model_relations',
                            'description' => "Model declares too many inline Eloquent relations ({$relationCount}). Standard limit is 6.",
                        ];
                    }
                }
            }
        }

        // Scan Vue Files for Large Files
        $jsPath = base_path('resources/js');
        if (is_dir($jsPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($jsPath));
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['vue', 'js'])) {
                    $content = file_get_contents($file->getPathname());
                    $lineCount = count(explode("\n", $content));
                    $relativePath = str_replace(base_path(), '', $file->getPathname());

                    if ($lineCount > $fileMaxLines) {
                        $findings[] = [
                            'severity' => 'high',
                            'file_path' => $relativePath,
                            'line_number' => 1,
                            'violation_code' => 'large_frontend_file',
                            'description' => "Vue Page or Component is too large ({$lineCount} lines). Should be modularized into sub-components.",
                        ];
                    }
                }
            }
        }

        return $findings;
    }
}
