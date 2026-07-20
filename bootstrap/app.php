<?php

use App\Http\Middleware\ConvertArabicNumerals;
use App\Http\Middleware\EnsureCenterContext;
use App\Http\Middleware\EnsureSystemAdmin;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\SentryContext;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeam;
use App\Http\Middleware\TrackAiUsage;
use App\Logging\JsonFormatter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\RouteServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetPermissionsTeam::class, // Must be FIRST - sets team before permissions are loaded
            SetLocale::class, // Must be before Inertia to pass correct locale to props
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ConvertArabicNumerals::class,
            PreventBackHistory::class,
        ]);

        // Observability: tag every request with correlation_id + tenant_id
        // so Sentry events and structured log records share the same context.
        // Registered on the global stack so it covers web + api routes.
        $middleware->append(SentryContext::class);

        // Exempt locale switching from CSRF — it's a safe, session-only action
        $middleware->validateCsrfTokens(except: [
            'locale',
        ]);

        // Health probes must NEVER appear in Sentry traces — they fire on every
        // load-balancer ping and would drown out real errors.
        $middleware->validateCsrfTokens(except: [
            'healthz',
            'readyz',
        ]);

        $middleware->alias([
            'tenant.active' => EnsureTenantActive::class,
            'center.context' => EnsureCenterContext::class,
            'system.admin' => EnsureSystemAdmin::class,
            // Spatie Permission middleware
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Ensure TrackAiUsage fails closed BEFORE SubstituteBindings so a
        // request without a tenant_id never triggers a 404 from route
        // model binding. See docs/features/ai-service-suggester/design.md §8.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: TrackAiUsage::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

/*
|--------------------------------------------------------------------------
| Monolog / Structured Logging bootstrap
|--------------------------------------------------------------------------
|
| For any non-local environment we force the *default* logger to emit JSON
| via App\Logging\JsonFormatter so downstream log shippers (Fluent Bit /
| Vector / Loki / Cloud Logging) can index the records without parsing.
| The correlation_id / tenant_id / user_id / route fields are added to
| every record automatically via App\Http\Middleware\SentryContext's
| Log::shareContext() call.
|
| In local we keep Laravel's default stack so developers can read
| human-friendly logs with stack traces in the terminal.
*/
if (! app()->environment('local')) {
    app()->configureMonologUsing(function (Logger $monolog): void {
        $formatter = new JsonFormatter;
        $stream = env('LOG_STRUCTURED_STREAM', 'php://stdout');

        foreach ($monolog->getHandlers() as $handler) {
            if (method_exists($handler, 'setFormatter')) {
                $handler->setFormatter($formatter);
            }
        }

        // Always add a stdout JSON handler in addition to existing ones so
        // container log shippers can scrape stdout regardless of the configured
        // LOG_CHANNEL. We respect LOG_STRUCTURED_STREAM for the destination
        // (e.g. php://stderr in production pods that stream stderr only).
        $monolog->pushHandler((new StreamHandler($stream, Logger::INFO))->setFormatter($formatter));
    });
}
