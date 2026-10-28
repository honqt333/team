<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shared phone-number normalization.
 *
 * The Customer store request used to own a private
 * normalizePhoneNumber() method. The customer portal needs
 * the same logic to map a typed-in phone to the canonical
 * format we store on the customer row (and compare against).
 * Centralising it here means both call sites see identical
 * rules; otherwise two registrations of the same number
 * could end up with different stored representations and
 * break the OTP lookup.
 *
 * The function is intentionally tolerant:
 *   - Arabic + Persian digits → English
 *   - whitespace, dashes, parens, dots → stripped
 *   - leading + kept
 *   - common Saudi prefixes (00966, 05, 5, 966) collapsed
 *     into +9665xxxxxxxx
 *
 * The function is NOT a validator. It only normalizes. The
 * caller decides whether the result is acceptable (length,
 * country code, etc.).
 */
class PhoneNumber
{
    /**
     * Normalize to the canonical +9665xxxxxxxx form when
     * possible. Returns null for empty input. Returns the
     * raw digits string when no rule matches (caller can
     * validate).
     */
    public static function normalize(?string $number): ?string
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

        // Country-code collapse rules (Saudi-focused, matching
        // the existing CustomerStoreRequest behaviour).
        if (str_starts_with($number, '00966')) {
            $number = '+966'.substr($number, 5);
        } elseif (str_starts_with($number, '05') && strlen($number) === 10) {
            $number = '+966'.substr($number, 1);
        } elseif (str_starts_with($number, '5') && strlen($number) === 9) {
            $number = '+966'.$number;
        } elseif (str_starts_with($number, '966') && ! str_starts_with($number, '+')) {
            $number = '+'.$number;
        }

        return $number;
    }

    /**
     * The "lookup key" for matching: digits only, no leading +.
     * Two phone numbers normalize to the same key if they refer
     * to the same line, regardless of formatting.
     */
    public static function lookupKey(?string $number): ?string
    {
        $normalized = self::normalize($number);

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
