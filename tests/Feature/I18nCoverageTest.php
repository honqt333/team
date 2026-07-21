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
        'ar' => 0,
        'en' => 0,
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
            ."Either add the missing keys to lang/{$locale}/*.php, or — if this is a\n"
            ."deliberate cleanup — bump MISSING_KEYS_EXPECTED['{$locale}'] in this test."
        );
    }
}
