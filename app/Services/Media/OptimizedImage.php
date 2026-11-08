<?php

declare(strict_types=1);

namespace App\Services\Media;

/**
 * The outcome of an image optimisation attempt.
 *
 * @see ImageOptimizer::optimize()
 */
final readonly class OptimizedImage
{
    public function __construct(
        /** The bytes to store (the original, or the re-encoded version). */
        public string $binary,
        /** The extension the bytes must be stored under. */
        public string $extension,
        /** How many bytes the optimisation saved (0 when nothing changed). */
        public int $savedBytes = 0,
        /** The width of the stored image, when known. */
        public ?int $width = null,
        /** The height of the stored image, when known. */
        public ?int $height = null,
    ) {}

    /**
     * Whether the image was actually re-encoded.
     */
    public function wasReencoded(): bool
    {
        return $this->savedBytes > 0;
    }
}
