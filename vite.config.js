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
        rollupOptions: {
            output: {
                /*
                 * Modulepreload control.
                 *
                 * By default Vite's `modulePreload` helper preloads the whole
                 * dependency graph of a dynamically-imported chunk. Because
                 * `app.js` globs all 219 pages into one map, every page
                 * loader carried a dependency list, and the browser fetched
                 * every one of them on first paint.
                 *
                 * Measured on the public landing page: 362 requests, ~2.1 MB
                 * transferred, including TenantHealthScore, Payments,
                 * Proforma, PrintEngine and the whole Website CMS — none of
                 * which a landing-page visitor ever opens.
                 *
                 * `resolveDependencies` now returns an empty list: the shared
                 * vendor chunks are already in the entry's own preload set,
                 * so preloading them again per page was pure duplication.
                 */
                modulePreload: {
                    resolveDependencies() {
                        return [];
                    },
                },
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
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
