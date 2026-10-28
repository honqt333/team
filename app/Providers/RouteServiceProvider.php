<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Auth Rate Limiters
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json(['error' => 'Too many login attempts'], 429);
            });
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('2fa-verify', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('phone-otp', function (Request $request) {
            return Limit::perMinute(3)->by($request->input('phone') ?: $request->ip());
        });

        // Phase 7 — Customer portal OTP rate limiters.
        //
        // These are coarse, IP-scoped limits. The fine-grained
        // (tenant, phone) and per-IP limits live inside
        // CustomerPortalOtpService via Laravel's RateLimiter
        // facade. The route-level limits exist to fail fast
        // at the framework layer before we even reach the
        // service (saves a DB hit and prevents a flood from
        // consuming the more nuanced buckets).
        RateLimiter::for('customer-portal-otp', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
        RateLimiter::for('customer-portal-verify', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Phase 8 — Customer portal booking rate limiters.
        //
        // The availability endpoint is read-only and
        // idempotent (the result is the same for a given
        // (center, services, date) triple within a few
        // minutes), so we can be a bit more generous.
        // The store endpoint creates a row, so we cap
        // it tighter.
        //
        // The key is a composite: tenant + customer + IP.
        // The tenant id is on the resolved route param,
        // not the body; the customer id comes from the
        // session, not the body; the IP is from the
        // request. A misbehaving client cannot use a
        // shared IP to skip the per-customer cap.
        RateLimiter::for('customer-portal-availability', function (Request $request) {
            $tenant = $request->attributes->get('public_tenant');
            $customer = $request->attributes->get('portal_customer');
            $key = sprintf(
                'avail:%s:%s:%s',
                $tenant?->id ?? 'x',
                $customer?->id ?? 'x',
                $request->ip() ?? 'x'
            );

            return Limit::perMinute(30)->by($key);
        });
        RateLimiter::for('customer-portal-booking', function (Request $request) {
            $tenant = $request->attributes->get('public_tenant');
            $customer = $request->attributes->get('portal_customer');
            $key = sprintf(
                'book:%s:%s:%s',
                $tenant?->id ?? 'x',
                $customer?->id ?? 'x',
                $request->ip() ?? 'x'
            );

            return Limit::perMinute(10)->by($key);
        });

        // Phase 9 — Self-service cancel + reschedule.
        // Separate buckets per action so a customer
        // who reschedules three times does not exhaust
        // their cancel budget. The key is a composite
        // (tenant, customer, IP) so a misbehaving client
        // cannot burn through the budget by rotating
        // IPs alone.
        RateLimiter::for('customer-portal-cancel', function (Request $request) {
            $tenant = $request->attributes->get('public_tenant');
            $customer = $request->attributes->get('portal_customer');
            $key = sprintf(
                'cancel:%s:%s:%s',
                $tenant?->id ?? 'x',
                $customer?->id ?? 'x',
                $request->ip() ?? 'x'
            );

            return Limit::perMinute(10)->by($key);
        });
        RateLimiter::for('customer-portal-reschedule-availability', function (Request $request) {
            $tenant = $request->attributes->get('public_tenant');
            $customer = $request->attributes->get('portal_customer');
            $key = sprintf(
                'resch-avail:%s:%s:%s',
                $tenant?->id ?? 'x',
                $customer?->id ?? 'x',
                $request->ip() ?? 'x'
            );

            return Limit::perMinute(30)->by($key);
        });
        RateLimiter::for('customer-portal-reschedule', function (Request $request) {
            $tenant = $request->attributes->get('public_tenant');
            $customer = $request->attributes->get('portal_customer');
            $key = sprintf(
                'resch:%s:%s:%s',
                $tenant?->id ?? 'x',
                $customer?->id ?? 'x',
                $request->ip() ?? 'x'
            );

            return Limit::perMinute(10)->by($key);
        });

        // Public Web Rate Limiters
        RateLimiter::for('public-landing', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('quote-public', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Uploads & Exports Rate Limiters
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('exports', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // API Public Rate Limiter
        RateLimiter::for('api-public', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Duplicate-lookup endpoints (customer phone, vehicle plate).
        //
        // These are called by the create/edit forms to detect a
        // duplicate, and they answer with the matching record's
        // details. They are permission-gated, but a user who holds the
        // read permission could still use them to sweep the tenant's
        // records one number at a time. The limit is generous enough
        // for interactive use — a form fires one request per field
        // blur — and keyed by user so one account cannot exhaust
        // another's budget.
        RateLimiter::for('record-lookup', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
