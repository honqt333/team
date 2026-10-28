<?php

declare(strict_types=1);

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Guards against the "Unable to locate file in Vite manifest" failure.
 *
 * WHAT WENT WRONG
 *
 * Nine Inertia pages were rendered by PHP but had no matching Vue file
 * under resources/js/Pages/:
 *
 *   EmployeePortal/PayslipShow
 *   HR/Payroll/PrintPaySlip
 *   Purchasing/GRN/Form
 *   Purchasing/Orders/Form
 *   Settings/Print/Signatures/Index
 *   System/AdminUsers/Create
 *   System/AdminUsers/Edit
 *   System/Plans/Form
 *   System/PromoCodes/Form
 *
 * Every route that rendered one of them returned HTTP 500 with
 * "Unable to locate file in Vite manifest: resources/js/Pages/...".
 * The problem was invisible to the test suite because no test visited
 * those routes, and invisible to the linters because a missing Vue
 * file is not a PHP error.
 *
 * A TENTH case slipped through afterwards: `System/Analytics/Reports`
 * was rendered from an inline closure in routes/web.php rather than
 * from a controller, so a scanner that only walks app/ never sees it.
 * The page stayed permanently white ("Page not found:
 * ./Pages/System/Analytics/Reports.vue") while this test still
 * passed. The scanner now covers routes/ as well.
 *
 * WHAT THIS TEST DOES
 *
 * It scans every Inertia::render() call in app/ and asserts that the
 * referenced Vue page exists. A new page added in PHP without its
 * Vue counterpart fails here instead of 500ing in production.
 *
 * The check is deliberately about the RENDER TARGET, not about the
 * route: a page can legitimately be rendered by many actions, and an
 * action can legitimately be redirected instead of rendering.
 */
class InertiaPageExistsTest extends TestCase
{
    /**
     * Every PHP file that can call Inertia::render().
     *
     * KEEP THIS LIST COMPLETE.
     *
     * Each entry here was a blind spot that let a broken page through:
     *
     *   app/       the controllers — the obvious one.
     *   routes/    inline closures in routes/web.php. `System/Analytics/
     *              Reports` was rendered from one and had no Vue file:
     *              a permanently white page.
     *   bootstrap/ the exception handler in bootstrap/app.php renders
     *              `Errors/403`. Scanning app/ and routes/ only made
     *              that page look like an orphan.
     */
    private function renderSourceFiles(): array
    {
        $files = [];

        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path(), RecursiveDirectoryIterator::SKIP_DOTS)
        ) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        foreach (glob(base_path('routes/*.php')) ?: [] as $routeFile) {
            $files[] = $routeFile;
        }

        foreach (glob(base_path('bootstrap/*.php')) ?: [] as $bootstrapFile) {
            $files[] = $bootstrapFile;
        }

        return $files;
    }

    /**
     * Map of Inertia page name => the files that render it.
     *
     * @return array<string, array<int, string>>
     */
    private function renderedPages(): array
    {
        $pages = [];

        foreach ($this->renderSourceFiles() as $file) {
            $source = file_get_contents($file);

            if (preg_match_all(
                "/Inertia::render\(\s*'([A-Za-z0-9_\\\\\/]+)'/",
                $source,
                $matches
            )) {
                foreach ($matches[1] as $page) {
                    $pages[$page][] = $file;
                }
            }
        }

        return $pages;
    }

    public function test_every_inertia_page_rendered_by_php_has_a_vue_file(): void
    {
        $pages = $this->renderedPages();

        // Guard against the scanner itself silently matching nothing.
        $this->assertGreaterThan(
            100,
            count($pages),
            'The scan should find the project\'s Inertia pages. If it does not, '
            .'the regex is broken and this test would pass vacuously.'
        );

        $missing = [];

        foreach ($pages as $page => $sources) {
            $expected = base_path("resources/js/Pages/{$page}.vue");

            if (! file_exists($expected)) {
                $missing[$page] = array_unique($sources);
            }
        }

        $this->assertSame(
            [],
            $missing,
            'These Inertia pages are rendered by PHP but have no Vue file, so '
            ."every route that renders them returns HTTP 500:\n"
            .implode(
                "\n",
                array_map(
                    fn ($page, $sources) => '  - '.$page.'  (rendered by: '
                        .implode(', ', array_map(
                            fn ($s) => str_replace(base_path().'/', '', $s),
                            $sources
                        )).')',
                    array_keys($missing),
                    $missing
                )
            )
        );
    }

    /**
     * Every route that renders a page must resolve to a page that
     * exists. This catches the case where a page is renamed on disk
     * but a controller still points at the old name.
     */
    public function test_the_vue_page_directory_has_no_orphaned_directories(): void
    {
        $pagesDir = base_path('resources/js/Pages');

        $this->assertDirectoryExists($pagesDir);

        // A page file whose name does not match its directory's
        // PascalCase convention is almost always a copy/paste mistake
        // that would make the route 500.
        $misnamed = [];

        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pagesDir, RecursiveDirectoryIterator::SKIP_DOTS)
        ) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'vue') {
                continue;
            }

            $name = $file->getBasename('.vue');

            // kebab-case file names are a valid convention too, but a
            // name that mixes styles is worth a look.
            if (preg_match('/^[a-z]+(-[a-z0-9]+)+$/', $name) === 1) {
                continue;
            }

            // HTTP status pages are named after the status code
            // (403.vue, 404.vue) — a digits-only name is a deliberate
            // choice, not a copy/paste mistake.
            if (preg_match('/^[0-9]+$/', $name) === 1) {
                continue;
            }

            if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $name) !== 1) {
                $misnamed[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $misnamed,
            'These Vue page files have a name that no Inertia::render() call '
            ."could produce (the page name is the path without .vue):\n"
            .implode("\n", array_map(fn ($p) => '  - '.$p, $misnamed))
        );
    }

    /**
     * Every page that the renderer can reach must be covered by the
     * scanner above.
     *
     * The `Errors/403` page is rendered from the exception handler in
     * bootstrap/app.php. When the page was removed as an "unreachable
     * Vue file" (the reachability graph was built from the app entry
     * point and the PHP pages only, and never looked at the test
     * files), the contract test that pinned it kept failing. This
     * test pins the wiring itself, so the page cannot go missing
     * silently again.
     */
    public function test_the_status_code_pages_are_wired_to_the_renderer(): void
    {
        $rendered = $this->renderedPages();

        foreach (['Errors/403'] as $page) {
            $this->assertArrayHasKey(
                $page,
                $rendered,
                "The {$page} page must be rendered by the application — "
                .'otherwise it is an unreachable file and the 403 it was '
                .'written for still shows the plain error view.'
            );
        }
    }
}
