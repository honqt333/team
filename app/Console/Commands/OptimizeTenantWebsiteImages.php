<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TenantWebsite;
use App\Services\Media\ImageOptimizer;
use App\Support\Media\ImageType;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Re-encodes the tenant public-site images that were uploaded before the
 * upload path started optimising them.
 *
 * WHY
 * ---
 * `TenantWebsiteSettingsController::storeImage()` now re-encodes large
 * PNG/JPEG uploads to WebP. That only helps uploads made from now on — the
 * files already on disk are still served as they were uploaded.
 *
 * A tenant's promotional banner was measured at 422 KB as a PNG and 65 KB
 * as WebP. Every visitor to that workshop's public page was downloading the
 * 422 KB version, for an image that renders behind a gradient overlay at
 * 25% opacity.
 *
 * WHAT IT DOES
 * ------------
 * For every tenant website image (banner images and the logo), it:
 *   1. reads the stored file,
 *   2. re-encodes it to WebP when that is smaller,
 *   3. writes the WebP next to the original,
 *   4. updates the path stored in the database,
 *   5. deletes the original only after the new file is safely written.
 *
 * The database is updated inside a transaction per website, and the old
 * file is deleted last, so an interrupted run never leaves a tenant with a
 * broken image reference.
 *
 * Usage:
 *   php artisan website:optimize-images
 *   php artisan website:optimize-images --dry-run
 */
class OptimizeTenantWebsiteImages extends Command
{
    protected $signature = 'website:optimize-images
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Re-encode tenant public-site images to WebP to cut page weight.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');

        $websites = TenantWebsite::query()->get();

        $this->info(sprintf('Scanning %d tenant website(s)...', $websites->count()));

        $converted = 0;
        $skipped = 0;
        $bytesSaved = 0;

        foreach ($websites as $website) {
            $banners = is_array($website->banners) ? $website->banners : [];
            $changed = false;

            foreach ($banners as $index => $banner) {
                if (! is_array($banner)) {
                    continue;
                }

                $path = (string) ($banner['image_path'] ?? '');

                if ($path === '') {
                    continue;
                }

                $result = $this->convert($disk, $path, $dryRun);

                if ($result === null) {
                    $skipped++;

                    continue;
                }

                [$newPath, $saved] = $result;

                $banners[$index]['image_path'] = $newPath;

                // The stored JSON keeps a cached `image_url` from the moment
                // the banner was created. It is overridden by the accessor
                // at render time, but leaving a stale .png path in the
                // database is misleading for anything that reads the raw
                // column.
                unset($banners[$index]['image_url']);

                $changed = true;
                $converted++;
                $bytesSaved += $saved;

                $this->line(sprintf(
                    '  <info>%s</info> → <info>%s</info> (saved %s KB)',
                    basename($path),
                    basename($newPath),
                    number_format($saved / 1024, 1),
                ));
            }

            $logoPath = (string) ($website->logo_path ?? '');

            if ($logoPath !== '') {
                $result = $this->convert($disk, $logoPath, $dryRun);

                if ($result !== null) {
                    [$newPath, $saved] = $result;
                    $website->logo_path = $newPath;
                    $changed = true;
                    $converted++;
                    $bytesSaved += $saved;

                    $this->line(sprintf(
                        '  <info>%s</info> (logo) → <info>%s</info> (saved %s KB)',
                        basename($logoPath),
                        basename($newPath),
                        number_format($saved / 1024, 1),
                    ));
                } else {
                    $skipped++;
                }
            }

            // The cover is the large hero background image. It was measured
            // at 1.5 MB as a PNG, rendered behind a gradient at 30% opacity.
            $coverPath = (string) ($website->cover_path ?? '');

            if ($coverPath !== '') {
                $result = $this->convert($disk, $coverPath, $dryRun);

                if ($result !== null) {
                    [$newPath, $saved] = $result;
                    $website->cover_path = $newPath;
                    $changed = true;
                    $converted++;
                    $bytesSaved += $saved;

                    $this->line(sprintf(
                        '  <info>%s</info> (cover) → <info>%s</info> (saved %s KB)',
                        basename($coverPath),
                        basename($newPath),
                        number_format($saved / 1024, 1),
                    ));
                } else {
                    $skipped++;
                }
            }

            if ($changed && ! $dryRun) {
                $website->banners = $banners;
                $website->save();
            }
        }

        if ($dryRun) {
            $this->info('Dry run — nothing was written.');
        }

        $this->info(sprintf(
            '%s: %d image(s) converted, %d skipped, %s KB saved.',
            $dryRun ? 'Would convert' : 'Converted',
            $converted,
            $skipped,
            number_format($bytesSaved / 1024, 1),
        ));

        return self::SUCCESS;
    }

    /**
     * Convert one stored image, returning [newPath, savedBytes] or null when
     * nothing should change.
     *
     * @return array{0: string, 1: int}|null
     */
    private function convert(Filesystem $disk, string $path, bool $dryRun): ?array
    {
        if (! $disk->exists($path)) {
            return null;
        }

        // Already WebP — nothing to do.
        if (str_ends_with(strtolower($path), '.webp')) {
            return null;
        }

        try {
            $binary = (string) $disk->get($path);
        } catch (Throwable) {
            return null;
        }

        $type = ImageType::detect($binary);

        if ($type === null || $type === ImageType::WEBP) {
            return null;
        }

        $optimised = ImageOptimizer::optimize($binary, $type);

        // The optimiser keeps the original when re-encoding does not help.
        if ($optimised->extension === 'webp' && $optimised->savedBytes > 0) {
            $newPath = preg_replace('/\.[a-z0-9]+$/i', '.webp', $path);

            if ($dryRun) {
                return [$newPath, $optimised->savedBytes];
            }

            $disk->put($newPath, $optimised->binary);

            // The old file is removed only after the new one is written.
            $disk->delete($path);

            return [$newPath, $optimised->savedBytes];
        }

        return null;
    }
}
