<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Error-code translation guard.
 *
 * WHAT IT CATCHES
 * ---------------
 * The API answers a failed action with a machine code:
 *
 *     { "code": "cross_tenant_or_center", "error": "…" }
 *
 * and the staff UI renders it with a DYNAMIC key:
 *
 *     alert(t(`messages.${e.response.data.code}`))
 *
 * A dynamic key cannot be found by a scanner that looks for literal
 * `t('…')` calls — so when the key is missing from the locale files,
 * vue-i18n renders the KEY ITSELF on the screen:
 *
 *     messages.cross_tenant_or_center: cross_tenant_or_center
 *
 * Thirteen of them were missing. The guard takes the codes from the
 * source — every `RuntimeException('…')` and every `'code' => '…'` — and
 * checks that each one exists in BOTH locale files.
 *
 * WHY "BOTH"
 * ----------
 * A key missing from one file falls back to the other language, which is
 * a (small) bug. A key missing from BOTH is rendered as the key — the
 * user reads `messages.slot_no_longer_available` instead of a sentence.
 */
class ErrorCodeTranslationTest extends TestCase
{
    /**
     * Every error code the application can hand to the UI.
     *
     * @return array<int, string>
     */
    private function errorCodes(): array
    {
        $all = [];

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = File::get($file->getPathname());

            // throw new RuntimeException('cross_tenant_or_center');
            preg_match_all("/RuntimeException\(\s*'([a-z_]+)'/", $source, $throws);

            // 'code' => 'cross_tenant_or_center',
            preg_match_all("/'code'\s*=>\s*'([a-z_]+)'/", $source, $codeMatches);

            // 'reason' => 'center_unavailable',
            preg_match_all("/'(?:reason|error_code)'\s*=>\s*'([a-z_]+)'/", $source, $reasons);

            foreach ([$throws, $codeMatches, $reasons] as $set) {
                foreach ($set[1] ?? [] as $code) {
                    $all[$code] = true;
                }
            }
        }

        $all = array_keys($all);

        sort($all);

        return $all;
    }

    public function test_every_error_code_the_api_returns_has_a_translation(): void
    {
        $codes = $this->errorCodes();

        $this->assertNotEmpty($codes, 'No error codes were found — the scanner is broken.');

        $missing = [];

        foreach (['ar', 'en'] as $locale) {
            $file = base_path("resources/js/i18n/lang/{$locale}.json");
            $this->assertFileExists($file);

            $translations = json_decode(File::get($file), true);
            $this->assertIsArray($translations);

            $messages = $translations['messages'] ?? [];

            foreach ($codes as $code) {
                if (! array_key_exists($code, $messages) || trim((string) $messages[$code]) === '') {
                    $missing[] = "{$locale}: messages.{$code}";
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These error codes are returned by the API but have no translation, so the UI\n"
            ."renders the key itself (vue-i18n does not fall back when a key is missing from\n"
            ."BOTH locale files).\n  ".implode("\n  ", $missing)
        );
    }

    public function test_the_php_locale_files_carry_the_same_codes(): void
    {
        $codes = $this->errorCodes();

        $missing = [];

        foreach (['ar', 'en'] as $locale) {
            $messages = require base_path("lang/{$locale}/messages.php");

            foreach ($codes as $code) {
                if (! array_key_exists($code, $messages)) {
                    $missing[] = "{$locale}: messages.{$code}";
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "The server-side locale files must carry the same codes as the UI.\n  "
            .implode("\n  ", $missing)
        );
    }
}
