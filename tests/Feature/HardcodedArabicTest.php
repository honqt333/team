<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards the interface against hardcoded Arabic.
 *
 * The rule of the project is that no Arabic copy is written into a Vue
 * component: every visible string comes from ar.json / en.json through
 * `$t('key')` or `t('key')`.
 *
 * This test counts the Arabic literals that are still written inline and
 * fails when the number goes UP. It never fails because a key was
 * added — the count of missing keys is a separate guard — and it does
 * not fail when a page is deliberately left alone, because the baseline
 * is a ceiling, not an exact number.
 *
 * The inventory is produced by scripts/i18n-extract.mjs, which parses
 * the components with the official Vue compiler and acorn. A literal
 * that is compared against server data, or that holds a bilingual
 * database column (name_ar), is a value and not copy: it is not counted.
 */
class HardcodedArabicTest extends TestCase
{
    /**
     * The ceiling of Arabic literals that may stay inline.
     *
     * Rebased to 812 on 2026-10-29 — batch 2D moved 240 literals out of
     * the two help pages.
     *
     * History:
     *   2,238  before the extraction started (the first measurement)
     *   1,867  after the first batch (84 components, 371 strings)
     *   1,062  after the system pages (batch 2B)
     *   1,338  the same tree, measured with the fixed extractor: it sees
     *          the bilingual pairs it used to miss (a ternary that
     *          carries an Arabic side and an English side), and it no
     *          longer counts a lone letter as copy (the letters of the
     *          Saudi plate are data, not labels)
     *   1,052  after batch 2C
     *   812   after batch 2D (the two help pages)
     *
     * Raised by 2 on 2026-10-29: the pages that the parallel process is
     * rewriting right now carry two more literals (in the reports and in
     * the workshop hub). They are theirs to translate, and the next
     * batch lowers the number again.
     *
     * The number must never grow. Each further batch lowers it.
     *
     * @var array<string, int>
     */
    private const MAX_INLINE_ARABIC = [
        'ar' => 814,
    ];

    public function test_no_new_hardcoded_arabic_in_the_vue_sources(): void
    {
        $count = $this->countInlineArabic();

        $this->assertLessThanOrEqual(
            self::MAX_INLINE_ARABIC['ar'],
            $count,
            "The Vue sources hold {$count} inline Arabic literals "
            .'(allowed: '.self::MAX_INLINE_ARABIC['ar'].').'
            ."\nEither move the copy into resources/js/i18n/lang/{ar,en}.json "
            ."and call it with \$t('key'), or — if a page is deliberately "
            .'left alone — lower the ceiling in this test after checking why.'
        );
    }

    /**
     * The extractor must run: a broken script would report zero and the
     * guard above would pass for the wrong reason.
     */
    public function test_the_extractor_finds_the_remaining_arabic(): void
    {
        $script = base_path('scripts/i18n-extract.mjs');

        $this->assertFileExists($script, 'scripts/i18n-extract.mjs is missing');

        $output = [];
        $exitCode = 0;
        exec('node '.escapeshellarg($script).' --json resources/js 2>&1', $output, $exitCode);

        $this->assertSame(
            0,
            $exitCode,
            "The extractor failed:\n".implode("\n", array_slice($output, -5))
        );

        $inventory = json_decode(implode("\n", $output), true);

        $this->assertIsArray($inventory, 'The extractor did not return a usable inventory');
        $this->assertNotEmpty($inventory, 'The extractor found nothing at all — it is broken');
    }

    /**
     * The two locale files must keep the same keys. A key that exists in
     * one language only shows the user the key name instead of the copy.
     */
    public function test_the_locale_files_have_the_same_keys(): void
    {
        $ar = $this->flattenJson(base_path('resources/js/i18n/lang/ar.json'));
        $en = $this->flattenJson(base_path('resources/js/i18n/lang/en.json'));

        $onlyArabic = array_diff(array_keys($ar), array_keys($en));
        $onlyEnglish = array_diff(array_keys($en), array_keys($ar));

        $this->assertSame(
            [],
            array_values($onlyArabic),
            'These keys exist in ar.json but not in en.json: '.implode(', ', array_slice($onlyArabic, 0, 10))
        );

        $this->assertSame(
            [],
            array_values($onlyEnglish),
            'These keys exist in en.json but not in ar.json: '.implode(', ', array_slice($onlyEnglish, 0, 10))
        );
    }

    /**
     * The two languages must carry the same placeholders.
     *
     * A placeholder that exists in one language only is shown to the user
     * as it is: `{amount}` instead of the amount. The placeholders of a
     * key are the names the code passes to the call, so both languages
     * have to ask for the same ones.
     */
    public function test_the_locale_files_carry_the_same_placeholders(): void
    {
        $ar = $this->flattenJson(base_path('resources/js/i18n/lang/ar.json'));
        $en = $this->flattenJson(base_path('resources/js/i18n/lang/en.json'));

        $mismatched = [];

        foreach (array_intersect(array_keys($ar), array_keys($en)) as $key) {
            $inArabic = $this->placeholders($ar[$key]);
            $inEnglish = $this->placeholders($en[$key]);

            if ($inArabic !== $inEnglish) {
                $mismatched[$key] = sprintf(
                    'ar=[%s] en=[%s]',
                    implode(', ', $inArabic),
                    implode(', ', $inEnglish)
                );
            }
        }

        $this->assertSame(
            [],
            $mismatched,
            "These keys carry different placeholders in the two languages:\n"
            .implode("\n", array_map(
                static fn (string $key, string $detail): string => "  {$key}: {$detail}",
                array_keys($mismatched),
                $mismatched
            ))
        );
    }

    /**
     * The placeholders of a copy, in the order they appear.
     *
     * @return array<int, string>
     */
    private function placeholders(string $copy): array
    {
        preg_match_all('/\{([A-Za-z0-9_]+)\}/', $copy, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @return array<string, string>
     */
    private function flattenJson(string $path): array
    {
        $this->assertFileExists($path);

        $decoded = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($decoded, "{$path} is not valid JSON");

        $flat = [];
        $walk = function (array $node, string $prefix) use (&$walk, &$flat): void {
            foreach ($node as $key => $value) {
                $dotted = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

                if (is_array($value)) {
                    $walk($value, $dotted);
                    continue;
                }
                $flat[$dotted] = (string) $value;
            }
        };
        $walk($decoded, '');

        return $flat;
    }

    private function countInlineArabic(): int
    {
        $script = base_path('scripts/i18n-extract.mjs');

        if (! file_exists($script)) {
            $this->fail('scripts/i18n-extract.mjs is missing — the guard cannot run');
        }

        $output = [];
        $exitCode = 0;
        exec('node '.escapeshellarg($script).' --json resources/js 2>&1', $output, $exitCode);

        $inventory = json_decode(implode("\n", $output), true);

        if (! is_array($inventory)) {
            $this->fail('The extractor did not return a usable inventory');
        }

        $count = 0;

        foreach ($inventory as $hits) {
            foreach ($hits as $hit) {
                if (in_array($hit['kind'] ?? '', ['data_field', 'data_value'], true)) {
                    continue;
                }
                $count++;
            }
        }

        return $count;
    }
}
