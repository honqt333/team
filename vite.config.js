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
                    if (id.includes('node_modules/inertia') || id.includes('node_modules/@inertiajs')) {
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
                    if (id.includes('node_modules/vee-validate') || id.includes('node_modules/yup') || id.includes('node_modules/zod')) {
                        return 'vendor-validation';
                    }
                    if (id.includes('node_modules/sortablejs') || id.includes('node_modules/draggable')) {
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
