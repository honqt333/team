<?php

declare(strict_types=1);

namespace Tests\Feature;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Guards the Vue components against a translation key that does not exist.
 *
 * WHY THIS IS NOT THE SAME AS I18nCoverageTest
 *
 * `I18nCoverageTest` counts the keys that the checker reports missing
 * and compares the count with a ceiling. That catches a SYSTEM that
 * drifts, but it says nothing about WHICH key is missing, and it
 * cannot tell a screen that was never opened from one that is.
 *
 * This test answers the question a user actually asks: "is there a
 * key on this screen that renders as `system.credits.active_workshops`?"
 *
 * WHY A MISSING KEY IS A VISIBLE DEFECT
 *
 * A key that is missing from BOTH ar.json and en.json does not fall
 * back to anything. vue-i18n renders the key itself, so the screen
 * reads `nav.groups.crm` in the middle of a table. The Arabic user
 * sees a dotted identifier where a label should be, and the English
 * user sees the same.
 *
 * The order of the work matters: a screen was written, it referenced
 * a key, and the key was never added. The screen rendered fine in
 * the editor because nobody looked at it in the browser.
 */
class VueTranslationKeyTest extends TestCase
{
    /**
     * The locale files that hold the keys.
     */
    private const AR_JSON = 'resources/js/i18n/lang/ar.json';

    private const EN_JSON = 'resources/js/i18n/lang/en.json';

    public function test_every_translation_key_used_in_a_vue_component_exists(): void
    {
        $ar = $this->flattenJson(self::AR_JSON);
        $en = $this->flattenJson(self::EN_JSON);

        $missing = [];
        $used = 0;

        foreach ($this->vueComponents() as $path) {
            $source = file_get_contents($path);

            if ($source === false) {
                $this->fail("Could not read {$path}.");
            }

            $relative = str_replace(resource_path('js').'/', '', $path);

            foreach ($this->keysIn($source) as $key) {
                $used++;

                if (! array_key_exists($key, $ar)) {
                    $missing[] = "{$relative} uses {$key} — not in ar.json";

                    continue;
                }

                if (! array_key_exists($key, $en)) {
                    $missing[] = "{$relative} uses {$key} — not in en.json";
                }
            }
        }

        $this->assertGreaterThan(0, $used, 'No translation keys were found. The scanner is broken.');

        $this->assertSame(
            [],
            $missing,
            "These translation keys are used in a component but do not exist in the locale files.\n"
            ."A key missing from BOTH files is not a fallback: vue-i18n renders the key itself,\n"
            ."so the screen shows `system.credits.active_workshops` instead of a label.\n - "
            .implode("\n - ", $missing)
        );
    }

    /**
     * The translation keys a component calls.
     *
     * Both `$t('key')` in a template and `t('key')` in a script count.
     * A key with a `{}` placeholder or a `$` in it is dynamic and is
     * skipped: its shape is decided at runtime.
     *
     * @return array<int, string>
     */
    private function keysIn(string $source): array
    {
        $keys = [];

        // `$t('key')` and `t('key')`.
        //
        // The lookbehind keeps `format(...)` and any other identifier
        // ending in `t` out, and it MUST NOT exclude the `$` of `$t(`:
        // `$t('key')` is the commonest call in a template, and
        // excluding it made the scanner miss most of the file.
        if (preg_match_all('/(?<![A-Za-z0-9_])t\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches) > 0) {
            $keys = array_merge($keys, $matches[1]);
        }

        return array_values(array_filter($keys, static function (string $key): bool {
            // A dynamic key: the shape is decided at runtime.
            if (str_contains($key, '$') || str_contains($key, '{') || str_contains($key, '}')) {
                return false;
            }

            // A CONCATENATED key: `t('customer_portal.dashboard.' + titleKey)`
            // and `t('common.status_' + inv.status)`. The prefix ends
            // with a dot or an underscore and the rest is decided at
            // runtime, so the whole call cannot be resolved here. The
            // scanner would otherwise report `common.status_` as a
            // missing key, which it is not.
            if (str_ends_with($key, '.') || str_ends_with($key, '_')) {
                return false;
            }

            // A bilingual model attribute (`title_ar`, `name_en`) is a
            // database column, not a translation key.
            if (preg_match('/_(ar|en)$/', $key) === 1) {
                return false;
            }

            return $key !== '';
        }));
    }

    /**
     * Every `.vue` file under resources/js.
     *
     * @return array<int, string>
     */
    private function vueComponents(): array
    {
        $root = resource_path('js');
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'vue') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * The leaf keys of a locale file, as dotted paths.
     *
     * @return array<string, string>
     */
    private function flattenJson(string $relativePath): array
    {
        $path = base_path($relativePath);
        $source = @file_get_contents($path);

        if ($source === false) {
            $this->fail("Could not read {$relativePath}.");
        }

        $decoded = json_decode($source, true);

        if (! is_array($decoded)) {
            $this->fail("{$relativePath} is not valid JSON.");
        }

        return $this->flatten($decoded);
    }

    /**
     * @param array<mixed> $data
     * @return array<string, string>
     */
    private function flatten(array $data, string $prefix = ''): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value) && $value !== [] && ! array_is_list($value)) {
                $out += $this->flatten($value, $path);

                continue;
            }

            $out[$path] = is_string($value) ? $value : '';
        }

        return $out;
    }
}
