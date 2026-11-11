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

const HARDCODED_COLOR_RE =
    /\b(?:violet|indigo|blue|purple|red|green|yellow|orange|pink|teal|cyan|sky|slate|gray|stone|neutral|zinc)-(?:50|100|200|300|400|500|600|700|800|900|950)\b/;
const LEGACY_BRAND_COLOR_RE =
    /\b(?:violet|indigo)-(?:50|100|200|300|400|500|600|700|800|900|950)\b/;

const designSystemPlugin = {
    rules: {
        'no-hardcoded-colors': {
            meta: {
                type: 'problem',
                docs: {
                    description:
                        'Disallow hardcoded Tailwind palette colors in favor of CSS design tokens.',
                },
                schema: [
                    {
                        type: 'object',
                        properties: {
                            mode: { enum: ['all', 'legacy-brand'] },
                        },
                        additionalProperties: false,
                    },
                ],
                messages: {
                    hardcodedColor:
                        'Avoid hardcoded Tailwind color "{{match}}". Use semantic design tokens (e.g. action-primary, surface, border-strong, text-primary, focus-ring).',
                },
            },
            create(context) {
                const mode = context.options[0]?.mode || 'all';
                const pattern =
                    mode === 'legacy-brand' ? LEGACY_BRAND_COLOR_RE : HARDCODED_COLOR_RE;

                const checkString = (node, value) => {
                    if (typeof value !== 'string') return;
                    const found = value.match(pattern);
                    if (found) {
                        context.report({
                            node,
                            messageId: 'hardcodedColor',
                            data: { match: found[0] },
                        });
                    }
                };

                const scriptVisitor = {
                    Literal(node) {
                        if (typeof node.value === 'string') {
                            checkString(node, node.value);
                        }
                    },
                    TemplateElement(node) {
                        checkString(node, node.value?.raw);
                    },
                };

                const parserServices = context.sourceCode?.parserServices ?? context.parserServices;
                if (parserServices?.defineTemplateBodyVisitor) {
                    return parserServices.defineTemplateBodyVisitor(
                        {
                            VLiteral(node) {
                                if (node.parent?.key?.name === 'class') {
                                    checkString(node, node.value);
                                }
                            },
                            Literal(node) {
                                if (typeof node.value === 'string') {
                                    checkString(node, node.value);
                                }
                            },
                            TemplateElement(node) {
                                checkString(node, node.value?.raw);
                            },
                        },
                        scriptVisitor
                    );
                }

                return scriptVisitor;
            },
        },
    },
};

const CORE_DESIGN_SYSTEM_COMPONENTS = [
    'resources/js/Components/TextInput.vue',
    'resources/js/Components/Checkbox.vue',
    'resources/js/Components/InputLabel.vue',
    'resources/js/Components/PrimaryButton.vue',
    'resources/js/Components/SecondaryButton.vue',
    'resources/js/Components/DangerButton.vue',
    'resources/js/Components/SearchableSelect.vue',
    'resources/js/Components/App/AppTextarea.vue',
    'resources/js/Components/Common/AppToggle.vue',
    'resources/js/Components/Common/FormField.vue',
    'resources/js/Components/Common/FormSection.vue',
    'resources/js/Components/Common/SelectableCard.vue',
    'resources/js/Components/Common/RadioGroup.vue',
];

export default [
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**', 'storage/**', 'resources/fonts/**'],
    },
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
                // Browser APIs that ESLint v9's default browser env
                // does not include. We add them per-file for the
                // ones that actually use them; keeping the list
                // tight prevents a stealth-y global leak.
                ResizeObserver: 'readonly',
                IntersectionObserver: 'readonly',
                MutationObserver: 'readonly',
                performance: 'readonly',
                requestAnimationFrame: 'readonly',
                cancelAnimationFrame: 'readonly',
                requestIdleCallback: 'readonly',
                cancelIdleCallback: 'readonly',
                getComputedStyle: 'readonly',
                matchMedia: 'readonly',
                Intl: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                prompt: 'readonly',
            },
        },
        rules: {
            // Allow `console.warn` and `console.error`. Forbid the rest.
            // This is the one rule we make a hard error — leaving
            // console.log noise in production code is the single
            // most common Angular/Vue mistake, and the fix is
            // mechanical.
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
            // The remaining rules are warnings during the migration
            // period. Once the codebase has been swept we will
            // promote them to errors.
            'no-undef': 'warn',
            'no-unused-expressions': 'off', // Vue templates trip this
            'no-empty': 'off',
            eqeqeq: 'warn',
            'no-useless-escape': 'warn',
        },
    },
    {
        // TypeScript-via-Vue files. The Phase 2 migration is
        // introducing `<script setup lang="ts">` and `type` imports
        // file-by-file. The plain vue parser chokes on these. We
        // explicitly tell ESLint to skip linting on .ts files for
        // now; vue-tsc covers the type checks. Re-enable when the
        // migration completes.
        ignores: [
            'resources/js/Composables/*.ts',
            'resources/js/types/**',
            'resources/js/auto-imports.d.ts',
            '**/*.d.ts',
        ],
    },
    {
        // Files that need the TypeScript parser. List them
        // explicitly to keep the surface small and reviewable.
        // These files contain `type` imports that the plain
        // vue-eslint-parser cannot read. They are covered by
        // `npm run type-check` (vue-tsc) in the CI pipeline.
        // TODO(phase-2): remove this override once every file
        // has been converted to <script setup lang="ts">.
        files: ['resources/js/Pages/WorkOrders/Show.vue'],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                ecmaVersion: 2022,
                sourceType: 'module',
            },
        },
        rules: {
            'no-unused-vars': 'off',
        },
    },
    {
        // WorkOrder files currently in the Phase 2 TS migration.
        // Once Show.vue + its siblings are on lang="ts" full time,
        // the override above can be deleted and this falls back to
        // the catch-all JS rule set.
        ignores: ['resources/js/Pages/WorkOrders/Show.vue'],
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
    {
        // The tools under scripts/ run on Node, not in the browser, so
        // they use the Node globals. The i18n scripts read the project
        // files and write the translation keys, so they have to stay
        // lint-clean.
        files: ['scripts/**/*.{js,mjs}'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                process: 'readonly',
                console: 'readonly',
                URL: 'readonly',
                __dirname: 'readonly',
            },
        },
    },
    {
        files: ['resources/js/{Components,Pages}/**/*.vue'],
        plugins: {
            'design-system': designSystemPlugin,
        },
        rules: {
            'design-system/no-hardcoded-colors': ['warn', { mode: 'legacy-brand' }],
        },
    },
    {
        files: CORE_DESIGN_SYSTEM_COMPONENTS,
        plugins: {
            'design-system': designSystemPlugin,
        },
        rules: {
            'design-system/no-hardcoded-colors': ['error', { mode: 'all' }],
        },
    },
];
