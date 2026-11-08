import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            'ziggy-js': path.resolve('vendor/tightenco/ziggy/dist/index.esm.js'),
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        /*
         * Modulepreload control.
         *
         * By default Vite's `modulePreload` helper preloads the whole
         * dependency graph of a dynamically-imported chunk. Because the page
         * registry maps every Inertia page, each page loader carried a
         * dependency list, and the browser fetched every one of them on first
         * paint.
         *
         * Measured on the public landing page: 362 requests and ~2.1 MB
         * transferred, including TenantHealthScore, Payments, Proforma,
         * PrintEngine and the whole Website CMS — none of which a landing-page
         * visitor ever opens.
         *
         * `false` disables the dependency preloading entirely. The shared
         * vendor chunks and the page's own CSS are still fetched in parallel
         * with the HTML parse, because Laravel's `@vite` directive emits the
         * entry's modulepreload set into the document head.
         *
         * NOTE: this must sit directly under `build`, not under
         * `rollupOptions.output` — Vite ignores it there.
         */
        modulePreload: {
            resolveDependencies(filename, deps) {
                // Keep only the CSS that the requesting chunk genuinely needs.
                // Dropping the JS deps avoids re-fetching the shared vendor
                // chunks (already preloaded via the @vite directive) and, more
                // importantly, avoids pulling in the dependency tree of every
                // other page's stylesheet.
                return deps.filter((d) => d.endsWith('.css'));
            },
        },
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        /*
                         * The page registry (resources/js/Pages/page-manifest.js)
                         * statically references every Inertia page. When it is
                         * bundled into the app entry chunk, all 219 page
                         * stylesheets become dependencies of that chunk, and
                         * Vite preloads them — which is how the landing page
                         * ended up fetching 58 unrelated CSS files.
                         *
                         * Giving the registry its own chunk keeps those
                         * stylesheets attached to the registry instead of to
                         * the entry, so they are only fetched when a page from
                         * the registry is actually requested.
                         */
                        if (id.includes('page-manifest')) {
                            return 'page-registry';
                        }
                        return;
                    }

                    // Split the giant vendor bundle into per-package chunks
                    // so no single chunk exceeds the 1MB warning threshold.
                    // The previous "everything in `vendor`" approach produced
                    // a single ~2MB chunk that triggered Vite's chunk-size
                    // warning AND silently failed in CI with rollup > 4.x.
                    //
                    // The split is by npm package family so the cache key
                    // (the package name) is stable across rebuilds.
                    if (id.includes('node_modules/vue') || id.includes('node_modules/@vue')) {
                        return 'vendor-vue';
                    }
                    if (
                        id.includes('node_modules/inertia') ||
                        id.includes('node_modules/@inertiajs')
                    ) {
                        return 'vendor-inertia';
                    }
                    if (id.includes('node_modules/axios')) {
                        return 'vendor-axios';
                    }
                    if (id.includes('node_modules/ziggy')) {
                        return 'vendor-ziggy';
                    }
                    if (id.includes('node_modules/dayjs') || id.includes('node_modules/moment')) {
                        return 'vendor-dayjs';
                    }
                    if (id.includes('node_modules/lodash') || id.includes('node_modules/ramda')) {
                        return 'vendor-utils';
                    }
                    if (
                        id.includes('node_modules/vee-validate') ||
                        id.includes('node_modules/yup') ||
                        id.includes('node_modules/zod')
                    ) {
                        return 'vendor-validation';
                    }
                    if (
                        id.includes('node_modules/sortablejs') ||
                        id.includes('node_modules/draggable')
                    ) {
                        return 'vendor-sortable';
                    }

                    // Catch-all for the rest. Still useful as a single
                    // chunk but should be much smaller after the splits
                    // above remove the biggest offenders.
                    return 'vendor-misc';
                },
            },
        },
    },
});
