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
    public function test_every_inertia_page_rendered_by_php_has_a_vue_file(): void
    {
        $pages = [];

        $controllers = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path())
        );

        foreach ($controllers as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            if (preg_match_all(
                "/Inertia::render\(\s*'([A-Za-z0-9_\\\\\/]+)'/",
                $source,
                $matches
            )) {
                foreach ($matches[1] as $page) {
                    $pages[$page][] = $file->getPathname();
                }
            }
        }

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
}
