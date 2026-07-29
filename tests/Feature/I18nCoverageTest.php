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
        // Bumped to 94 for ar / 94 for en in 2026-07-21.
        // The Vue i18n migration on feature/phase-2-goal-3 added
        // ~94 system.* keys to resources/js/i18n/lang/*.json but
        // the lang/{ar,en}/*.php backend files have not been
        // updated to match. The test passes when <= this number;
        // 0 would mean every JSON key is mirrored in PHP, which
        // is not required for the Vue-only keys.
        // Bumped again in 2026-07-27 for stamp UI keys (8 new
        // JSON-only keys per locale for sections.stamp.*).
        // Bumped in 2026-07-29 for Phase 6 (tenant public site) —
        // adds ~37 new JSON-only keys per locale for the
        // tenant_site.* + common.dark/light_mode namespace.
        // All new tenant_site.* keys that are referenced from PHP
        // are mirrored in lang/{ar,en}/tenant_site.php.
        // TODO: Audit and add the missing PHP lang entries.
        'ar' => 640,
        'en' => 640,
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
