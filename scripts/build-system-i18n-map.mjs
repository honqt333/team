/**
 * Build the key map for the /system Vue pages.
 *
 * For every string the extractor found, propose a translation key:
 *
 *   - REUSE an existing key when its ARABIC value matches exactly.
 *     That is the safe case: the key already has a reviewed English.
 *   - PROPOSE a new key when nothing matches.
 *
 * The English for a new key is derived from the file's own English
 * half where the string came from a bilingual pair or a ternary —
 * the codebase already carries the English next to the Arabic, so
 * there is no guessing.
 *
 * Usage:
 *     node scripts/build-system-i18n-map.mjs
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { TRANSLATIONS } from './system-i18n-translations.mjs';

const ARABIC = /[؀-ۿ]/;

const data = JSON.parse(readFileSync('/tmp/system-i18n.json', 'utf8'));
const ar = JSON.parse(readFileSync('resources/js/i18n/lang/ar.json', 'utf8'));
const en = JSON.parse(readFileSync('resources/js/i18n/lang/en.json', 'utf8'));

/** Flatten a nested object into dotted leaf paths. */
function flatten(obj, prefix = '') {
    return Object.entries(obj).flatMap(([key, value]) =>
        value && typeof value === 'object' && !Array.isArray(value)
            ? flatten(value, prefix ? `${prefix}.${key}` : key)
            : [[prefix ? `${prefix}.${key}` : key, value]]
    );
}

const arFlat = new Map(flatten(ar));
const enFlat = new Map(flatten(en));

/** Arabic value -> the keys that carry it. */
const byArabic = new Map();

for (const [key, value] of arFlat) {
    if (typeof value !== 'string') {
        continue;
    }

    if (!byArabic.has(value)) {
        byArabic.set(value, []);
    }

    byArabic.get(value).push(key);
}

/**
 * The namespace a new key belongs to.
 *
 * Derived from the page path so the keys stay grouped the way the
 * pages are grouped.
 */
function namespaceFor(file) {
    const m = file.match(/^resources\/js\/Pages\/System\/([^/]+)\//);

    if (!m) {
        return 'system.common';
    }

    const section = m[1];

    const known = {
        AdminUsers: 'system.admin_users',
        Analytics: 'system.analytics',
        Announcements: 'system.announcements',
        BankTransferReceipts: 'system.bank_transfer_receipts',
        Communication: 'system.communication',
        Credits: 'system.credits',
        Developer: 'system.developer',
        Help: 'system.help',
        Invoices: 'system.invoices',
        Notifications: 'system.notifications',
        Payment: 'system.payment',
        Profile: 'system.profile',
        Security: 'system.security',
        Settings: 'system.settings',
        Subscriptions: 'system.subscriptions',
        Tenants: 'system.tenants',
        Website: 'system.website',
    };

    return known[section] ?? `system.${section.toLowerCase()}`;
}

/**
 * A readable key fragment for a string.
 *
 * Transliteration of Arabic is not an option here — the key has to
 * be readable by whoever maintains it. So the fragment comes from the
 * ENGLISH, which every entry has, and falls back to a generic name
 * only for the rare string with no usable English.
 */
function slugFor(value) {
    const ascii = value
        .replace(/[^\x20-\x7E]/g, ' ')
        // A dot in a key is the PATH SEPARATOR in the lang file, so
        // `Search by tenant name...` must not become
        // `search_by_tenant_name...` — the writer would turn it into
        // three nested objects.
        .replace(/[^\w\s-]/g, '')
        .trim()
        .replace(/\s+/g, '_')
        .replace(/-+/g, '_')
        .replace(/^_+|_+$/g, '')
        .toLowerCase();

    if (ascii.length >= 3) {
        return ascii.slice(0, 48).replace(/_+$/, '');
    }

    return null;
}

/**
 * The key fragment for a string that has no usable English.
 *
 * The kind and the context describe what the string IS, so the key
 * still says something a reader can act on.
 */
function fallbackSlug(kind, context) {
    const fromContext = String(context ?? '')
        .replace(/[^\w\s-]/g, '')
        .trim()
        .replace(/\s+/g, '_')
        .toLowerCase();

    if (fromContext.length >= 4) {
        return fromContext.slice(0, 40);
    }

    const byKind = {
        template_text: 'text',
        static_attr: 'placeholder',
        bound_attr: 'label',
        interpolation: 'label',
        script_literal: 'text',
        bilingual_pair: 'text',
        data_map: 'value',
    };

    return byKind[kind] ?? 'label';
}

/**
 * The English half of a bilingual pair or a ternary, for the same file.
 *
 * The search is POSITIONAL. A file-wide regex happily matches a
 * ternary that has nothing to do with this string — `مسودة` once
 * picked up "MRR" from a KPI card three hundred lines away — and the
 * key that gets built from it is wrong in both languages.
 *
 * So the search is restricted to a window around the string, and the
 * window is small enough that only the string's own line and its
 * immediate neighbours are in range.
 */
function englishFor(file, arabicValue, offset) {
    const source = readFileSync(file, 'utf8');

    // The window: the string's own line plus the lines around it.
    const lineStart = source.lastIndexOf('\n', offset) + 1;
    const lineEnd = source.indexOf('\n', offset);
    const from = Math.max(0, lineStart - 400);
    const to = Math.min(source.length, (lineEnd < 0 ? source.length : lineEnd) + 400);
    const window = source.slice(from, to);

    // A bilingual object `{ ar: '...', en: '...' }`.
    const bilingual = new RegExp(
        `ar\\s*:\\s*(['"\`])(?:\\\\.|(?!\\1).)*${escapeRe(arabicValue)}(?:\\\\.|(?!\\1).)*\\1\\s*,\\s*en\\s*:\\s*(['"\`])([^'"\`]*)\\2`
    );

    const m = window.match(bilingual);

    if (m && !ARABIC.test(m[3])) {
        return m[3];
    }

    // A ternary `cond ? 'arabic' : 'english'` — the string is the
    // FIRST branch.
    const ternaryFirst = new RegExp(`(['"\`])${escapeRe(arabicValue)}\\1\\s*:\\s*(['"\`])([^'"\`]*)\\2`);

    const t = window.match(ternaryFirst);

    if (t && !ARABIC.test(t[3])) {
        return t[3];
    }

    // A ternary `cond ? 'english' : 'arabic'` — the string is the
    // SECOND branch.
    const ternarySecond = new RegExp(`(['"\`])([^'"\`]*)\\1\\s*:\\s*(['"\`])${escapeRe(arabicValue)}\\3`);

    const s = window.match(ternarySecond);

    if (s && !ARABIC.test(s[2])) {
        return s[2];
    }

    // The other half of the pair may be Arabic too (`is_published ?
    // 'منشور' : 'مسودة'`), in which case there is no English in the
    // file at all and the dictionary has to supply it.
    const arPair = window.match(
        new RegExp(`(['"\`])${escapeRe(arabicValue)}\\1\\s*:\\s*(['"\`])((?:\\\\.|(?!\\2).)*)\\2`)
    );

    if (arPair) {
        // The sibling is Arabic — keep the pair together by giving
        // the sibling the same treatment.
        return arPair[3];
    }

    return null;
}

function escapeRe(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}


/**
 * Is this string a fragment of a template literal?
 *
 * A fragment is the tail of a sentence with a hole in it:
 * `هل تريد حذف باقة "${pkg.name_ar}"؟` is TWO fragments, and the
 * sentence between them is the composed key.
 */
function isTemplateLiteralFragment(file, item) {
    const source = readFileSync(file, 'utf8');
    const after = source.slice(item.end, item.end + 4);
    const before = source.slice(Math.max(0, item.start - 60), item.start);

    return /^\$\{/.test(after) || /\$\{[^}]*\}\s*$/.test(before);
}

const used = new Set();
const map = [];
const newKeys = [];

/** The same string in the same file always gets the SAME key. */
const assigned = new Map();

for (const detail of data.detail) {
    const ns = namespaceFor(detail.file);

    for (const item of detail.strings) {
        const assignKey = `${detail.file}||${item.value}`;
        const already = assigned.get(assignKey);

        if (already) {
            map.push({
                file: detail.file,
                value: item.value,
                kind: item.kind,
                start: item.start,
                end: item.end,
                context: item.context,
                key: already.key,
                status: already.status,
                en: already.en,
                duplicate: true,
            });

            continue;
        }

        const existing = byArabic.get(item.value) ?? [];

        /**
         * Pick the best existing key for this string.
         *
         * The rule is deliberately strict. A loose match lets an
         * unrelated key hijack a string — `إجمالي المشتريات` landed on
         * `reports.tax_periods_report.total_purchases_label` once,
         * which reads wrong even though the Arabic is identical.
         *
         * So only TWO families may be reused:
         *
         *   1. the page's own namespace (`system.credits.*`) — the
         *      key was written for this exact screen;
         *   2. the shared vocabulary (`system.common.*`, `common.*`) —
         *      a reviewed, deliberately generic label.
         *
         * Anything else becomes a NEW key, even when the Arabic
         * matches somewhere unrelated. A duplicate key is a much
         * smaller problem than a wrong one.
         */
        const rank = (key) => {
            if (key.startsWith(ns + '.')) {
                return 0;
            }

            if (key.startsWith('system.common.') || key.startsWith('common.')) {
                return 1;
            }

            return null;
        };

        const candidates = existing
            .map((key) => ({ key, rank: rank(key) }))
            .filter((c) => c.rank !== null)
            .sort((a, b) => a.rank - b.rank || a.key.localeCompare(b.key));

        const chosen = candidates.length > 0 ? candidates[0].key : null;

        if (chosen) {
            const entry = {
                file: detail.file,
                value: item.value,
                kind: item.kind,
                start: item.start,
                end: item.end,
                context: item.context,
                key: chosen,
                status: 'reuse',
                en: enFlat.get(chosen) ?? null,
            };

            assigned.set(assignKey, entry);
            map.push(entry);

            continue;
        }

        // A new key: the English comes from the file's own English
        // half when the string is half of a bilingual pair, and from
        // the reviewed dictionary otherwise.
        const relative = detail.file.replace('resources/js/Pages/System/', '');
        const fromDict = TRANSLATIONS[`${relative}||${item.value}`] ?? null;
        const fromFile = englishFor(detail.file, item.value, item.start);

        // The dictionary is the reviewed source of truth, so it wins
        // over the file. The file's own English is the fallback for
        // the strings the dictionary does not cover.
        const english = fromDict ?? fromFile;
        const slug = slugFor(english ?? '') ?? slugFor(item.value) ?? fallbackSlug(item.kind, item.context);

        let candidate = `${ns}.${slug}`;
        let n = 2;

        while (used.has(candidate) || arFlat.has(candidate)) {
            candidate = `${ns}.${slug}_${n}`;
            n++;
        }

        used.add(candidate);

        const entry = {
            file: detail.file,
            value: item.value,
            kind: item.kind,
            start: item.start,
            end: item.end,
            context: item.context,
            key: candidate,
            status: 'new',
            en: english,
            needsEnglish: !english,
            // A FRAGMENT of a template literal is not a sentence. Its
            // key is the COMPOSED one, written from the
            // template-literal table, and this fragment must not
            // create a key of its own.
            isFragment: isTemplateLiteralFragment(detail.file, item),
        };

        assigned.set(assignKey, entry);
        map.push(entry);
        newKeys.push(entry);
    }
}

const summary = {
    total: map.length,
    reuse: map.filter((m) => m.status === 'reuse').length,
    new: newKeys.length,
    needsEnglish: newKeys.filter((k) => k.needsEnglish).length,
    files: data.detail.length,
};

writeFileSync('/tmp/system-i18n-map.json', JSON.stringify({ summary, map, newKeys }, null, 2));

console.error(JSON.stringify(summary, null, 2));
