<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * The customer signature that arrived with the request is not an image
 * the system is willing to store.
 *
 * The signature is written to the public disk and served straight from
 * there, so the accepted content has to be inert: raster images only,
 * with the type taken from the bytes themselves.
 */
class InvalidQuoteSignatureException extends RuntimeException
{
    public static function unsupportedType(): self
    {
        return new self('unsupported_signature_type');
    }

    public static function notAnImage(): self
    {
        return new self('signature_not_an_image');
    }

    public static function typeMismatch(): self
    {
        return new self('signature_type_mismatch');
    }

    public static function tooLarge(): self
    {
        return new self('signature_too_large');
    }

    public static function malformed(): self
    {
        return new self('signature_malformed');
    }
}
