/**
 * Rename the translation keys whose name came from a tool rather than
 * from the copy.
 *
 * A key like `system.subscriptions.expression_literal` says where the
 * string was FOUND, not what it SAYS. The next person to open the
 * lang file cannot tell what it is for.
 *
 * The rename has to happen in THREE places at once:
 *
 *   1. the key in ar.json,
 *   2. the key in en.json,
 *   3. every call site in the Vue sources.
 *
 * Missing one of them renders the raw key in the UI, which is worse
 * than an ugly name.
 *
 * Usage:
 *     node scripts/rename-system-i18n-keys.mjs
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { globSync } from 'node:fs';

const RENAMES = {
    'system.tenants.static_template_text': 'system.tenants.price_closing',
    'system.subscriptions.expression_literal': 'system.subscriptions.currency_suffix',
    'system.settings.expression_literal': 'system.settings.disabled_label',
    'system.analytics.expression_literal': 'system.analytics.mrr_label',
    'system.communication.expression_literal': 'system.communication.sms_label',
    'system.credits.static_template_text': 'system.credits.add_label',
};

const AR_PATH = 'resources/js/i18n/lang/ar.json';
const EN_PATH = 'resources/js/i18n/lang/en.json';

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

/** Delete a dotted path. */
function deletePath(target, path) {
    const parts = path.split('.');
    let node = target;

    for (let i = 0; i < parts.length - 1; i++) {
        if (!node[parts[i]] || typeof node[parts[i]] !== 'object') {
            return false;
        }

        node = node[parts[i]];
    }

    if (!(parts[parts.length - 1] in node)) {
        return false;
    }

    delete node[parts[parts.length - 1]];

    return true;
}

const ar = JSON.parse(readFileSync(AR_PATH, 'utf8'));
const en = JSON.parse(readFileSync(EN_PATH, 'utf8'));

const moved = [];

for (const [from, to] of Object.entries(RENAMES)) {
    const get = (obj, path) => path.split('.').reduce((n, k) => (n == null ? undefined : n[k]), obj);

    const arValue = get(ar, from);
    const enValue = get(en, from);

    if (arValue === undefined && enValue === undefined) {
        console.error(`  skip ${from} (not present)`);

        continue;
    }

    if (arValue !== undefined) {
        setPath(ar, to, arValue);
        deletePath(ar, from);
    }

    if (enValue !== undefined) {
        setPath(en, to, enValue);
        deletePath(en, from);
    }

    moved.push({ from, to });
}

// The orphaned fragment keys: nothing references them, and a fragment
// is not a sentence, so they are removed outright.
const ORPHANS = ['system.credits.template_literal_chunk', 'system.credits.template_literal_chunk_2'];

for (const key of ORPHANS) {
    const get = (obj, path) => path.split('.').reduce((n, k) => (n == null ? undefined : n[k]), obj);

    if (get(ar, key) !== undefined) {
        deletePath(ar, key);
    }

    if (get(en, key) !== undefined) {
        deletePath(en, key);
    }
}

// ── The call sites ────────────────────────────────────────────────────────
const files = globSync('resources/js/**/*.vue');
let touched = 0;

for (const file of files) {
    const source = readFileSync(file, 'utf8');
    let out = source;

    for (const { from, to } of moved) {
        const pattern = new RegExp(`(['"\`])${from.replace(/\./g, '\\.')}\\1`, 'g');

        if (pattern.test(out)) {
            out = out.replace(pattern, `$1${to}$1`);
        }
    }

    if (out !== source) {
        writeFileSync(file, out);
        touched++;
    }
}

// ── Write the lang files ──────────────────────────────────────────────────
const format = (obj) => `${JSON.stringify(obj, null, 4)}\n`;

writeFileSync(AR_PATH, format(ar));
writeFileSync(EN_PATH, format(en));

console.error(JSON.stringify({ renamed: moved.length, orphansRemoved: ORPHANS.length, filesTouched: touched }, null, 2));
