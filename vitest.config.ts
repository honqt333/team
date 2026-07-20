import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [vue()],
    test: {
        globals: true,
        environment: 'jsdom',
        include: ['resources/js/**/*.test.ts', 'resources/js/**/*.test.js'],
        setupFiles: ['resources/js/test/setup.js'],
        coverage: {
            reporter: ['text', 'html'],
            exclude: ['**/node_modules/**', '**/dist/**'],
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            'ziggy-js': fileURLToPath(
                new URL('./vendor/tightenco/ziggy/dist/index.esm.js', import.meta.url)
            ),
        },
    },
});
