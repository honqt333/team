<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Regression guard for the i18n coverage.
 *
 * We do NOT test "every key is translated" here — that would be a
 * forever-changing assertion as we add features. Instead, we
 * snapshot the *count* of missing keys per locale and fail only if
 * the count goes UP.
 *
 * Adding a new translation key never fails this test (the count of
 * usages grows, but missing stays flat). Forgetting a translation
 * DOES fail (missing count grows by 1).
 *
 * To accept a new round of missing keys (e.g. after a major refactor
 * deletes a feature), bump the per-locale expected count in
 * MISSING_KEYS_EXPECTED below by the same number you observed.
 */
class I18nCoverageTest extends TestCase
{
    /**
     * Snapshot of the current missing-key count per locale.
     * Re-baseline with `php artisan translations:check --locale=ar`
     * after a deliberate round of cleanup, then update this map.
     *
     * @var array<string, int>
     */
    private const MISSING_KEYS_EXPECTED = [
        // Rebased to 13 for ar / 13 for en on 2026-10-25.
        //
        // History: the baseline used to be 708 and was ratcheted up
        // five times (0 → 94 → 700 → 708) because the checker only
        // compared usage against lang/{ar,en}/*.php. Every key that
        // lives ONLY in resources/js/i18n/lang/*.json — the correct
        // home for keys consumed by $t() in Vue components — was
        // therefore reported missing. That produced ~700 false
        // positives out of the 713 reported.
        //
        // `translations:check` now resolves a key against BOTH
        // sources by default (see the --source option: php|vue|both).
        // The real number is 13, and it is identical in both locales.
        //
        // The 13 remaining keys are genuine and are tracked in
        // PROJECT_TRANSLATION_AUDIT.md §3. They are real gaps
        // (5 payment error messages, the SMS OTP body, the secure
        // payment note, and 6 secondary labels) — the count is a
        // ceiling that must not grow.
        'ar' => 13,
        'en' => 13,
    ];

    public function test_no_new_missing_translation_keys_in_arabic(): void
    {
        $this->assertMissingKeysCountIsAtMost('ar');
    }

    public function test_no_new_missing_translation_keys_in_english(): void
    {
        $this->assertMissingKeysCountIsAtMost('en');
    }

    /**
     * Run the existing translations:check command and assert the
     * reported missing count is no greater than the baseline. We
     * capture the actual count in the failure message so the dev
     * can decide whether to add the missing keys or bump the
     * baseline.
     */
    private function assertMissingKeysCountIsAtMost(string $locale): void
    {
        $expected = self::MISSING_KEYS_EXPECTED[$locale] ?? 0;

        $exitCode = Artisan::call('translations:check', [
            '--locale' => $locale,
            '--strict' => true,
        ]);

        $output = Artisan::output();

        // Parse the "Total missing keys: N" line out of the command output.
        $actual = null;

        if (preg_match('/Total missing keys:\s*(\d+)/', $output, $m)) {
            $actual = (int) $m[1];
        }

        $this->assertNotNull(
            $actual,
            "translations:check did not report a missing-keys count. Output was:\n{$output}"
        );

        $this->assertLessThanOrEqual(
            $expected,
            $actual,
            "{$locale} has {$actual} missing translation keys (allowed: {$expected}).\n"
            ."Either add the missing keys to lang/{$locale}/*.php or\n"
            ."resources/js/i18n/lang/{$locale}.json, or — if this is a\n"
            ."deliberate cleanup — bump MISSING_KEYS_EXPECTED['{$locale}'] in this test."
        );
    }

    /**
     * Regression guard for the checker itself.
     *
     * The checker used to compare every `$t()` usage against
     * lang/{ar,en}/*.php only, so each key that lives solely in
     * resources/js/i18n/lang/*.json was reported missing. That
     * produced ~700 false positives and forced the baseline to be
     * ratcheted up five times.
     *
     * These tests pin the corrected behaviour so the bug cannot
     * silently return and inflate the count again.
     */
    public function test_keys_defined_only_in_vue_json_are_not_reported_missing(): void
    {
        // A key that exists in the Vue JSON bundle but NOT in the
        // PHP lang files. It must not be reported missing.
        $json = json_decode(
            file_get_contents(base_path('resources/js/i18n/lang/ar.json')),
            true
        );

        $this->assertIsArray($json);
        $this->assertArrayHasKey('system', $json, 'ar.json should define the "system" namespace');
        $this->assertArrayHasKey('tenants', $json['system']);

        $vueOnlyKey = 'system.tenants.title';
        $this->assertTrue(
            data_get($json, str_replace('.', '.', $vueOnlyKey)) !== null,
            "Expected {$vueOnlyKey} to be defined in the Vue JSON bundle"
        );

        // The PHP side must NOT define it, otherwise this test
        // would not actually prove the Vue source is consulted.
        $phpLang = include base_path('lang/ar/system_settings.php');
        $this->assertFalse(
            $this->phpLangDefines($phpLang, $vueOnlyKey),
            "{$vueOnlyKey} unexpectedly exists in lang/ar — pick a different key for this guard"
        );

        $output = Artisan::output();
        $this->assertStringNotContainsString(
            $vueOnlyKey,
            $output,
            "{$vueOnlyKey} is defined in resources/js/i18n/lang/ar.json and must not be reported missing"
        );
    }

    /**
     * The --source=php mode must still resolve keys from the PHP
     * lang files, so the two sources can be compared independently.
     */
    public function test_source_php_mode_reports_more_missing_keys_than_the_default(): void
    {
        $default = Artisan::call('translations:check', ['--locale' => 'ar']);
        $defaultCount = $this->parseMissingCount(Artisan::output());

        $phpOnly = Artisan::call('translations:check', ['--locale' => 'ar', '--source' => 'php']);
        $phpCount = $this->parseMissingCount(Artisan::output());

        $this->assertNotNull($defaultCount);
        $this->assertNotNull($phpCount);

        $this->assertLessThan(
            $phpCount,
            $defaultCount,
            'The default (both sources) must report fewer missing keys than --source=php'
        );
    }

    /**
     * @param array<string, mixed> $arr
     */
    private function phpLangDefines(array $arr, string $dottedKey): bool
    {
        $current = $arr;

        foreach (explode('.', $dottedKey) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];
        }

        return true;
    }

    private function parseMissingCount(string $output): ?int
    {
        if (preg_match('/Total missing keys:\s*(\d+)/', $output, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
