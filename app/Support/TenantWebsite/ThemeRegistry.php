<?php

declare(strict_types=1);

namespace App\Support\TenantWebsite;

use Illuminate\Support\Str;

/**
 * Central allow-list for public-site theme keys.
 *
 * Why this exists
 * ---------------
 * A tenant website can be configured to render in one of a small,
 * known set of layouts. We store the layout choice as a plain
 * `theme_key` string in the database, but the public site must
 * never trust that string to be one we actually support — a stale
 * row, a database tampering, or a future theme that was retired
 * must NOT cause a 500 on the public page.
 *
 * The contract is:
 *   1. {@see allowedKeys()} is the source of truth for what we ship.
 *   2. {@see normalize()} coerces ANY input to a known key (default
 *      fallback if the key is missing/retired/injected).
 *   3. {@see label()} returns the human label, which the Vue admin
 *      form uses to render the dropdown — keeping the i18n for
 *      theme names in one place.
 *
 * Adding a new theme
 * ------------------
 * - Add the key to {@see allowedKeys()}.
 * - Add a matching layout in resources/js/Pages/Public/Site/themes/
 *   (the public site picks the component by key).
 * - Add a label entry in {@see labels()}.
 *
 * Removing a theme: just drop the key from {@see allowedKeys()}.
 * Existing rows with the retired key will silently fall back to
 * 'default' on next read — no migration required.
 */
class ThemeRegistry
{
    public const DEFAULT_THEME = 'default';

    /**
     * The set of theme keys we currently ship. The list is
     * intentionally short for Phase 6: only "default" is
     * implemented. Other entries here are reserved slots so the
     * admin UI can show the option (and we don't need to ship
     * extra Vue components to demonstrate the allow-list
     * enforcement path in tests).
     *
     * @var array<int, string>
     */
    public static function allowedKeys(): array
    {
        return [
            self::DEFAULT_THEME,
        ];
    }

    /**
     * Coerce a value to a known theme key.
     *
     * Order of checks:
     *   1. non-string → default (defensive — never trust payload type)
     *   2. not in allow-list → default
     *   3. otherwise → trim + return as-is
     *
     * We do NOT lowercase: keys are case-sensitive on purpose
     * (avoids accidental aliasing when we eventually add
     * `premium_dark` vs `premium_Dark`).
     */
    public static function normalize(mixed $value): string
    {
        if (! is_string($value)) {
            return self::DEFAULT_THEME;
        }

        $trimmed = trim($value);

        if ($trimmed === '' || ! in_array($trimmed, self::allowedKeys(), true)) {
            return self::DEFAULT_THEME;
        }

        return $trimmed;
    }

    /**
     * Human-readable label for a key. Used by the admin form
     * dropdown. Keys without a label fall back to a Title-Case
     * version of the key — never the raw key in the UI.
     *
     * @return array<string, array{ar: string, en: string}>
     */
    public static function labels(): array
    {
        return [
            self::DEFAULT_THEME => [
                'ar' => 'الافتراضي',
                'en' => 'Default',
            ],
        ];
    }

    /**
     * Convenience for the admin form: build a list of options
     * [{value, label_ar, label_en}] for a <select>.
     *
     * @return array<int, array{value: string, label_ar: string, label_en: string}>
     */
    public static function optionsForSelect(): array
    {
        $out = [];

        foreach (self::allowedKeys() as $key) {
            $label = self::labels()[$key] ?? null;
            $out[] = [
                'value' => $key,
                'label_ar' => $label['ar'] ?? Str::headline($key),
                'label_en' => $label['en'] ?? Str::headline($key),
            ];
        }

        return $out;
    }
}
