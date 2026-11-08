<?php

declare(strict_types=1);

namespace Tests\Feature;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Guards the Vue components against a translation call that cannot run.
 *
 * In a `<script setup>` block there is no `$t`. The `$t` in a template
 * is injected by the compiler; in a script the only way to reach a
 * translation is `useI18n()`:
 *
 *     import { useI18n } from 'vue-i18n';
 *     const { t } = useI18n();
 *
 * A component that calls `t('key')` in its script without that
 * binding throws on load, and the page is blank. A component that
 * calls `$t('key')` in its script throws the same way, because `$t`
 * does not exist outside a template expression.
 *
 * This is not a hypothetical: the migration of the system panel wrote
 * `$t()` into the script block of two receipt pages, and the two
 * pages crashed on open.
 *
 * The test walks every `<script>` block of every component and checks
 * that a translation call in the script has a binding to back it.
 */
class VueI18nBindingTest extends TestCase
{
    public function test_a_script_block_that_calls_the_translator_has_a_binding(): void
    {
        $root = resource_path('js');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        $problems = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'vue') {
                continue;
            }

            $path = $file->getPathname();
            $source = file_get_contents($path);

            if ($source === false) {
                $this->fail("Could not read {$path}.");
            }

            $relative = substr($path, strlen($root) + 1);

            foreach ($this->scriptBlocks($source) as $block) {
                if ($this->hasBinding($block)) {
                    continue;
                }

                if (preg_match('/(?<![A-Za-z0-9_$])t\(/', $block) === 1) {
                    $problems[] = "{$relative} calls t() in a script block without a useI18n() binding";
                }

                if (preg_match('/(?<![A-Za-z0-9_$])\$t\(/', $block) === 1) {
                    $problems[] = "{$relative} calls \$t() in a script block; \$t exists only in a template";
                }
            }
        }

        $this->assertSame(
            [],
            $problems,
            "A translation call in a script block has no binding to back it:\n - "
            .implode("\n - ", $problems)
            ."\n\nAdd to the component:\n"
            ."    import { useI18n } from 'vue-i18n';\n"
            .'    const { t } = useI18n();'
        );
    }

    /**
     * The contents of every `<script>` block of a component.
     *
     * @return array<int, string>
     */
    private function scriptBlocks(string $source): array
    {
        preg_match_all('/<script\b[^>]*>(.*?)<\/script>/s', $source, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Does this script block have a `t` to call?
     *
     * Either the composable is destructured as `t`, or it is aliased
     * (`const { t: $t } = useI18n()`), or the whole instance is bound
     * under a name that is then called.
     */
    private function hasBinding(string $block): bool
    {
        // `const { t } = useI18n()` or `const { t: $t } = useI18n()`
        if (preg_match('/const\s*\{[^}]*\bt\b[^}]*\}\s*=\s*useI18n\(\)/', $block) === 1) {
            return true;
        }

        // `const { t: $t } = useI18n()` — the alias is `$t`, so the
        // call has to be `$t(`.
        if (preg_match('/const\s*\{\s*t\s*:\s*(\$?\w+)\s*\}\s*=\s*useI18n\(\)/', $block, $m) === 1) {
            return str_contains($block, $m[1].'(');
        }

        // `const i18n = useI18n()` — the instance is bound, so a call
        // through it is fine.
        return preg_match('/const\s+\{?\s*\w+\s*\}?\s*=\s*useI18n\(\)/', $block) === 1;
    }
}
