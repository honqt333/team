<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Media\ImageOptimizer;
use App\Support\Media\ImageType;
use Tests\TestCase;

/**
 * ImageOptimizer.
 *
 * The service exists because a tenant's promotional banner was stored as a
 * 422 KB PNG and served to every visitor of their public site — for an
 * image that renders behind a gradient overlay at 25% opacity.
 *
 * These tests pin the behaviour that matters:
 *   1. a large PNG/JPEG becomes a much smaller WebP,
 *   2. the palette-image case (which GD cannot encode to WebP directly) is
 *      handled — promoted PNGs are commonly palette images, so this is the
 *      NORMAL path, not an edge case,
 *   3. a small image is left alone (re-encoding it would make it bigger),
 *   4. an already-WebP image is never touched,
 *   5. a non-image is never accepted.
 */
class ImageOptimizerTest extends TestCase
{
    /**
     * Build a real PNG in memory.
     *
     * @param bool $palette Produce an indexed-colour (palette) PNG, which
     *                      is what an image with few colours compresses
     *                      to and what GD cannot encode to WebP
     *                      without converting it first.
     */
    private function makePng(int $width = 400, int $height = 300, bool $palette = false): string
    {
        $image = imagecreatetruecolor($width, $height);

        // A noisy gradient gives the encoder real work to do, so the
        // compressed result is genuinely smaller than the raw pixels.
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $c = imagecolorallocate(
                    $image,
                    (int) round(($x * 255) / $width),
                    (int) round(($y * 255) / $height),
                    (int) round((($x + $y) * 255) / ($width + $height))
                );
                imagesetpixel($image, $x, $y, $c);
            }
        }

        if ($palette) {
            imagetruecolortopalette($image, false, 64);
        }

        ob_start();
        imagepng($image, null, 0);
        $binary = (string) ob_get_clean();

        imagedestroy($image);

        return $binary;
    }

    public function test_large_png_is_converted_to_a_smaller_webp(): void
    {
        $png = $this->makePng(1200, 800);
        $result = ImageOptimizer::optimize($png, ImageType::detect($png));

        $this->assertSame('webp', $result->extension);
        $this->assertTrue($result->wasReencoded());
        $this->assertGreaterThan(0, $result->savedBytes);
        $this->assertLessThan(strlen($png), strlen($result->binary));

        // The bytes must really be a WebP.
        $this->assertSame(ImageType::WEBP, ImageType::detect($result->binary));
    }

    public function test_palette_png_is_converted(): void
    {
        // GD cannot encode a palette image to WebP directly — it warns and
        // writes nothing. The service converts it to truecolor first.
        $png = $this->makePng(800, 600, palette: true);
        $result = ImageOptimizer::optimize($png, ImageType::detect($png));

        $this->assertSame('webp', $result->extension);
        $this->assertSame(ImageType::WEBP, ImageType::detect($result->binary));
        $this->assertGreaterThan(0, $result->savedBytes);
    }

    public function test_small_image_is_left_untouched(): void
    {
        // A tiny icon re-encoded as WebP is often BIGGER, so it must be
        // left alone rather than made worse.
        $png = $this->makePng(8, 8);
        $result = ImageOptimizer::optimize($png, ImageType::detect($png));

        $this->assertSame('png', $result->extension);
        $this->assertSame(0, $result->savedBytes);
        $this->assertSame($png, $result->binary);
    }

    public function test_webp_input_is_never_re_encoded(): void
    {
        $png = $this->makePng(1200, 800);
        $webp = ImageOptimizer::optimize($png, ImageType::detect($png))->binary;

        $result = ImageOptimizer::optimize($webp, ImageType::detect($webp));

        $this->assertSame('webp', $result->extension);
        $this->assertSame(0, $result->savedBytes);
        $this->assertSame($webp, $result->binary);
    }

    public function test_non_image_is_rejected(): void
    {
        $result = ImageOptimizer::optimize('not an image at all', ImageType::detect('not an image at all'));

        $this->assertSame(0, $result->savedBytes);
        $this->assertSame('not an image at all', $result->binary);
    }

    public function test_wide_image_is_scaled_down_to_the_max_width(): void
    {
        $png = $this->makePng(2400, 1200);
        $result = ImageOptimizer::optimize($png, ImageType::detect($png));

        $this->assertSame('webp', $result->extension);
        $this->assertSame(ImageOptimizer::MAX_WIDTH, $result->width);
        $this->assertSame(
            (int) round(1200 * (ImageOptimizer::MAX_WIDTH / 2400)),
            $result->height
        );
    }
}
