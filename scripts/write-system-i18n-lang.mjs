/**
 * Write the new translation keys into ar.json and en.json.
 *
 * THE PARITY RULE
 *
 * ar.json and en.json must carry the SAME keys. vue-i18n falls back
 * to the other language when a key is missing, so a key that exists
 * only in ar.json shows English text in the Arabic UI — which looks
 * exactly like a translation bug and is very hard to spot in review.
 *
 * The script refuses to write if the two files would end up out of
 * sync, so a half-finished batch cannot be committed.
 *
 * Usage:
 *     node scripts/write-system-i18n-lang.mjs [--dry]
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { TEMPLATE_LITERALS } from './system-i18n-template-literals.mjs';

const map = JSON.parse(readFileSync('/tmp/system-i18n-map.json', 'utf8'));

const dry = process.argv.includes('--dry');

const AR_PATH = 'resources/js/i18n/lang/ar.json';
const EN_PATH = 'resources/js/i18n/lang/en.json';

const ar = JSON.parse(readFileSync(AR_PATH, 'utf8'));
const en = JSON.parse(readFileSync(EN_PATH, 'utf8'));

/** Set a dotted path, creating the intermediate objects. */
function setPath(target, path, value) {
    const parts = path.split('.');
    let node = target;

    for (let i = 0; i < parts.length - 1; i++) {
        const part = parts[i];

        if (!node[part] || typeof node[part] !== 'object') {
            node[part] = {};
        }

        node = node[part];
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

const added = [];
const updated = [];
const skipped = [];

/** The keys owned by the composed template-literal table. */
const composedKeys = new Set(Object.values(TEMPLATE_LITERALS).map((r) => r.key));

// ── The composed template-literal keys ────────────────────────────────────
// A template literal is ONE sentence, so its key holds the WHOLE
// sentence with a placeholder where the hole was.
for (const record of Object.values(TEMPLATE_LITERALS)) {
    const existingAr = getPath(ar, record.key);
    const existingEn = getPath(en, record.key);

    if (existingAr !== undefined && existingEn !== undefined) {
        skipped.push({ key: record.key, reason: 'already exists' });

        continue;
    }

    setPath(ar, record.key, record.ar);
    setPath(en, record.key, record.en);

    added.push(record.key);
}


for (const entry of map.newKeys) {
    const arabic = entry.value;
    const english = entry.en;

    if (!english) {
        skipped.push({ key: entry.key, reason: 'no English' });

        continue;
    }

    // A string that is a FRAGMENT of a template literal is not a
    // sentence and cannot be a translation on its own. Its key is the
    // COMPOSED one, written above from the template-literal table.
    //
    // The fragment is recognised by its key: the composed table owns
    // that key, so writing it here would overwrite the composed
    // sentence with the fragment.
    if (composedKeys.has(entry.key)) {
        continue;
    }

    const existingAr = getPath(ar, entry.key);
    const existingEn = getPath(en, entry.key);

    if (existingAr !== undefined && existingEn !== undefined) {
        skipped.push({ key: entry.key, reason: 'already exists' });

        continue;
    }

    setPath(ar, entry.key, arabic);
    setPath(en, entry.key, english);

    added.push(entry.key);
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
    console.error(JSON.stringify({ added: added.length, skipped: skipped.length, wouldWrite: true }, null, 2));

    process.exit(0);
}

// ── Write ──────────────────────────────────────────────────────────────────
// The lang files are formatted with 4 spaces by Prettier, so match it.
const format = (obj) => `${JSON.stringify(obj, null, 4)}\n`;

writeFileSync(AR_PATH, format(ar));
writeFileSync(EN_PATH, format(en));

console.error(
    JSON.stringify(
        {
            added: added.length,
            skipped: skipped.length,
            arKeys: arKeys.length,
            enKeys: enKeys.length,
            parity: 'ok',
        },
        null,
        2
    )
);
