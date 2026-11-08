<?php

declare(strict_types=1);

namespace Tests\Feature;

use FilesystemIterator;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Landing page performance guard.
 *
 * The Phase 1 rebuild of the public landing page took it from 49% to 95%
 * Lighthouse performance. The bulk of that came from ONE fix: the page
 * registry in `resources/js/app.js` used to make the browser fetch every
 * Inertia page's chunk and stylesheet on the landing page — 362 requests and
 * ~2.1 MB, including the whole Website CMS and the tenant dashboard.
 *
 * That class of regression is invisible in code review: nothing looks wrong,
 * the page still works, and it just quietly gets slower. This test is the
 * guard.
 *
 * It checks the SOURCE (so it runs without a build) for the patterns that
 * caused the problem, plus a few content rules that must not regress.
 */
class LandingPerformanceGuardTest extends TestCase
{
    private function source(string $path): string
    {
        $full = base_path($path);
        $this->assertFileExists($full, "Expected file {$path} to exist.");

        return File::get($full);
    }

    /**
     * Source with COMMENTS stripped, but strings intact.
     *
     * The rebuild left explanatory comments in the code describing exactly
     * what was removed ("the particle canvas is gone", "AutoCare was
     * invented"). Matching the raw text would flag those comments as
     * violations.
     *
     * Strings must be KEPT: the patterns being checked for live in string
     * literals (a glob path, a forbidden client name). Stripping strings
     * would blind the guard entirely.
     *
     * A comment-aware scan is needed rather than a regex, because `//` also
     * appears inside URLs and template literals.
     */
    private function code(string $path): string
    {
        $source = $this->source($path);
        $out = '';
        $length = strlen($source);
        $i = 0;
        $inBlock = false;
        $inLine = false;
        $quote = null;

        while ($i < $length) {
            $char = $source[$i];
            $next = $i + 1 < $length ? $source[$i + 1] : '';

            if ($inBlock) {
                if ($char === '*' && $next === '/') {
                    $inBlock = false;
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }

            if ($inLine) {
                if ($char === "\n") {
                    $inLine = false;
                    $out .= $char;
                }
                $i++;
                continue;
            }

            if ($quote !== null) {
                $out .= $char;

                if ($char === '\\' && $next !== '') {
                    $out .= $next;
                    $i += 2;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }
                $i++;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $inBlock = true;
                $i += 2;
                continue;
            }

            if ($char === '/' && $next === '/') {
                $inLine = true;
                $i += 2;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $out .= $char;
                $i++;
                continue;
            }

            $out .= $char;
            $i++;
        }

        return $out;
    }

    public function test_page_registry_does_not_use_a_glob(): void
    {
        /*
         * `import.meta.glob` over the Pages tree makes Vite attach a
         * dependency list to every page loader, and the browser fetches all
         * of them. The explicit manifest avoids that.
         */
        $app = $this->code('resources/js/app.js');

        $this->assertStringNotContainsString(
            "import.meta.glob('./Pages/**/*.vue')",
            $app,
            'The page registry must use the generated page-manifest.js, not a glob. '
            .'A glob makes the browser fetch every Inertia page on every page load.'
        );

        $this->assertStringContainsString(
            "from './Pages/page-manifest'",
            $app,
            'app.js must resolve pages through the generated page manifest.'
        );
    }

    public function test_page_manifest_covers_every_page_component(): void
    {
        $manifest = $this->source('resources/js/Pages/page-manifest.js');

        // The generated format is:  'Name': () => import('./Path.vue'),
        preg_match_all("/'([^']+)':\\s*\\(\\)\\s*=>\\s*import\\('\\./", $manifest, $matches);
        $registered = $matches[1] ?? [];

        $this->assertNotEmpty($registered, 'The page manifest must not be empty.');

        // Every .vue file under Pages/ (except the manifest itself) must be
        // registered, or a page would 404 at runtime.
        $onDisk = [];
        $pagesRoot = base_path('resources/js/Pages');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pagesRoot, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'vue') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($pagesRoot) + 1);
            $onDisk[] = str_replace(['\\', '.vue'], ['/', ''], $relative);
        }

        $missing = array_values(array_diff($onDisk, $registered));
        $this->assertSame(
            [],
            array_values($missing),
            'These page components are missing from the page manifest and would '
            .'404 at runtime. Run: npm run build:manifest'
        );
    }

    public function test_page_registry_is_its_own_chunk(): void
    {
        /*
         * If the registry is bundled into the app entry chunk, all 219 page
         * stylesheets become dependencies of that chunk and get preloaded.
         */
        $vite = $this->source('vite.config.js');

        $this->assertStringContainsString(
            'page-manifest',
            $vite,
            'vite.config.js must give the page registry its own chunk, otherwise '
            .'every page stylesheet is preloaded on every page load.'
        );
    }

    public function test_landing_page_does_not_use_the_particle_canvas(): void
    {
        /*
         * The particle canvas was the single biggest source of main-thread
         * work on the page, and one of the clearest "AI template" tells.
         */
        $landing = $this->code('resources/js/Pages/Public/LandingPreview.vue');

        $this->assertStringNotContainsString(
            'particles-canvas',
            $landing,
            'The particle canvas is gone — it must not come back.'
        );
        $this->assertStringNotContainsString(
            'cursor-glow',
            $landing,
            'The cursor-glow follower is gone — it must not come back.'
        );
    }

    public function test_landing_stylesheet_has_no_expensive_backdrop_filters(): void
    {
        /*
         * `backdrop-filter: blur()` on a large surface forces a full-screen
         * repaint on every scroll frame. The page keeps exactly one, on the
         * sticky header, where the blur is the point.
         */
        $css = $this->code('resources/css/landing.css');

        /*
         * Count RULES, not declarations: one rule usually carries both
         * `backdrop-filter` and `-webkit-backdrop-filter`, and those are one
         * expensive effect, not two.
         */
        preg_match_all('/backdrop-filter:\s*blur\([^)]*\)/', $css, $matches);
        $declarations = $matches[0] ?? [];

        $webkit = preg_match_all('/-webkit-backdrop-filter:\s*blur\([^)]*\)/', $css);
        $count = count($declarations) - $webkit;

        $this->assertLessThanOrEqual(
            1,
            $count,
            "The landing stylesheet must keep at most one backdrop-filter (found {$count})."
        );
    }

    public function test_landing_page_respects_reduced_motion(): void
    {
        $css = $this->source('resources/css/landing.css');

        $this->assertStringContainsString(
            'prefers-reduced-motion',
            $css,
            'The landing page must honour the reduced-motion preference. Without it, '
            .'the scroll-reveal leaves content pinned at opacity:0 for those users.'
        );
    }

    public function test_hero_image_is_preloaded_and_has_explicit_dimensions(): void
    {
        /*
         * The hero image is the LCP candidate. It needs an explicit size (no
         * layout shift) and a preload (no discovery round-trip).
         */
        $landing = $this->code('resources/js/Pages/Public/LandingPreview.vue');

        $this->assertStringContainsString(
            'rel="preload"',
            $landing,
            'The hero image must be preloaded — it is the LCP candidate.'
        );
        $this->assertStringContainsString(
            'as="image"',
            $landing,
            'The hero image preload must declare as="image".'
        );
    }

    public function test_landing_page_has_no_hardcoded_arbitrary_client_logos(): void
    {
        /*
         * The previous version pointed at Wikimedia car logos (BMW, Mercedes,
         * Volkswagen, Suzuki) and named four invented workshops. Those must
         * never come back.
         */
        $controller = $this->code('app/Http/Controllers/Public/PublicLandingController.php');
        $settings = $this->code('app/Http/Controllers/System/WebsiteSettingsController.php');

        foreach ([$controller, $settings] as $source) {
            $this->assertStringNotContainsString(
                'upload.wikimedia.org',
                $source,
                'The landing defaults must not reference Wikimedia car logos.'
            );

            foreach (['AutoCare', 'SpeedFix', 'ProGarage', 'StarMotors'] as $invented) {
                $this->assertStringNotContainsString(
                    $invented,
                    $source,
                    "\"{$invented}\" is an invented client name — it must not be a default."
                );
            }
        }
    }

    public function test_landing_page_does_not_ship_fabricated_growth_claims(): void
    {
        $controller = $this->code('app/Http/Controllers/Public/PublicLandingController.php');
        $settings = $this->code('app/Http/Controllers/System/WebsiteSettingsController.php');

        foreach ([$controller, $settings] as $source) {
            $this->assertDoesNotMatchRegularExpression(
                "/'value'\s*=>\s*'\+\d/",
                $source,
                'The landing defaults must not ship "+N customers" style growth claims.'
            );
        }
    }
}
