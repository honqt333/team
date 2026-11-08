/**
 * Write the missing translation keys into the locale files.
 *
 * THREE PLACES, ONE KEY
 *
 * A key can live in the Vue JSON files or in the PHP array files,
 * and `translations:check` reads both. The key
 * `invoices.original_invoice_not_found` is called from PHP with
 * `__('...')`, so it belongs in `lang/ar/invoices.php` and
 * `lang/en/invoices.php`; the rest are called from a Vue template
 * with `$t('...')`, so they belong in the JSON files.
 *
 * THE PARITY RULE
 *
 * ar and en must carry the same keys. vue-i18n falls back to the
 * other language, so a key that exists only in ar.json shows ENGLISH
 * in the Arabic UI — which reads as a translation bug and is very
 * hard to spot in review.
 *
 * The script refuses to write if the two files would end up out of
 * sync.
 *
 * Usage:
 *     node scripts/write-missing-i18n-keys.mjs [--dry]
 */

import { readFileSync, writeFileSync } from 'node:fs';

import { MISSING_KEYS } from './missing-i18n-keys.mjs';

const dry = process.argv.includes('--dry');

const AR_JSON = 'resources/js/i18n/lang/ar.json';
const EN_JSON = 'resources/js/i18n/lang/en.json';

/** The keys that belong in the PHP array files. */
const PHP_KEYS = {
    'invoices.original_invoice_not_found': {
        ar: 'الفاتورة الأصلية غير موجودة',
        en: 'The original invoice was not found',
    },
};

/** Set a dotted path, creating the intermediate objects. */
function setPath(target, path, value) {
    const parts = path.split('.');
    let node = target;

    for (let i = 0; i < parts.length - 1; i++) {
        if (!node[parts[i]] || typeof node[parts[i]] !== 'object') {
            node[parts[i]] = {};
        }

        node = node[parts[i]];
    }

    node[parts[parts.length - 1]] = value;
}

/** Read a dotted path. */
function getPath(target, path) {
    return path.split('.').reduce((node, part) => (node == null ? undefined : node[part]), target);
}

/** Flatten a nested object into dotted leaf paths. */
function flatten(obj, prefix = '') {
    return Object.entries(obj).flatMap(([key, value]) =>
        value && typeof value === 'object' && !Array.isArray(value)
            ? flatten(value, prefix ? `${prefix}.${key}` : key)
            : [[prefix ? `${prefix}.${key}` : key, value]]
    );
}

const ar = JSON.parse(readFileSync(AR_JSON, 'utf8'));
const en = JSON.parse(readFileSync(EN_JSON, 'utf8'));

const added = [];
const skipped = [];

for (const [key, value] of Object.entries(MISSING_KEYS)) {
    if (getPath(ar, key) !== undefined || getPath(en, key) !== undefined) {
        skipped.push(key);

        continue;
    }

    setPath(ar, key, value.ar);
    setPath(en, key, value.en);

    added.push(key);
}

// ── The parity check ───────────────────────────────────────────────────────
const arKeys = flatten(ar).map(([k]) => k).sort();
const enKeys = flatten(en).map(([k]) => k).sort();

const onlyAr = arKeys.filter((k) => !enKeys.includes(k));
const onlyEn = enKeys.filter((k) => !arKeys.includes(k));

if (onlyAr.length > 0 || onlyEn.length > 0) {
    console.error('PARITY BROKEN — refusing to write.');
    console.error('  only in ar.json:', onlyAr.slice(0, 20));
    console.error('  only in en.json:', onlyEn.slice(0, 20));
    process.exit(1);
}

if (dry) {
    console.error(JSON.stringify({ added: added.length, skipped: skipped.length }, null, 2));

    process.exit(0);
}

// ── Write the JSON files ──────────────────────────────────────────────────
const format = (obj) => `${JSON.stringify(obj, null, 4)}\n`;

writeFileSync(AR_JSON, format(ar));
writeFileSync(EN_JSON, format(en));

// ── The PHP array files ───────────────────────────────────────────────────
const phpAdded = [];

for (const [key, value] of Object.entries(PHP_KEYS)) {
    const [group, name] = key.split('.');

    for (const [locale, text] of [['ar', value.ar], ['en', value.en]]) {
        const path = `lang/${locale}/${group}.php`;

        let source = readFileSync(path, 'utf8');

        // The key is already there.
        if (new RegExp(`'${name}'\\s*=>`).test(source)) {
            continue;
        }

        // Insert it in alphabetical order, because the file is sorted
        // and a key dropped in at the end is a key nobody finds.
        const entries = [...source.matchAll(/^ {4}'([a-z0-9_]+)' =>/gm)].map((m) => m[1]);

        if (entries.length === 0) {
            throw new Error(`Could not find the entries of ${path}.`);
        }

        let insertAt = entries.findIndex((entry) => entry > name);

        if (insertAt === -1) {
            insertAt = entries.length - 1;
        }

        const lines = source.split('\n');
        const targetLine = lines.findIndex((line) => new RegExp(`^ {4}'${entries[insertAt]}' =>`).test(line));

        lines.splice(targetLine, 0, `    '${name}' => '${text.replace(/'/g, "\\'")}',`);

        writeFileSync(path, lines.join('\n'));

        phpAdded.push(`${path}: ${name}`);
    }
}

console.error(
    JSON.stringify(
        {
            jsonAdded: added.length,
            jsonSkipped: skipped.length,
            phpAdded: phpAdded.length,
            arKeys: arKeys.length,
            enKeys: enKeys.length,
            parity: 'ok',
        },
        null,
        2
    )
);
