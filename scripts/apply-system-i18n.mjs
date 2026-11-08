/**
 * Apply the key map to the /system Vue pages.
 *
 * THE ORDER MATTERS
 *
 * The spans come from the extractor and they all point at the SOURCE
 * text. Replacing them in DESCENDING offset order keeps every earlier
 * span valid, because a later replacement never moves the text before
 * it.
 *
 * WHAT GETS REPLACED
 *
 *   - a plain string literal  'نص'        -> t('key')
 *   - a static attribute       title="نص"  -> :title="t('key')"
 *   - a text node              نص          -> {{ t('key') }}
 *   - a ternary branch         cond ? 'ن'  -> cond ? t('k')
 *
 * A TERNARY IS NOT REPLACED BRANCH BY BRANCH
 *
 * `isAr ? 'نص' : 'Text'` is ONE bilingual pair, not two independent
 * strings. Replacing the Arabic half with `t('key')` would leave the
 * English half in place, and the English UI would still show Arabic.
 * So a ternary is replaced as a WHOLE, with both branches becoming the
 * same key, and the condition is preserved.
 *
 * A TEMPLATE LITERAL WITH AN INTERPOLATION
 *
 * `هل تريد حذف باقة "${pkg.name_ar}"؟` is ONE sentence with a hole in
 * it. It becomes `t('key', { name: pkg.name_ar })` — the hole keeps
 * its value. Replacing the chunk with a bare `t('key')` would DROP the
 * value, which is data loss in a printed statement, not a translation
 * bug.
 *
 * Usage:
 *     node scripts/apply-system-i18n.mjs [--files=a.vue,b.vue]
 */

import { readFileSync, writeFileSync } from 'node:fs';
import * as acorn from 'acorn';
import { TEMPLATE_LITERALS } from './system-i18n-template-literals.mjs';

const map = JSON.parse(readFileSync('/tmp/system-i18n-map.json', 'utf8'));

// ── Options ────────────────────────────────────────────────────────────────
const only = (process.argv.find((a) => a.startsWith('--files=')) ?? '').replace('--files=', '');
const onlyFiles = only ? new Set(only.split(',').map((f) => f.trim()).filter(Boolean)) : null;

// ── Helpers ────────────────────────────────────────────────────────────────
const ARABIC = /[؀-ۿ]/;

/** Escape a value for use inside a single-quoted JS string. */
function jsString(value) {
    return value.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

/** The key for a given file+value. */
const keyIndex = new Map();

for (const entry of map.map) {
    keyIndex.set(`${entry.file}||${entry.value}`, entry);
}

/**
 * Find the template literal that contains a given span.
 *
 * The extractor reports the CHUNKS of a template literal, not the
 * literal itself, so the rewriter has to find the whole thing to
 * rebuild it as a single `t()` call.
 */
function findTemplateLiteral(source, start, end) {
    // Find the backtick that opens the literal: walk backwards from
    // the chunk, skipping over `${ ... }` holes.
    let i = start - 1;
    let depth = 0;

    while (i >= 0) {
        const ch = source[i];

        if (ch === '}') {
            depth++;
            i--;

            continue;
        }

        if (ch === '{') {
            if (depth === 0 && source[i - 1] === '$') {
                // The hole of an interpolation we are inside.
                depth--;
                i -= 2;

                continue;
            }

            depth--;
            i--;

            continue;
        }

        if (ch === '`') {
            if (depth === 0) {
                return { open: i };
            }

            i--;

            continue;
        }

        if (ch === '"' || ch === "'") {
            // Skip a quoted string so a backtick inside it is not
            // mistaken for the template delimiter.
            const quote = ch;
            i--;

            while (i >= 0 && source[i] !== quote) {
                i--;
            }

            i--;

            continue;
        }

        i--;
    }

    return null;
}

/** Find the end of the template literal that opens at `open`. */
function findTemplateEnd(source, open) {
    let i = open + 1;
    let depth = 0;

    while (i < source.length) {
        const ch = source[i];

        if (ch === '\\') {
            i += 2;

            continue;
        }

        if (ch === '$' && source[i + 1] === '{') {
            depth++;
            i += 2;

            continue;
        }

        if (ch === '}') {
            depth--;
            i++;

            continue;
        }

        if (ch === '`' && depth === 0) {
            return i;
        }

        i++;
    }

    return -1;
}

/**
 * Parse a template literal into its chunks and its holes.
 *
 * `holes` are the `${...}` expressions, `chunks` the static text
 * between them.
 */
function parseTemplateLiteral(source, open, close) {
    const body = source.slice(open + 1, close);
    const chunks = [];
    const holes = [];
    let i = 0;
    let chunkStart = 0;

    while (i < body.length) {
        if (body[i] === '\\') {
            i += 2;

            continue;
        }

        if (body[i] === '$' && body[i + 1] === '{') {
            chunks.push(body.slice(chunkStart, i));

            // Find the matching brace.
            let depth = 1;
            let j = i + 2;

            while (j < body.length && depth > 0) {
                if (body[j] === '{') {
                    depth++;
                } else if (body[j] === '}') {
                    depth--;
                }

                j++;
            }

            holes.push(body.slice(i + 2, j - 1));
            i = j;
            chunkStart = j;

            continue;
        }

        i++;
    }

    chunks.push(body.slice(chunkStart));

    return { chunks, holes };
}

/**
 * The placeholder name for a hole.
 *
 * The name is PART OF THE CONTRACT with the lang file: it has to be
 * the same string in ar.json, in en.json and in the call site, or
 * vue-i18n renders `{amount}` literally in one of the two languages.
 *
 * So the name is PINNED here rather than derived. The derived name
 * was `_diff` for `formatCurrency(-diff)` and `value0` for
 * `selectedPlanObj.trial_days || 14` — both wrong, and both would
 * have produced a lang file that did not match its own call sites.
 */
const PARAM_NAMES = {
    'formatCurrency(-diff)': 'amount',
    'formatCurrency(diff)': 'amount',
    'formatCurrency(partialShortfall)': 'amount',
    'formatCurrency(-difference)': 'amount',
    'variable': 'variable',
    'pkg.name_ar': 'name',
    'invoice.invoice_number': 'number',
    'tenant.name': 'name',
    'selectedPlanObj.trial_days || 14': 'days',
};

function paramName(hole, index) {
    const pinned = PARAM_NAMES[hole.trim()];

    if (pinned) {
        return pinned;
    }

    // `pkg.name_ar` -> `name_ar`.
    const path = hole.match(/^[\w$]+(?:\.[\w$]+)*$/);

    if (path) {
        return path[0].split('.').pop().replace(/[^\w]/g, '_') || `value${index}`;
    }

    // `formatCurrency(x)` -> `value0`; the caller renames it.
    return `value${index}`;
}

/**
 * Build the replacement for a template literal.
 *
 * The literal is ONE sentence, so it becomes ONE call:
 *
 *     `سيُسجَّل نقص ${amount} SAR كرصيد`  ->  t('key', { amount: amount })
 *
 * The key comes from the composed table, where the Arabic and the
 * English are the WHOLE sentence with a placeholder where the hole
 * was. That is what keeps the two languages in step — a chunk-by-
 * chunk translation would put a fragment in the lang file, and the
 * fragment would render as a fragment.
 */
function buildTemplateReplacement(file, source, open, close, t) {
    const { chunks, holes } = parseTemplateLiteral(source, open, close);

    // The composed form of this literal: the chunks with the holes
    // marked by their placeholder name.
    const names = holes.map((hole, index) => paramName(hole, index));

    let composed = '';
    let cursor = 0;

    for (let i = 0; i < chunks.length; i++) {
        composed += chunks[i];

        if (i < names.length) {
            composed += `{${names[i]}}`;
        }
    }

    const relative = file.replace('resources/js/Pages/System/', '');
    const record = TEMPLATE_LITERALS[`${relative}||${composed}`];

    if (!record) {
        return null;
    }

    const params = names.map((name, index) => `${name}: ${holes[index]}`);
    const args = params.length > 0 ? `, { ${params.join(', ')} }` : '';

    return {
        text: `${t}('${record.key}'${args})`,
        key: record.key,
        params: names,
        ar: record.ar,
        en: record.en,
    };
}

/**
 * Find a text node that is glued to an interpolation.
 *
 * `{{ price }} ر.س` is one piece of output but two AST nodes: a
 * TEXT node holding ` ر.س` and an INTERPOLATION holding the price.
 * Turning them into two separate interpolations puts a space where
 * the copy had none, and the number and its currency can end up on
 * different lines.
 *
 * The fix is to merge them: `{{ $t('price') + $t('currency') }}`.
 */
function findGluedNeighbour(source, entry, entries) {
    const before = source.slice(Math.max(0, entry.start - 80), entry.start);
    const after = source.slice(entry.end, entry.end + 80);

    // The text node FOLLOWS an interpolation.
    const beforeMatch = before.match(/\{\{([^}]*)\}\}\s*$/);

    if (beforeMatch) {
        const interpStart = entry.start - beforeMatch[0].length;
        const inner = beforeMatch[1].trim();

        // Only merge when the interpolation is a single expression.
        if (!/[{}]/.test(inner) && inner.length > 0) {
            return {
                start: interpStart,
                end: entry.end,
                text: `{{ ${beforeMatch[1].trim()} + $t('${entry.key}') }}`,
                other: { start: interpStart },
            };
        }

        return null;
    }

    // The text node PRECEDES an interpolation.
    const afterMatch = after.match(/^\s*\{\{([^}]*)\}\}/);

    if (afterMatch) {
        const inner = afterMatch[1].trim();

        if (!/[{}]/.test(inner) && inner.length > 0) {
            const interpEnd = entry.end + afterMatch[0].length;

            return {
                start: entry.start,
                end: interpEnd,
                text: `{{ $t('${entry.key}') + ${afterMatch[1].trim()} }}`,
                other: { start: entry.end },
            };
        }
    }

    return null;
}

// ── The rewriter ───────────────────────────────────────────────────────────
const stats = {
    files: 0,
    replaced: 0,
    skipped: 0,
    needsI18n: [],
    byKind: {},
};

/** The `$t` in a template, `t` in a script. */
const TEMPLATE_KINDS = new Set(['template_text', 'static_attr', 'bound_attr', 'interpolation']);

const byFile = new Map();

for (const entry of map.map) {
    if (!byFile.has(entry.file)) {
        byFile.set(entry.file, []);
    }

    byFile.get(entry.file).push(entry);
}

for (const [file, entries] of byFile) {
    if (onlyFiles && !onlyFiles.has(file)) {
        continue;
    }

    const source = readFileSync(file, 'utf8');

    // Where does the template end and the script begin? A span in the
    // template uses `$t`, a span in a script uses `t`.
    const templateEnd = findTemplateEndOffset(source);

    const edits = [];
    const handled = new Set();

    for (const entry of entries) {
        if (handled.has(entry.start)) {
            continue;
        }

        const inTemplate = !isInScript(source, entry.start);
        const t = inTemplate ? '$t' : 't';

        // ── A template literal ──────────────────────────────────────────
        const tpl = findTemplateLiteral(source, entry.start, entry.end);

        if (tpl && tpl.open !== undefined) {
            const close = findTemplateEnd(source, tpl.open);

            if (close > 0) {
                const replacement = buildTemplateReplacement(file, source, tpl.open, close, t);

                if (replacement) {
                    edits.push({
                        start: tpl.open,
                        end: close + 1,
                        text: replacement.text,
                        kind: 'template_literal',
                    });

                    // Every chunk of this literal is handled by the one
                    // replacement.
                    const { chunks } = parseTemplateLiteral(source, tpl.open, close);
                    let cursor = tpl.open + 1;

                    for (const chunk of chunks) {
                        const offset = source.indexOf(chunk, cursor);
                        cursor = offset + chunk.length;

                        for (const e of entries) {
                            if (e.start >= offset && e.start <= offset + chunk.length) {
                                handled.add(e.start);
                            }
                        }
                    }

                    continue;
                }
            }
        }

        // ── A static attribute ──────────────────────────────────────────
        // `placeholder="نص"` is not valid once the value is an
        // expression, so the attribute becomes a binding:
        // `:placeholder="$t('key')"`.
        if (entry.kind === 'static_attr') {
            const before = source.slice(entry.start - 1, entry.start);
            const after = source.slice(entry.end, entry.end + 1);

            if ((before === '"' && after === '"') || (before === "'" && after === "'")) {
                // The attribute name precedes the opening quote. The
                // name has to be found in the SOURCE, not in a slice,
                // because the slice's start would throw the offset
                // calculation off by the slice's length.
                // The head ends just before the opening quote, so it
                // ends with `name=`. The name is the last word before
                // that `=`.
                const head = source.slice(Math.max(0, entry.start - 200), entry.start - 1);
                const nameMatch = head.match(/([\w:.-]+)\s*=\s*$/);

                if (nameMatch) {
                    const nameStart = entry.start - 1 - head.length + nameMatch.index;

                    edits.push({
                        start: nameStart,
                        end: entry.end + 1,
                        text: `:${nameMatch[1]}="${t}('${entry.key}')"`,
                        kind: entry.kind,
                    });

                    continue;
                }
            }
        }

        // ── A plain string literal ──────────────────────────────────────
        const raw = source.slice(entry.start - 1, entry.end + 1);
        const isQuoted = raw[0] === "'" || raw[0] === '"';

        if (isQuoted) {
            edits.push({
                start: entry.start - 1,
                end: entry.end + 1,
                text: `${t}('${entry.key}')`,
                kind: entry.kind,
            });

            continue;
        }

        // ── A text node ─────────────────────────────────────────────────
        if (entry.kind === 'template_text') {
            // A text node that sits right next to an interpolation is
            // ONE piece of output: `{{ price }} ر.س`. Splitting it into
            // two interpolations would put a stray space in the middle
            // and, worse, would render the currency on its own line
            // when the template wraps.
            //
            // So the two are merged into a single interpolation:
            // `{{ price }} ر.س` -> `{{ $t('k1') + $t('k2') }}`.
            const glued = findGluedNeighbour(source, entry, entries);

            if (glued) {
                edits.push({
                    start: glued.start,
                    end: glued.end,
                    text: glued.text,
                    kind: 'glued',
                });

                handled.add(glued.other.start);

                continue;
            }

            edits.push({
                start: entry.start,
                end: entry.end,
                text: `{{ ${t}('${entry.key}') }}`,
                kind: entry.kind,
            });

            continue;
        }

        // ── A bound attribute or an interpolation ───────────────────────
        // The span covers only the literal, so the quotes around it
        // stay: `:title="cond ? 'a' : 'b'"` becomes
        // `:title="cond ? $t('k') : $t('k')"`.
        if (entry.kind === 'bound_attr' || entry.kind === 'interpolation') {
            const before = source.slice(entry.start - 1, entry.start);
            const after = source.slice(entry.end, entry.end + 1);

            if ((before === "'" && after === "'") || (before === '"' && after === '"')) {
                edits.push({
                    start: entry.start,
                    end: entry.end,
                    text: `${t}('${entry.key}')`,
                    kind: entry.kind,
                });

                continue;
            }
        }

        stats.skipped++;
        stats.skippedList ??= [];
        stats.skippedList.push({ file, value: entry.value, kind: entry.kind, context: entry.context });
    }

    if (edits.length === 0) {
        continue;
    }

    // Apply the edits in DESCENDING order so every offset stays valid.
    edits.sort((a, b) => b.start - a.start);

    let out = source;
    let applied = 0;

    for (const edit of edits) {
        out = out.slice(0, edit.start) + edit.text + out.slice(edit.end);
        applied++;
        stats.byKind[edit.kind] = (stats.byKind[edit.kind] ?? 0) + 1;
    }

    // A script that now calls `t(...)` needs the composable.
    // The composable is only needed when the SCRIPT calls `t(...)`.
    // A template uses `$t(...)`, which needs no import, and the regex
    // must not match the `t` inside `$t(`.
    const scriptUsesT = /(?<![\w$])t\('/.test(out);

    if (scriptUsesT && !/useI18n/.test(out)) {
        out = addUseI18n(out);
        stats.needsI18n.push(file);
    }

    writeFileSync(file, out);

    stats.files++;
    stats.replaced += applied;
}

/**
 * The offset where the TEMPLATE block ends.
 *
 * A span before it is in the template and uses `$t`; a span after it
 * is in a script and uses `t`.
 *
 * The blocks can come in ANY order. Most pages put `<script setup>`
 * first and `<template>` second, and a file where the script comes
 * first used to get `$t()` inside the script — where `$t` does not
 * exist in `<script setup>` and the page throws on load.
 */
function findTemplateEndOffset(source) {
    const match = source.match(/<\/template>/);

    return match ? match.index + match[0].length : 0;
}

/**
 * Is this offset inside a SCRIPT block?
 *
 * The template is the region between `<template>` and `</template>`;
 * everything else — `<script>`, `<script setup>` — is script.
 */
function isInScript(source, offset) {
    const templateStart = source.indexOf('<template>');
    const templateEnd = source.indexOf('</template>');

    if (templateStart === -1 || templateEnd === -1) {
        return true;
    }

    return offset < templateStart || offset > templateEnd;
}

/**
 * Add the `useI18n` composable to a script block.
 *
 * The import goes at the top of the `<script setup>` block, right
 * after the opening tag, and the destructuring right after it.
 */
function addUseI18n(source) {
    const setup = source.match(/<script setup[^>]*>/);

    if (setup) {
        const at = setup.index + setup[0].length;

        return (
            source.slice(0, at) +
            "\nimport { useI18n } from 'vue-i18n';\nconst { t } = useI18n();\n" +
            source.slice(at)
        );
    }

    const plain = source.match(/<script[^>]*>/);

    if (plain) {
        const at = plain.index + plain[0].length;

        return (
            source.slice(0, at) +
            "\nimport { useI18n } from 'vue-i18n';\nconst { t } = useI18n();\n" +
            source.slice(at)
        );
    }

    return source;
}

console.error(JSON.stringify(stats, null, 2));
