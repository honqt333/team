import { describe, it, expect, beforeEach } from 'vitest';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { createI18n } from 'vue-i18n';

const ROOT = resolve(__dirname, '../../..');

const readJson = (relativePath) => JSON.parse(readFileSync(resolve(ROOT, relativePath), 'utf-8'));

const ar = readJson('resources/js/i18n/lang/ar.json');
const en = readJson('resources/js/i18n/lang/en.json');

/**
 * Flatten a nested translation catalogue into dotted leaf paths.
 */
const flatten = (node, prefix = '') => {
    const out = {};
    for (const [key, value] of Object.entries(node)) {
        const path = prefix ? `${prefix}.${key}` : key;
        if (value && typeof value === 'object' && !Array.isArray(value)) {
            Object.assign(out, flatten(value, path));
        } else {
            out[path] = value;
        }
    }
    return out;
};

const arFlat = flatten(ar);
const enFlat = flatten(en);

/**
 * The locale files are read on every page load, so a key that exists in
 * one language but not the other renders the raw key in the other
 * language. The parity check is what keeps that from happening.
 */
describe('locale catalogue parity', () => {
    it('has the same keys in Arabic and English', () => {
        const onlyAr = Object.keys(arFlat).filter((k) => !(k in enFlat));
        const onlyEn = Object.keys(enFlat).filter((k) => !(k in arFlat));

        expect({ onlyAr, onlyEn }).toEqual({ onlyAr: [], onlyEn: [] });
    });

    it('has no empty values', () => {
        const emptyAr = Object.entries(arFlat)
            .filter(([, v]) => v === '')
            .map(([k]) => k);
        const emptyEn = Object.entries(enFlat)
            .filter(([, v]) => v === '')
            .map(([k]) => k);

        expect({ emptyAr, emptyEn }).toEqual({ emptyAr: [], emptyEn: [] });
    });

    it('resolves every key to a non-empty string in both locales', () => {
        const i18nAr = createI18n({ locale: 'ar', fallbackLocale: 'ar', messages: { ar } });
        const i18nEn = createI18n({ locale: 'en', fallbackLocale: 'en', messages: { en } });

        for (const key of Object.keys(arFlat)) {
            const valueAr = i18nAr.global.t(key);
            const valueEn = i18nEn.global.t(key);

            expect(valueAr, `ar:${key}`).not.toBe(key);
            expect(valueEn, `en:${key}`).not.toBe(key);
        }
    });
});

/**
 * The application name.
 *
 * The server is the source of truth — HandleInertiaRequests shares
 * `app_name` from config('app.name_ar') / config('app.name'). The locale
 * files are only a fallback for the first paint, and they must not
 * contradict each other.
 */
describe('application name', () => {
    it('is present and identical in meaning across both locales', () => {
        expect(arFlat['common.app_name']).toBeTruthy();
        expect(enFlat['common.app_name']).toBeTruthy();
    });

    it('is not a broken machine translation', () => {
        // "Pro service" was the literal result of translating
        // "خدمة برو" key by key — it names neither the brand nor the
        // product.
        expect(enFlat['common.app_name']).not.toBe('Pro service');
        expect(arFlat['common.app_name']).not.toBe('Pro service');
    });

    it('does not fall back to the stock Laravel name in the frontend', () => {
        const appJs = readFileSync(resolve(ROOT, 'resources/js/app.js'), 'utf-8');

        expect(appJs).not.toMatch(/'Laravel'/);
        expect(appJs).not.toMatch(/"Laravel"/);
    });

    it('reads the name from the server rather than only from the catalogue', () => {
        const appJs = readFileSync(resolve(ROOT, 'resources/js/app.js'), 'utf-8');

        // The server-provided name must take precedence.
        expect(appJs).toContain('app_name');
        expect(appJs).toContain('serverAppName');
    });

    it('has no hardcoded brand name left in the Vue components', () => {
        const files = [
            'resources/js/Components/Print/PrintTemplates/TemplateThermal80.vue',
            'resources/js/Components/Print/PrintTemplates/TemplateDefaultA4.vue',
            'resources/js/Pages/Auth/Register.vue',
            'resources/js/Pages/Public/CustomPage.vue',
        ];

        for (const file of files) {
            const content = readFileSync(resolve(ROOT, file), 'utf-8');

            expect(content, file).not.toMatch(/'خدمة برو'/);
            expect(content, file).not.toMatch(/'Khidmh Pro'/);
            expect(content, file).not.toMatch(/>\s*خدمة برو\s*</);
        }
    });
});

/**
 * The public custom page.
 *
 * Its direction used to be forced to RTL with an `|| true`, which made
 * the English branch unreachable: every public custom page was rendered
 * right-to-left even when the locale was English.
 */
describe('public custom page direction', () => {
    const source = readFileSync(resolve(ROOT, 'resources/js/Pages/Public/CustomPage.vue'), 'utf-8');

    it('does not force RTL', () => {
        expect(source).not.toMatch(/dir\s*===\s*'rtl'\s*\|\|\s*true/);
    });

    it('derives the direction from the document direction', () => {
        expect(source).toMatch(/dir\s*===\s*'rtl'/);
    });

    it('reads the platform name from the server props', () => {
        expect(source).toContain('app_name');
    });
});
