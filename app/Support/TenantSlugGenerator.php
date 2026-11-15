<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Generates URL-safe, unique tenant slugs that match the public site and
 * customer portal route constraints in routes/web.php.
 */
final class TenantSlugGenerator
{
    public const MAX_LENGTH = 63;

    public const SUFFIX_LENGTH = 6;

    public const DEFAULT_BASE = 'workshop';

    /**
     * Must stay aligned with the {tenant} route constraint in routes/web.php:
     * lowercase ASCII alphanumeric + internal hyphens, max 63 characters.
     */
    public const ROUTE_PATTERN = '/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/';

    /**
     * Reserved top-level route segments that must never be issued as a tenant slug.
     *
     * @var list<string>
     */
    public const RESERVED_SLUGS = [
        'app',
        'system',
        'dashboard',
        'login',
        'logout',
        'register',
        'password',
        'forgot-password',
        'reset-password',
        'verify-email',
        'confirm-password',
        'two-factor',
        '2fa',
        'locale',
        'phone',
        'lang',
        'landing-preview',
        'view',
        'impersonate',
        'impersonate-stop',
        'up',
        'storage',
        'livewire',
        'invitations',
        'admin',
        'api',
        'email',
        'page',
        'reports',
        'sanctum',
        'portal',
        'site',
        'userprofile',
    ];

    public static function routeConstraintPattern(): string
    {
        $reserved = implode('|', array_map(
            static fn (string $slug): string => preg_quote($slug, '/'),
            self::RESERVED_SLUGS
        ));

        return '^(?!(?:'.$reserved.')$)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$';
    }

    public static function baseSlug(string $companyName): string
    {
        $normalized = Str::lower(Str::slug($companyName));
        $normalized = (string) preg_replace('/[^a-z0-9-]+/', '-', $normalized);
        $normalized = (string) preg_replace('/-+/', '-', $normalized);
        $normalized = trim($normalized, '-');

        if ($normalized === '' || in_array($normalized, self::RESERVED_SLUGS, true)) {
            $normalized = self::DEFAULT_BASE;
        }

        $maxBaseLength = self::MAX_LENGTH - 1 - self::SUFFIX_LENGTH;
        $truncated = rtrim(substr($normalized, 0, $maxBaseLength), '-');

        return $truncated !== '' ? $truncated : self::DEFAULT_BASE;
    }

    /**
     * Generate a unique, route-valid slug for a new tenant.
     *
     * @param (callable(string): bool)|null $existsChecker
     */
    public static function generate(
        string $companyName,
        ?callable $existsChecker = null,
        int $maxAttempts = 10,
    ): string {
        $base = self::baseSlug($companyName);
        $exists = $existsChecker ?? static fn (string $candidate): bool => Tenant::withTrashed()
            ->where('slug', $candidate)
            ->exists();

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $suffix = Str::lower(Str::random(self::SUFFIX_LENGTH));
            $candidate = $base.'-'.$suffix;

            if (in_array($candidate, self::RESERVED_SLUGS, true)) {
                continue;
            }

            if (preg_match(self::ROUTE_PATTERN, $candidate) !== 1) {
                continue;
            }

            if (! $exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException("Unable to generate a unique tenant slug after {$maxAttempts} attempts.");
    }
}
