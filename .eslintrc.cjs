// ESLint configuration for Khidmah Pro (active configuration for ESLint v8)
//
// NOTE: `eslint.config.js` is an inactive ESLint v9 flat-config draft.
// Until ESLint is upgraded to v9, `.eslintrc.cjs` is the single active config
// used by `npm run lint` and CI (`lint-frontend`).
module.exports = {
    root: true,
    env: {
        browser: true,
        node: true,
        es2022: true,
    },
    parser: 'vue-eslint-parser',
    parserOptions: {
        ecmaVersion: 2022,
        sourceType: 'module',
        parser: {
            // For <script lang="ts"> blocks, delegate to TS parser
            ts: '@typescript-eslint/parser',
            // For <script> (default JS) blocks, use the default parser
            js: 'espree',
        },
    },
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
    ],
    plugins: ['vue'],
    rules: {
        // Carag currently mixes .js and .ts; relax rules that flag untyped
        // call sites in the JS-only portions until Phase 2 migrates them.
        'no-unused-vars': 'off',
        'no-undef': 'off',
        'no-empty': ['error', { allowEmptyCatch: true }],
        'vue/multi-word-component-names': 'off',
        'vue/html-self-closing': 'off',
        'vue/max-attributes-per-line': 'off',
        'vue/singleline-html-element-content-newline': 'off',
        'vue/html-indent': 'off',
        'vue/html-closing-bracket-newline': 'off',
        'vue/attributes-order': 'off',
        'vue/first-attribute-linebreak': 'off',
        'vue/attribute-hyphenation': 'off',

        // ---- Phase 1 baseline --------------------------------------------------
        // The legacy Vue codebase (Composables/, Pages/WorkOrders/) predates
        // this lint setup and contains patterns that the recommended Vue 3
        // ruleset flags as errors. We surface them as warnings so the lint
        // script can run green during Phase 1 DevX rollout, then tighten to
        // 'error' in Phase 2 once the file owners address them.
        // -----------------------------------------------------------------------
        'vue/no-mutating-props': 'warn',
        'vue/no-unused-vars': 'warn',
        'no-useless-escape': 'warn',
        'vue/no-side-effects-in-computed-properties': 'warn',
        'vue/no-dupe-keys': 'warn',
        'vue/require-default-prop': 'warn',
        'vue/html-closing-bracket-spacing': 'warn',
        'vue/multiline-html-element-content-newline': 'warn',
        'no-restricted-syntax': [
            'error',
            {
                selector: "VAttribute[key.name='html']",
                message: 'Use v-safe-html instead of v-html. See SafeHtmlPlugin.',
            },
        ],
        'no-console': ['warn', { allow: ['warn', 'error'] }],
        'no-restricted-imports': [
            'warn',
            {
                paths: [
                    {
                        name: '@/Components/TextInput.vue',
                        message: 'Deprecated: use @/Components/App/AppInput.vue instead.',
                    },
                    {
                        name: '@/Components/PrimaryButton.vue',
                        message: 'Deprecated: use @/Components/App/AppButton.vue instead.',
                    },
                    {
                        name: '@/Components/SecondaryButton.vue',
                        message: 'Deprecated: use @/Components/App/AppButton.vue (variant="secondary") instead.',
                    },
                    {
                        name: '@/Components/DangerButton.vue',
                        message: 'Deprecated: use @/Components/App/AppButton.vue (variant="danger") instead.',
                    },
                    {
                        name: '@/Components/Checkbox.vue',
                        message: 'Deprecated: use @/Components/App/AppCheckbox.vue instead.',
                    },
                    {
                        name: '@/Components/Toggle.vue',
                        message: 'Deprecated: use @/Components/Common/AppToggle.vue instead.',
                    },
                    {
                        name: '@/Components/Modal.vue',
                        message: 'Deprecated: use @/Components/BaseModal.vue or ConfirmModal.vue instead.',
                    },
                    {
                        name: '@/Components/DialogModal.vue',
                        message: 'Deprecated: use @/Components/BaseModal.vue or ConfirmModal.vue instead.',
                    },
                ],
            },
        ],
    },
    ignorePatterns: [
        'node_modules/',
        'public/',
        'vendor/',
        'storage/',
        'bootstrap/cache/',
        'public/build/',
        'resources/js/ziggy.js',
        // TypeScript declaration files (consumed by vue-tsc, not Vite/ESLint)
        'resources/js/types/',
    ],
};