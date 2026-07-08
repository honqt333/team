<?php

namespace App\Services\Developer\Scanners;

use App\Services\Developer\Contracts\ScannerInterface;
use Illuminate\Support\Facades\Route;

class SecurityScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'Security & Tenancy Guard Scanner';
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function run(array $context = []): array
    {
        $findings = [];

        // 1. Audit Models for Tenant Scopes
        $modelsPath = base_path('app/Models');
        if (is_dir($modelsPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modelsPath));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    $relativePath = str_replace(base_path(), '', $file->getPathname());

                    // Skip known authentication and parent-scoped/global models
                    $filename = $file->getFilename();
                    $exceptions = ['User.php', 'Role.php', 'SystemAnnouncement.php', 'GoodsReceivedNote.php', 'CenterAddress.php', 'CenterWorkingHour.php'];
                    if (in_array($filename, $exceptions) || str_contains($content, '@bypass-tenancy-scanner')) {
                        continue;
                    }

                    // If model contains tenant_id in schema or matches tenant columns, it MUST use TenantScoped/CenterScoped trait
                    if (str_contains($content, 'tenant_id') || str_contains($content, 'center_id')) {
                        if (!str_contains($content, 'TenantScoped') && !str_contains($content, 'CenterScoped')) {
                            $findings[] = [
                                'severity' => 'critical',
                                'file_path' => $relativePath,
                                'line_number' => 1,
                                'violation_code' => 'missing_tenant_isolation_scope',
                                'description' => "Eloquent model has tenant references but does not implement TenantScoped or CenterScoped query traits. Danger of data leakage!",
                            ];
                        }
                    }
                }
            }
        }

        // 2. Audit Controllers for authorize() calls
        $controllersPath = base_path('app/Http/Controllers/App');
        if (is_dir($controllersPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllersPath));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    $relativePath = str_replace(base_path(), '', $file->getPathname());

                    // Controllers annotated with @bypass-authorization-scanner are protected at route middleware level
                    if (str_contains($content, '@bypass-authorization-scanner')) {
                        continue;
                    }

                    // Check actions that perform write operations: store, update, destroy
                    $hasWriteMethods = preg_match('/\bfunction\s+(store|update|destroy|edit|create)\b/i', $content);
                    if ($hasWriteMethods) {
                        $hasAuthorize = str_contains($content, 'authorize')
                            || str_contains($content, 'Gate::')
                            || str_contains($content, '$this->middleware')
                            || str_contains($content, 'HasMiddleware')
                            || str_contains($content, 'middleware(')
                            || str_contains($content, "->can(")
                            || str_contains($content, 'canAny(');
                        if (!$hasAuthorize) {
                            $findings[] = [
                                'severity' => 'critical',
                                'file_path' => $relativePath,
                                'line_number' => 1,
                                'violation_code' => 'missing_authorization_check',
                                'description' => "Controller handles write actions but lacks explicit policy authorize() or Gate check. Security vulnerability!",
                            ];
                        }
                    }
                }
            }
        }

        // 3. Scan routes looking for unprotected non-public endpoints
        $routes = Route::getRoutes();
        foreach ($routes as $route) {
            $uri = $route->uri();
            $action = $route->getActionName();

            // Exclude assets, sanctum, login, register, public views, and intentional 2FA challenge routes
            if (
                str_starts_with($uri, '_') ||
                str_starts_with($uri, 'sanctum/') ||
                str_contains($uri, 'login') ||
                str_contains($uri, 'register') ||
                str_starts_with($uri, 'view/') ||
                str_contains($uri, '2fa/') // 2FA challenge routes intentionally use partial auth
            ) {
                continue;
            }

            $middleware = $route->middleware();
            $isProtected = false;
            foreach ($middleware as $mw) {
                if (str_contains($mw, 'auth') || str_contains($mw, 'EnsureSystemAdmin') || str_contains($mw, 'signed')) {
                    $isProtected = true;
                    break;
                }
            }

            if (!$isProtected && (str_starts_with($uri, 'app/') || str_starts_with($uri, 'system/'))) {
                $findings[] = [
                    'severity' => 'high',
                    'file_path' => 'routes/web.php',
                    'line_number' => null,
                    'violation_code' => 'unprotected_app_route',
                    'description' => "Route [{$uri}] mapped to [{$action}] is accessible under restricted prefix but lacks auth middleware.",
                ];
            }
        }

        return $findings;
    }
}
