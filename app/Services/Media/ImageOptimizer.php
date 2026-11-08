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
            if (is_object($image)) {
                imagedestroy($image);
            }
        }
    }
}
