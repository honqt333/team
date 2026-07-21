// ESLint v9 flat config for carag-v2.
//
// Why this file:
//   - The previous CI step (`npm run lint`) was a no-op because no
//     ESLint config existed. Adding one unblocks the lint-frontend
//     CI job and lets us ban `console.log` in production code.
//
// Rules worth keeping tight:
//   - no-console: ban `console.log` / `console.info` / `console.debug`
//     because they leak to the browser console in production.
//     `console.warn` and `console.error` are allowed — they're the
//     error reporting channel we actually want in the browser.
//   - no-unused-vars: standard, but with `_` prefix to allow the
//     common Vue 3 `<script setup>` "import for type" pattern.

import js from '@eslint/js';
import vueParser from 'vue-eslint-parser';

export default [
    js.configs.recommended,
    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                ecmaVersion: 2022,
                sourceType: 'module',
            },
            globals: {
                // Browser globals
                window: 'readonly',
                document: 'readonly',
                navigator: 'readonly',
                localStorage: 'readonly',
                sessionStorage: 'readonly',
                fetch: 'readonly',
                FormData: 'readonly',
                URL: 'readonly',
                URLSearchParams: 'readonly',
                Image: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                setInterval: 'readonly',
                clearInterval: 'readonly',
                console: 'readonly',
                // Vue
                defineProps: 'readonly',
                defineEmits: 'readonly',
                defineExpose: 'readonly',
                defineModel: 'readonly',
                withDefaults: 'readonly',
                // Inertia
                route: 'readonly',
                // Node
                process: 'readonly',
                global: 'readonly',
            },
        },
        rules: {
            // Allow `console.warn` and `console.error`. Forbid the rest.
            'no-console': ['error', { allow: ['warn', 'error'] }],
            // Vue + ESLint recommended
            'no-unused-vars': [
                'warn',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                    caughtErrorsIgnorePattern: '^_',
                },
            ],
            'no-undef': 'error',
            'no-unused-expressions': 'off', // Vue templates trip this
            'no-empty': ['error', { allowEmptyCatch: true }],
            // Equality
            eqeqeq: ['error', 'smart'],
        },
    },
    {
        // Test files can use any console method.
        files: [
            'resources/js/**/__tests__/**',
            'resources/js/**/*.test.js',
            'resources/js/**/*.spec.js',
        ],
        rules: {
            'no-console': 'off',
        },
    },
];
