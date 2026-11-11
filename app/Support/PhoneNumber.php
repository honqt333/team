<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

/**
 * Shared phone-number normalization across supported countries.
 */
class PhoneNumber
{
    /**
     * Resolve the active ISO country code from explicit argument, active tenant, or default ('SA').
     */
    public static function resolveCountry(?string $country = null): string
    {
        if ($country !== null && trim($country) !== '') {
            return CountryCurrencyRegistry::normalizeCountry($country);
        }

        try {
            if (function_exists('app') && app()->bound('auth') && auth()->hasUser()) {
                $tenantCountry = auth()->user()?->tenant?->country;

                if (is_string($tenantCountry) && trim($tenantCountry) !== '') {
                    return CountryCurrencyRegistry::normalizeCountry($tenantCountry);
                }
            }
        } catch (Throwable) {
            // Pure unit contexts without Laravel container fall back cleanly.
        }

        return CountryCurrencyRegistry::DEFAULT_COUNTRY;
    }

    /**
     * Normalize to canonical E.164 international format (+<country><number>) when possible.
     * Supports SA, GCC, EG, JO local formats using the provided or tenant default country,
     * as well as any international E.164 (+...) or 00-prefixed number.
     */
    public static function normalize(?string $number, ?string $defaultCountry = null): ?string
    {
        if ($number === null) {
            return null;
        }

        $number = trim($number);

        if ($number === '') {
            return null;
        }

        // Convert Arabic/Persian digits to English.
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $number = str_replace($arabic, $english, $number);
        $number = str_replace($persian, $english, $number);

        // Remove all non-numeric characters except a leading +.
        if (str_starts_with($number, '+')) {
            $number = '+'.preg_replace('/[^\d]/', '', substr($number, 1));
        } else {
            $number = preg_replace('/[^\d]/', '', $number);
        }

        if ($number === '' || $number === '+') {
            return null;
        }

        if (str_starts_with($number, '+')) {
            return $number;
        }

        if (str_starts_with($number, '00') && strlen($number) >= 7) {
            return '+'.substr($number, 2);
        }

        $countries = CountryCurrencyRegistry::allCountries();
        uasort(
            $countries,
            static fn (array $a, array $b): int => strlen((string) $b['dial_digits']) <=> strlen((string) $a['dial_digits'])
        );

        foreach ($countries as $meta) {
            $dialDigits = (string) $meta['dial_digits'];

            if (! str_starts_with($number, $dialDigits)) {
                continue;
            }

            $e164Regex = (string) ($meta['e164_regex'] ?? '');

            if ($e164Regex !== '' && preg_match($e164Regex, '+'.$number)) {
                return '+'.$number;
            }

            if ($dialDigits === '966' && strlen($number) >= 10) {
                return '+'.$number;
            }
        }

        $resolvedCountry = self::resolveCountry($defaultCountry);
        $countryMeta = CountryCurrencyRegistry::countryMeta($resolvedCountry);
        $dialCode = (string) ($countryMeta['dial_code'] ?? '+966');
        $localLengths = (array) ($countryMeta['local_lengths'] ?? [9]);
        $localRegex = (string) ($countryMeta['local_regex'] ?? '/^5\d{8}$/');

        if (str_starts_with($number, '0')) {
            $stripped = substr($number, 1);

            if (in_array(strlen($stripped), $localLengths, true) && preg_match($localRegex, $stripped)) {
                return $dialCode.$stripped;
            }
        }

        if (in_array(strlen($number), $localLengths, true) && preg_match($localRegex, $number)) {
            return $dialCode.$number;
        }

        return $number;
    }

    /**
     * Validate canonical E.164 format (+ followed by 7 to 15 digits).
     */
    public static function isValidE164(?string $number): bool
    {
        return is_string($number) && preg_match('/^\+[1-9]\d{6,14}$/', $number) === 1;
    }

    /**
     * The "lookup key" for matching: digits only, no leading +.
     * Two phone numbers normalize to the same key if they refer
     * to the same line, regardless of formatting.
     */
    public static function lookupKey(?string $number, ?string $defaultCountry = null): ?string
    {
        $normalized = self::normalize($number, $defaultCountry);

        if ($normalized === null) {
            return null;
        }

        // Drop the leading +; lookup is digit-only.
        return ltrim($normalized, '+');
    }

    /**
     * A non-reversible identifier for a phone number, safe to write
     * to the application log.
     *
     * WHY NOT LOG THE NUMBER ITSELF
     *
     * The application log is readable by whoever holds the log file
     * or a log backup, which is a much wider group than the people
     * who may see customer contact details. A plain phone number in
     * the log is a copy of the personal data outside its intended
     * store.
     *
     * A fingerprint keeps the operational value of the log line —
     * attempts for the same number can still be correlated — without
     * carrying the number. The salt is the application key, so the
     * fingerprint cannot be reversed by brute force against the small
     * space of real phone numbers.
     */
    public static function fingerprint(?string $number): ?string
    {
        $key = self::lookupKey($number);

        if ($key === null || $key === '') {
            return null;
        }

        $salt = config('app.key');

        if (! is_string($salt) || $salt === '') {
            // Without an application key we cannot salt the digest.
            // Fall back to an unsalted hash rather than the number.
            $salt = 'phone-fingerprint';
        }

        return 'ph_'.substr(hash_hmac('sha256', $key, $salt), 0, 12);
    }
}
