<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\TenantWebsite;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Builds the 1200×630 JPEG used for social share previews.
 *
 * WHY THIS EXISTS
 * ---------------
 * A share card is rendered by Facebook, X, WhatsApp and Slack — not by the
 * visitor's browser. Two facts make that context different from the page
 * itself:
 *
 *  1. The image must be JPEG or PNG. WebP is NOT supported by the Open
 *     Graph crawlers: the banner of the tenant site was optimised to WebP
 *     (422 KB → 67 KB, a win for the page) and the very same file was
 *     being advertised as `og:image`, where it renders as NOTHING.
 *  2. The URL must be ABSOLUTE. A relative `og:image` is ignored by every
 *     platform, so the card falls back to a bare text link.
 *
 * The image is also generated rather than picked: a banner is 1920×960 and
 * a logo is square, and neither is a 1.91:1 card. Cropping to the card
 * ratio is what makes the preview look deliberate.
 *
 * NO TEXT IS DRAWN. GD has no Arabic shaping and no bidi: an Arabic
 * tenant name would come out as disconnected letters in the wrong order,
 * which is worse than no name at all. The card is therefore built from the
 * tenant's own photography and their brand colour, which need no shaping.
 * The tenant's NAME lives in the `og:title` next to the image.
 */
final class OpenGraphImage
{
    /**
     * The card ratio every social platform expects (1.91:1).
     */
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /**
     * JPEG quality. The card is a photo with an overlay — 82 keeps the
     * banding out of the gradient without pushing the file over ~200 KB.
     */
    public const QUALITY = 82;

    /**
     * Used when the tenant has no photography of their own.
     */
    private const DEFAULT_BRAND = '#0f172a';

    /**
     * The JPEG bytes of a tenant's share card.
     *
     * The result is cached on the public disk under a name that carries a
     * fingerprint of the sources, so a new banner produces a new file and
     * the old one is simply never requested again.
     */
    public function forTenant(TenantWebsite $website, string $slug): string
    {
        $photo = $this->photoPath($website);
        $logo = $this->logoPath($website);
        $brand = $website->effective_primary_color ?: self::DEFAULT_BRAND;

        $fingerprint = substr(sha1(implode('|', [
            $this->stamp($photo),
            $this->stamp($logo),
            $brand,
        ])), 0, 12);

        $cachePath = "og/{$slug}-{$fingerprint}.jpg";
        $disk = Storage::disk('public');

        try {
            if ($disk->exists($cachePath)) {
                $cached = $disk->get($cachePath);

                if (is_string($cached) && $cached !== '') {
                    return $cached;
                }
            }
        } catch (Throwable) {
            // A read-only disk must not break the page. Fall through to
            // rendering it in memory.
        }

        $bytes = $this->render($photo, $logo, $brand);

        try {
            $disk->put($cachePath, $bytes);
        } catch (Throwable) {
            // The cache is an optimisation, not a requirement.
        }

        return $bytes;
    }

    /**
     * Draw the card.
     */
    public function render(?string $photoPath, ?string $logoPath, string $brandColor): string
    {
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if ($canvas === false) {
            // GD without truecolor support is not a scenario this project
            // supports; return an empty JPEG rather than a fatal error.
            return '';
        }

        $this->paintBackground($canvas, $brandColor);

        if ($photoPath !== null && $this->paintPhoto($canvas, $photoPath)) {
            $this->paintOverlay($canvas);
        } elseif ($logoPath !== null) {
            $this->paintLogo($canvas, $logoPath);
        }

        ob_start();
        imagejpeg($canvas, null, self::QUALITY);
        $bytes = (string) ob_get_clean();

        imagedestroy($canvas);

        return $bytes;
    }

    /**
     * A vertical gradient from the brand colour into a darker shade of it.
     */
    private function paintBackground(GdImage $canvas, string $brandColor): void
    {
        [$r, $g, $b] = $this->rgb($brandColor);

        // The bottom of the card is a darker version of the brand colour,
        // so the card still reads as the tenant's brand.
        $dr = (int) round($r * 0.45);
        $dg = (int) round($g * 0.45);
        $db = (int) round($b * 0.45);

        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / max(1, self::HEIGHT - 1);
            $color = imagecolorallocate(
                $canvas,
                (int) round($r + ($dr - $r) * $t),
                (int) round($g + ($dg - $g) * $t),
                (int) round($b + ($db - $b) * $t)
            );
            imagefilledrectangle($canvas, 0, $y, self::WIDTH, $y, $color);
        }
    }

    /**
     * Cover-crop the photo to the card ratio and draw it.
     */
    private function paintPhoto(GdImage $canvas, string $photoPath): bool
    {
        if (! is_file($photoPath) || ! is_readable($photoPath)) {
            return false;
        }

        $binary = @file_get_contents($photoPath);

        if ($binary === false || $binary === '') {
            return false;
        }

        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            return false;
        }

        try {
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);

            $targetRatio = self::WIDTH / self::HEIGHT;
            $sourceRatio = $sourceWidth / $sourceHeight;

            if ($sourceRatio > $targetRatio) {
                // Too wide: cut the sides.
                $cropWidth = (int) round($sourceHeight * $targetRatio);
                $cropHeight = $sourceHeight;
                $cropX = (int) round(($sourceWidth - $cropWidth) / 2);
                $cropY = 0;
            } else {
                // Too tall: cut the top and the bottom, biased upwards —
                // the interesting part of a workshop photo is the upper half.
                $cropWidth = $sourceWidth;
                $cropHeight = (int) round($sourceWidth / $targetRatio);
                $cropX = 0;
                $cropY = (int) round(($sourceHeight - $cropHeight) * 0.35);
            }

            imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                $cropX,
                $cropY,
                self::WIDTH,
                self::HEIGHT,
                max(1, $cropWidth),
                max(1, $cropHeight)
            );

            return true;
        } finally {
            imagedestroy($source);
        }
    }

    /**
     * A soft dark gradient over the bottom of the photo, so the card has
     * the same weight as the rest of the design language.
     */
    private function paintOverlay(GdImage $canvas): void
    {
        for ($y = (int) (self::HEIGHT * 0.55); $y < self::HEIGHT; $y++) {
            $t = ($y - self::HEIGHT * 0.55) / (self::HEIGHT * 0.45);
            $alpha = (int) round(90 * $t);
            $color = imagecolorallocatealpha($canvas, 8, 12, 24, max(0, 127 - $alpha));
            imagefilledrectangle($canvas, 0, $y, self::WIDTH, $y, $color);
        }
    }

    /**
     * Draw the tenant's logo centred on the brand gradient.
     */
    private function paintLogo(GdImage $canvas, string $logoPath): void
    {
        if (! is_file($logoPath) || ! is_readable($logoPath)) {
            return;
        }

        $binary = @file_get_contents($logoPath);

        if ($binary === false || $binary === '') {
            return;
        }

        $logo = @imagecreatefromstring($binary);

        if ($logo === false) {
            return;
        }

        try {
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);

            $maxHeight = (int) (self::HEIGHT * 0.55);
            $maxWidth = (int) (self::WIDTH * 0.6);
            $scale = min($maxWidth / $logoWidth, $maxHeight / $logoHeight);

            $targetWidth = max(1, (int) round($logoWidth * $scale));
            $targetHeight = max(1, (int) round($logoHeight * $scale));

            imagecopyresampled(
                $canvas,
                $logo,
                (int) round((self::WIDTH - $targetWidth) / 2),
                (int) round((self::HEIGHT - $targetHeight) / 2),
                0,
                0,
                $targetWidth,
                $targetHeight,
                $logoWidth,
                $logoHeight
            );
        } finally {
            imagedestroy($logo);
        }
    }

    /**
     * The tenant's best available photography.
     */
    private function photoPath(TenantWebsite $website): ?string
    {
        foreach ($website->effective_banners as $banner) {
            if (! empty($banner['image_path'])) {
                $path = Storage::disk('public')->path($banner['image_path']);

                if (is_file($path)) {
                    return $path;
                }
            }
        }

        return $this->localPath($website->cover_url);
    }

    /**
     * The tenant's logo, used when there is no photography.
     */
    private function logoPath(TenantWebsite $website): ?string
    {
        return $this->localPath($website->logo_url);
    }

    /**
     * Map a stored URL to a file on the public disk.
     *
     * Only local files are used: a remote URL would mean fetching an
     * untrusted image during a page render.
     */
    private function localPath(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return null;
        }

        $relative = $url;

        foreach (['/storage/', 'storage/'] as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                $relative = substr($relative, strlen($prefix));

                break;
            }
        }

        $path = Storage::disk('public')->path($relative);

        return is_file($path) ? $path : null;
    }

    /**
     * A fingerprint of a source file: its path, size and modification time.
     */
    private function stamp(?string $path): string
    {
        if ($path === null || ! is_file($path)) {
            return 'none';
        }

        return $path.'|'.filesize($path).'|'.filemtime($path);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            $hex = ltrim(self::DEFAULT_BRAND, '#');
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
