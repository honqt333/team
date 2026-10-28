<?php

declare(strict_types=1);

namespace App\Support\Media;

/**
 * Reads the real type of an image from its bytes.
 *
 * The extension of an uploaded file is chosen by the client, so it can
 * never be used to decide what is written to disk. The magic numbers at
 * the start of the bytes are what actually decide the type — and they
 * decide the extension too.
 *
 * Only raster types are recognised. SVG is deliberately absent: it is an
 * XML document that can carry scripts, and the files handled here are
 * served straight from a web-served disk, so opening the file URL would
 * run that script inside the application's own origin.
 */
final class ImageType
{
    public const PNG = 'png';

    public const JPEG = 'jpeg';

    public const WEBP = 'webp';

    /**
     * The extension each accepted type is stored under.
     *
     * @var array<string, string>
     */
    private const EXTENSIONS = [
        self::PNG => 'png',
        self::JPEG => 'jpg',
        self::WEBP => 'webp',
    ];

    /**
     * The real type of the bytes, or null when they are not an image of
     * an accepted type.
     */
    public static function detect(string $binary): ?string
    {
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return self::PNG;
        }

        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return self::JPEG;
        }

        if (str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
            return self::WEBP;
        }

        return null;
    }

    /**
     * The extension the bytes are stored under, or null when they are
     * not an accepted image.
     */
    public static function extensionFor(string $binary): ?string
    {
        $type = self::detect($binary);

        return $type === null ? null : self::EXTENSIONS[$type];
    }
}
