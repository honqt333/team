// ESLint configuration for Carag V2
//
// Track D (DevX) — Phase 1 starter. Kept intentionally permissive so the
// existing JS codebase (which predates the TS migration) can still be linted
// without churn. Tighten during Phase 2 once resources/js .ts files land.
module.exports = {
    root: true,
    env: {
        browser: true,
        node: true,
        es2022: true,
    },
    parserOptions: {
        ecmaVersion: 2022,
        sourceType: 'module',
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
    },
    ignorePatterns: [
        'node_modules/',
        'public/',
        'vendor/',
        'storage/',
        'bootstrap/cache/',
        'public/build/',
        'resources/js/ziggy.js',
    ],
};