<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidQuoteSignatureException;
use App\Models\Quote;
use App\Support\Media\ImageType;
use Illuminate\Support\Facades\Storage;

/**
 * Stores the customer signature that arrives as a data URI.
 *
 * The signature used to be written to the public disk with the file
 * extension taken from the MIME type declared inside the data URI, and
 * SVG was explicitly allowed. A data URI is attacker-controlled: it can
 * declare `image/svg+xml` and carry a script, and the file was served
 * straight from the public disk — so opening the file URL ran that
 * script inside the application's own origin. There was also no limit on
 * the size of the payload, so one request could ask the server to decode
 * and store megabytes.
 *
 * Three rules apply now:
 *
 *   1. only raster images are accepted — the signature pad produces PNG,
 *      so SVG is not a feature anybody loses here;
 *   2. the type is decided by the bytes, never by what the request
 *      claims, and the extension written to disk comes from that;
 *   3. the payload is size-capped before it is decoded, and decoded
 *      strictly.
 *
 * The three callers (the public approval, the internal approval and the
 * signature editor) share this service so the rules cannot drift apart.
 */
class QuoteSignatureStorage
{
    /**
     * The largest signature accepted, after decoding. A signature is a
     * few kilobytes; half a megabyte is generous and still bounded.
     */
    public const MAX_BYTES = 512 * 1024;

    /**
     * The declared type of a data URI, mapped to the real type it must
     * have. `image/jpg` is not a real media type but browsers and
     * scripts emit it anyway.
     *
     * @var array<string, string>
     */
    private const TYPE_ALIASES = [
        'png' => 'png',
        'jpg' => 'jpeg',
        'jpeg' => 'jpeg',
        'webp' => 'webp',
    ];

    /**
     * Store the signature of a quote and return its path on the public
     * disk.
     *
     * @throws InvalidQuoteSignatureException
     */
    public function store(string $dataUri, Quote $quote): string
    {
        $binary = $this->decode($dataUri);

        $filename = 'signatures/quotes/'.$quote->id.'_'.time().'.'.$this->extensionFor($binary);

        Storage::disk('public')->put($filename, $binary);

        return $filename;
    }

    /**
     * Decode the data URI into raw bytes, or refuse it.
     *
     * @throws InvalidQuoteSignatureException
     */
    public function decode(string $dataUri): string
    {
        if (! preg_match('#^data:image/([a-z0-9.+-]+);base64,#i', $dataUri, $matches)) {
            throw InvalidQuoteSignatureException::malformed();
        }

        $declared = self::TYPE_ALIASES[strtolower($matches[1])] ?? null;

        if ($declared === null) {
            // SVG lands here as well: it carries active content and the
            // file is served from the public disk.
            throw InvalidQuoteSignatureException::unsupportedType();
        }

        $raw = substr($dataUri, (int) strpos($dataUri, ',') + 1);

        // Cap the payload BEFORE decoding it — otherwise a huge base64
        // string is decoded into memory before it is measured.
        if (strlen($raw) > (int) ceil(self::MAX_BYTES / 3) * 4 + 4) {
            throw InvalidQuoteSignatureException::tooLarge();
        }

        $binary = base64_decode($raw, true);

        if ($binary === false || $binary === '') {
            throw InvalidQuoteSignatureException::malformed();
        }

        if (strlen($binary) > self::MAX_BYTES) {
            throw InvalidQuoteSignatureException::tooLarge();
        }

        $actual = ImageType::detect($binary);

        if ($actual === null) {
            throw InvalidQuoteSignatureException::notAnImage();
        }

        // The declared type is a claim by the request; the bytes decide.
        if ($actual !== $declared) {
            throw InvalidQuoteSignatureException::typeMismatch();
        }

        return $binary;
    }

    /**
     * The extension the bytes are stored under.
     *
     * @throws InvalidQuoteSignatureException
     */
    private function extensionFor(string $binary): string
    {
        $extension = ImageType::extensionFor($binary);

        if ($extension === null) {
            throw InvalidQuoteSignatureException::notAnImage();
        }

        return $extension;
    }
}
