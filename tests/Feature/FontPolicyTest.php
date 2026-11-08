<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Font policy guard.
 *
 * The constitution allows exactly one webfont: IBM Plex Sans Arabic. The
 * application used to pull a second family (Tajawal) and a duplicate copy
 * of IBM Plex from Google Fonts through a CSS `@import` in
 * `resources/js/Design/typography.css`.
 *
 * A CSS `@import` is the worst way to load a font: the browser only finds
 * the import after the stylesheet has downloaded, then opens a connection
 * to a third-party origin and downloads the font files from there. On the
 * tenant public site that was 8 files / 164 KiB from fonts.gstatic.com on
 * every page load, and it delayed the first paint.
 *
 * Removing the import is the fix, but nothing stopped it from coming back
 * — so this test does.
 */
class FontPolicyTest extends TestCase
{
    /**
     * Source with comments stripped.
     *
     * The files that document the fix quote the offending URLs, so a naive
     * match would flag its own explanation as a violation.
     */
    private function withoutComments(string $source): string
    {
        // Block comments.
        $source = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;

        // Line comments — but NOT the `//` inside a URL (`https://`).
        $source = preg_replace('#(?<!:)//[^\n]*#', '', $source) ?? $source;

        return $source;
    }

    private function sourceFiles(): array
    {
        $files = [];

        foreach (['resources/js', 'resources/css', 'resources/views'] as $dir) {
            foreach (File::allFiles(base_path($dir)) as $file) {
                if (in_array($file->getExtension(), ['js', 'mjs', 'vue', 'css', 'blade.php'], true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    public function test_no_source_file_loads_fonts_from_google(): void
    {
        $offenders = [];

        foreach ($this->sourceFiles() as $file) {
            $code = $this->withoutComments(File::get($file));

            if (str_contains($code, 'fonts.googleapis.com') || str_contains($code, 'fonts.gstatic.com')) {
                $offenders[] = str_replace(base_path().'/', '', $file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'These files load a webfont from Google Fonts. The project allows one webfont '
            .'(IBM Plex Sans Arabic), self-hosted from /public/fonts — a CSS @import adds a '
            ."third-party connection and delays the first paint.\n  ".implode("\n  ", $offenders)
        );
    }

    public function test_the_font_token_does_not_name_a_parallel_family(): void
    {
        $code = $this->withoutComments(File::get(base_path('resources/js/Design/tokens.css')));

        $this->assertStringNotContainsString(
            'Tajawal',
            $code,
            'Tajawal is a parallel font. Only IBM Plex Sans Arabic may be used.'
        );
        $this->assertStringNotContainsString(
            'Cairo',
            $code,
            'Cairo is a parallel font. Only IBM Plex Sans Arabic may be used.'
        );
        $this->assertStringContainsString(
            "'IBM Plex Sans Arabic'",
            $code,
            'The font token must name the approved family.'
        );
    }

    public function test_the_self_hosted_font_stylesheet_is_served_and_declares_the_family(): void
    {
        $path = base_path('public/css/fonts.css');

        $this->assertFileExists($path, 'The self-hosted font stylesheet is missing.');

        $css = File::get($path);

        $this->assertStringContainsString("font-family: 'IBM Plex Sans Arabic'", $css);
        $this->assertStringContainsString('font-display: swap', $css, 'Fonts must not block the first paint.');

        // Every @font-face src must point at a file that actually exists.
        preg_match_all('#url\(\s*[\'"]?(/fonts/[^\'")]+)#', $css, $matches);

        $this->assertNotEmpty($matches[1], 'No font files are declared.');

        foreach (array_unique($matches[1]) as $url) {
            $this->assertFileExists(
                base_path('public'.$url),
                "The stylesheet points at {$url}, which does not exist on disk."
            );
        }
    }

    public function test_the_layout_links_the_local_font_stylesheet(): void
    {
        $blade = $this->withoutComments(File::get(base_path('resources/views/app.blade.php')));

        $this->assertStringContainsString(
            '/css/fonts.css',
            $blade,
            'The layout must link the self-hosted font stylesheet.'
        );
    }
}
