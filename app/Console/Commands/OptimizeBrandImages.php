<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Media\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Shrinks the platform brand images that were uploaded before the upload
 * path started optimising them.
 *
 * WHY
 * ---
 * `WebsiteSettingsController::uploadImage()` now rewrites the favicon to a
 * 64×64 PNG and scales the logo down to 512 px. That only helps uploads
 * made from now on — the files already on disk are still the originals.
 *
 * Measured on this installation:
 *
 *   website.favicon  68 KB PNG, downloaded by the browser on EVERY page
 *                    view of the whole application, for an icon that
 *                    renders at 16 px.
 *   website.logo     36 KB PNG, rendered at 48 px in the header.
 *
 * WHAT IT DOES
 * ------------
 * For each of the two settings it:
 *   1. reads the stored file,
 *   2. rebuilds it at the right size,
 *   3. writes it back over the same path (the URL in the settings does not
 *      change, so nothing else has to be updated),
 *   4. keeps the original as a `.original` file next to it, so the change
 *      can be undone by hand.
 *
 * Usage:
 *   php artisan website:optimize-brand-images
 *   php artisan website:optimize-brand-images --dry-run
 */
class OptimizeBrandImages extends Command
{
    protected $signature = 'website:optimize-brand-images
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Shrink the platform favicon and logo to the size they are actually rendered at.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');

        $targets = [
            'website.favicon' => 'favicon',
            'website.logo' => 'logo',
        ];

        $changed = 0;

        foreach ($targets as $key => $kind) {
            $setting = Setting::where('key', $key)->first();

            if (! $setting || $setting->value === null || $setting->value === '') {
                $this->line("· {$key}: not set — skipped.");

                continue;
            }

            $path = $this->diskPath((string) $setting->value);

            if ($path === null || ! is_file($path)) {
                $this->line("· {$key}: file not found — skipped.");

                continue;
            }

            $binary = (string) file_get_contents($path);
            $before = strlen($binary);

            $optimised = $kind === 'favicon'
                ? ImageOptimizer::favicon($binary)
                : ImageOptimizer::logo($binary);

            $after = strlen($optimised->binary);

            if ($optimised->binary === '' || $after >= $before) {
                $this->line("· {$key}: already small enough ({$this->kb($before)} KB) — left alone.");

                continue;
            }

            if ($dryRun) {
                $this->line("· {$key}: would go from {$this->kb($before)} KB to {$this->kb($after)} KB.");

                continue;
            }

            // Keep the original next to the file so the change is reversible
            // by hand.
            if (! is_file($path.'.original')) {
                copy($path, $path.'.original');
            }

            file_put_contents($path, $optimised->binary);

            $this->line("· {$key}: {$this->kb($before)} KB → {$this->kb($after)} KB (original kept as .original)");
            $changed++;
        }

        if (! $dryRun && $changed > 0) {
            Setting::query()->whereIn('key', array_keys($targets))->update(['updated_at' => now()]);
        }

        $this->info($dryRun
            ? 'Dry run — nothing was written.'
            : "Done. {$changed} file(s) rewritten.");

        return self::SUCCESS;
    }

    /**
     * Map a stored URL back to a file on the public disk.
     *
     * The stored value is an absolute URL (asset() produced it), so the
     * path has to be extracted first. The file names are random hashes, so
     * a path that resolves to an existing file on our disk IS our file —
     * the host in the URL can be anything (the site may have moved).
     */
    private function diskPath(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $relative = $parts['path'] ?? $url;

        foreach (['/storage/', 'storage/'] as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                $relative = substr($relative, strlen($prefix));

                break;
            }
        }

        $path = Storage::disk('public')->path($relative);

        if (str_contains($path, '..')) {
            return null;
        }

        return is_file($path) ? $path : null;
    }

    private function kb(int $bytes): string
    {
        return (string) round($bytes / 1024, 1);
    }
}
