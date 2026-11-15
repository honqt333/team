<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Validates and normalizes tenant custom domains without performing live DNS lookups.
 */
final class CustomDomainValidator
{
    private const MAX_ASCII_LENGTH = 253;

    private const FQDN_PATTERN = '/\A(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9]{1,59})\z/';

    /**
     * Platform domains (and their subdomains) that tenants must never claim.
     *
     * @var list<string>
     */
    private const RESERVED_DOMAINS = [
        'khidmh.pro',
        'khidmah.pro',
        'localhost',
        'localhost.localdomain',
    ];

    /**
     * Validate and normalize a raw custom domain input.
     *
     * Returns null for empty/null input, or the normalized ASCII hostname.
     *
     * @throws InvalidArgumentException when the input is malformed or reserved
     */
    public static function normalizeAndValidate(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        // Reject protocols, paths, ports, query strings, fragments, userinfo, and whitespace.
        if (
            preg_match('/\s/', $trimmed) === 1
            || str_contains($trimmed, '://')
            || str_starts_with($trimmed, '//')
            || str_contains($trimmed, '/')
            || str_contains($trimmed, '?')
            || str_contains($trimmed, '#')
            || str_contains($trimmed, ':')
            || str_contains($trimmed, '@')
        ) {
            throw new InvalidArgumentException('Domain must be a bare hostname without protocol, port, path, or query string.');
        }

        $normalized = mb_strtolower($trimmed, 'UTF-8');
        $normalized = rtrim($normalized, '.');

        if ($normalized === '') {
            throw new InvalidArgumentException('Domain cannot be empty.');
        }

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($normalized, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if ($ascii === false) {
                throw new InvalidArgumentException('Invalid internationalized domain name.');
            }

            $normalized = strtolower($ascii);
        }

        if (strlen($normalized) > self::MAX_ASCII_LENGTH) {
            throw new InvalidArgumentException('Domain exceeds maximum hostname length.');
        }

        if (filter_var($normalized, FILTER_VALIDATE_IP) !== false) {
            throw new InvalidArgumentException('IP addresses are not allowed as custom domains.');
        }

        if (preg_match(self::FQDN_PATTERN, $normalized) !== 1) {
            throw new InvalidArgumentException('Domain is not a valid fully-qualified domain name.');
        }

        if (self::isReservedDomain($normalized)) {
            throw new InvalidArgumentException('This domain is reserved by the platform.');
        }

        return $normalized;
    }

    public static function isReservedDomain(string $asciiDomain): bool
    {
        $reserved = self::RESERVED_DOMAINS;

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($appHost) && $appHost !== '') {
            $appHost = strtolower($appHost);

            if (str_starts_with($appHost, 'www.')) {
                $appHost = substr($appHost, 4);
            }

            $reserved[] = $appHost;
        }

        foreach (array_unique($reserved) as $candidate) {
            if ($asciiDomain === $candidate || str_ends_with($asciiDomain, '.'.$candidate)) {
                return true;
            }
        }

        return false;
    }
}
