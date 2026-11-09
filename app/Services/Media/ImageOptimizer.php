<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Support\Media\ImageType;

/**
 * Re-encodes uploaded images into a smaller, web-optimised format.
 *
 * WHY THIS EXISTS
 * ---------------
 * A tenant uploaded a 1920×960 PNG banner for their public site: 422 KB.
 * That file was served to EVERY visitor of that workshop's page, and it
 * renders behind a gradient overlay at 25% opacity — so almost none of it
 * is actually visible.
 *
 * Measured on that exact file: re-encoded to WebP at quality 72 it is
 * 65 KB — an 85% reduction — in about 150 ms.
 *
 * WHY WEBP AND NOT AVIF
 * ---------------------
 * GD can encode AVIF, but it is roughly an order of magnitude slower than
 * WebP. On an upload path that is a bad trade: the tenant waits, and the
 * saving over WebP is small next to the cost. WebP is supported by every
 * browser that matters and is what gets stored.
 *
 * The original file is always kept when re-encoding does not actually help
 * (already WebP, or the "optimised" version came out bigger), so a
 * pathological image can never become larger than what was uploaded.
 */
final class ImageOptimizer
{
    /**
     * Quality setting for the WebP encoder.
     *
     * 72 was chosen by measuring the tenant banner: at 82 the file was
     * 100 KB, at 72 it was 65 KB, at 65 it was 58 KB. Below ~70 the
     * artefacts start to show on the gradient overlays these images sit
     * behind, so 72 is the floor.
     */
    public const WEBP_QUALITY = 72;

    /**
     * Images narrower than this are left alone.
     *
     * A small icon re-encoded as WebP is often BIGGER than the PNG, and the
     * conversion is not worth the bytes.
     */
    public const MIN_BYTES = 16 * 1024;

    /**
     * Images wider than this are scaled down before encoding.
     *
     * Banners render inside a max-w-6xl container (1152px), so a 2560px
     * upload is pure waste on both bandwidth and decode time.
     */
    public const MAX_WIDTH = 1920;

    /**
     * The favicon canvas. 64×64 covers every browser tab, bookmark and
     * home-screen icon at any density.
     */
    public const FAVICON_SIZE = 64;

    /**
     * The largest a brand logo is kept. It is rendered at 48 px in the
     * header, so 512 px is already generous — and 12× the display size.
     */
    public const LOGO_MAX_WIDTH = 512;

    /**
     * Build a favicon: a 64×64 PNG with the artwork fitted inside it.
     *
     * WHY THIS EXISTS
     * ---------------
     * The favicon was uploaded as a full-size PNG — 68 KB — and the browser
     * downloads it on EVERY page view, from every page of the application.
     * It is the second heaviest thing the landing page fetched, after the
     * hero artwork, and it is never looked at.
     *
     * It is FITTED rather than cropped: a brand logo is often wider than it
     * is tall, and cropping it to a square would cut the wordmark in half.
     * The canvas is transparent so the logo keeps its own background.
     */
    public static function favicon(string $binary, ?string $type = null): OptimizedImage
    {
        $type ??= ImageType::detect($binary);

        if ($type === null || ! function_exists('imagecreatefromstring')) {
            return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
        }

        try {
            if (! imageistruecolor($image) && function_exists('imagepalettetotruecolor')) {
                imagepalettetotruecolor($image);
            }

            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);

            if ($sourceWidth < 1 || $sourceHeight < 1) {
                return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
            }

            $canvas = imagecreatetruecolor(self::FAVICON_SIZE, self::FAVICON_SIZE);

            if ($canvas === false) {
                return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
            }

            // Fully transparent canvas: the logo keeps its own colours.
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefilledrectangle($canvas, 0, 0, self::FAVICON_SIZE, self::FAVICON_SIZE, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

            $scale = min(self::FAVICON_SIZE / $sourceWidth, self::FAVICON_SIZE / $sourceHeight);
            $targetWidth = max(1, (int) round($sourceWidth * $scale));
            $targetHeight = max(1, (int) round($sourceHeight * $scale));

            imagealphablending($canvas, true);

            imagecopyresampled(
                $canvas,
                $image,
                (int) round((self::FAVICON_SIZE - $targetWidth) / 2),
                (int) round((self::FAVICON_SIZE - $targetHeight) / 2),
                0,
                0,
                $targetWidth,
                $targetHeight,
                $sourceWidth,
                $sourceHeight
            );

            ob_start();
            imagepng($canvas, null, 9);
            $png = (string) ob_get_clean();

            imagedestroy($canvas);

            return new OptimizedImage($png, 'png', strlen($binary) - strlen($png), self::FAVICON_SIZE, self::FAVICON_SIZE);
        } finally {
            if (is_object($image)) {
                imagedestroy($image);
            }
        }
    }

    /**
     * Scale a logo down to a sane size, keeping its transparency.
     *
     * The logo is shown in the header at 40-60 px, but it was uploaded at
     * whatever size the operator had on their screen — the one in the
     * database was 36 KB for an image that is rendered at 48 px.
     */
    public static function logo(string $binary, ?string $type = null): OptimizedImage
    {
        $type ??= ImageType::detect($binary);

        if ($type === null || ! function_exists('imagecreatefromstring')) {
            return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
        }

        try {
            if (! imageistruecolor($image) && function_exists('imagepalettetotruecolor')) {
                imagepalettetotruecolor($image);
            }

            $width = imagesx($image);
            $height = imagesy($image);

            if ($width < 1 || $height < 1 || ($width <= self::LOGO_MAX_WIDTH && $height <= self::LOGO_MAX_WIDTH)) {
                return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png', 0, $width, $height);
            }

            $scale = self::LOGO_MAX_WIDTH / max($width, $height);
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));

            $resized = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($resized === false) {
                return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png', 0, $width, $height);
            }

            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;

            ob_start();
            imagepng($image, null, 9);
            $png = (string) ob_get_clean();

            return new OptimizedImage($png, 'png', strlen($binary) - strlen($png), $targetWidth, $targetHeight);
        } finally {
            if (is_object($image)) {
                imagedestroy($image);
            }
        }
    }

    /**
     * Optimise an uploaded image.
     *
     * @param string $binary The raw bytes of the upload.
     * @param string|null $type The detected type (see ImageType::detect).
     */
    public static function optimize(string $binary, ?string $type = null): OptimizedImage
    {
        $type ??= ImageType::detect($binary);

        // Unknown type, or already the best format we store: keep it as-is.
        if ($type === null || $type === ImageType::WEBP) {
            return new OptimizedImage($binary, $type === ImageType::WEBP ? 'webp' : 'bin');
        }

        if (strlen($binary) < self::MIN_BYTES) {
            return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            // GD without WebP support: keep the original rather than
            // degrading the image.
            return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png');
        }

        try {
            // GD cannot encode a palette (indexed-colour) image to WebP.
            // Promoted PNGs are commonly palette images, so this is the
            // normal path, not an edge case.
            if (! imageistruecolor($image) && function_exists('imagepalettetotruecolor')) {
                imagepalettetotruecolor($image);
            }

            $width = imagesx($image);
            $height = imagesy($image);

            if ($width > self::MAX_WIDTH) {
                $newHeight = (int) round($height * (self::MAX_WIDTH / $width));
                $resized = imagecreatetruecolor(self::MAX_WIDTH, $newHeight);

                if ($resized !== false) {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled(
                        $resized,
                        $image,
                        0,
                        0,
                        0,
                        0,
                        self::MAX_WIDTH,
                        $newHeight,
                        $width,
                        $height
                    );
                    imagedestroy($image);
                    $image = $resized;
                    $width = self::MAX_WIDTH;
                    $height = $newHeight;
                }
            }

            ob_start();
            $ok = imagewebp($image, null, self::WEBP_QUALITY);
            $webp = (string) ob_get_clean();

            if ($ok === false || $webp === '' || strlen($webp) >= strlen($binary)) {
                // The re-encode did not help. Keep the original.
                return new OptimizedImage($binary, $type === ImageType::JPEG ? 'jpg' : 'png', 0, $width, $height);
            }

            return new OptimizedImage($webp, 'webp', strlen($binary) - strlen($webp), $width, $height);
        } finally {
            imagedestroy($image);
        }
    }
}
