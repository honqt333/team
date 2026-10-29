#!/usr/bin/env node
/**
 * Replace the hardcoded Arabic literals with translation calls, using a
 * mapping file that says which key each literal becomes.
 *
 * The mapping file is JSON:
 *
 *   {
 *     "resources/js/Pages/Help/Index.vue": {
 *       "the arabic text": { "key": "help.guide.intro", "en": "The intro" }
 *     }
 *   }
 *
 * A literal whose Arabic text matches an entry is replaced according to
 * the context it was found in:
 *
 *   template_text    ->  {{ $t('key') }}
 *   template_attr    ->  :attr="$t('key')"
 *   script_literal   ->  t('key')
 *   script_template  ->  t('key')
 *   ui_label         ->  t('key')
 *   bilingual_pair   ->  t('key')          (the English half is dropped)
 *
 * The script rewrites a file in one pass, from the end backwards, so the
 * offsets of the earlier hits stay valid.
 *
 * Usage:
 *   node scripts/i18n-rewrite.mjs --map <file.json> [--dry-run] [paths ...]
 */

import fs from 'fs';
import * as acorn from 'acorn';
import path from 'path';

const args = process.argv.slice(2);
const mapIndex = args.indexOf('--map');
if (mapIndex === -1) {
    console.error('ERROR: --map <file.json> is required');
    process.exit(1);
}
const mapPath = args[mapIndex + 1];
const dryRun = args.includes('--dry-run');
const targets = args.filter((a) => a !== '--map' && a !== mapPath && a !== '--dry-run');

const mapping = JSON.parse(fs.readFileSync(mapPath, 'utf8'));

// The inventory the extraction produced.
const inventoryPath = path.join(path.dirname(new URL(import.meta.url).pathname), '.i18n-inventory.json');
let inventory = {};
if (fs.existsSync(inventoryPath)) {
    inventory = JSON.parse(fs.readFileSync(inventoryPath, 'utf8'));
}

function findInventory(file) {
    if (inventory[file]) return inventory[file];
    // Fall back to a path suffix match.
    for (const [key, hits] of Object.entries(inventory)) {
        if (file.endsWith(key) || key.endsWith(file)) return hits;
    }
    return null;
}

/**
 * The name a placeholder gets in a translation call. The name is taken
 * from the last part of the interpolated expression, so
 * `${invoice.code || invoice.number}` becomes `number`. The same rule is
 * used when the key is written to the language files, so the Arabic, the
 * English and the call all carry the same name.
 */
function parameterName(expression) {
    const last = expression.split(/\|\||\?\?|:/).pop().trim();
    const name = (last.split('.').pop() || 'value').replace(/[^A-Za-z0-9_]/g, '_');
    return name || 'value';
}

/** Does this file already have a `t` in scope inside <script setup>? */
function scriptHasT(source) {
    return /const\s*\{[^}]*\bt\b[^}]*\}\s*=\s*useI18n\s*\(/.test(source);
}

function hasUseI18nImport(source) {
    return /import\s*\{[^}]*useI18n[^}]*\}\s*from\s*['"]vue-i18n['"]/.test(source);
}

/**
 * The Arabic branches of the conditional expressions in an expression
 * string, read from the AST.
 */
function arabicBranches(expression) {
    const found = [];
    let ast;
    try {
        ast = acorn.parse(`(${expression})`, { ecmaVersion: 'latest' });
    } catch {
        return found;
    }
    const walk = (node) => {
        if (!node || typeof node !== 'object') return;
        if (Array.isArray(node)) {
            node.forEach(walk);
            return;
        }
        if (node.type === 'ConditionalExpression') {
            for (const branch of [node.consequent, node.alternate]) {
                if (!branch || branch.type !== 'Literal') continue;
                if (typeof branch.value !== 'string') continue;
                if (!/[\u0600-\u06FF]/.test(branch.value)) continue;
                found.push(branch.value);
            }
        }
        for (const key of Object.keys(node)) {
            if (key === 'loc' || key === 'start' || key === 'end' || key === 'type') continue;
            const value = node[key];
            if (value && typeof value === 'object') walk(value);
        }
    };
    walk(ast);
    return found;
}

/**
 * Does this offset sit inside a `defineProps(...)` call?
 *
 * The options of `defineProps` are hoisted out of `setup()`, so they may
 * not call `t()` — the build fails with "defineProps() in <script setup>
 * cannot reference locally declared variables". A default value that
 * holds copy has to be translated where it is shown instead.
 */
function insideDefineProps(source, offset) {
    const start = source.lastIndexOf('defineProps(', offset);
    if (start === -1) return false;

    let depth = 0;
    // The scan starts at the bracket of `defineProps(` itself, so the
    // first closing bracket that brings the depth back to zero is the
    // end of the whole call.
    for (let index = start + 'defineProps'.length; index < source.length; index++) {
        const character = source[index];
        if (character === '(') depth++;
        else if (character === ')') {
            depth--;
            if (depth === 0) return offset > start && offset < index;
        }
    }
    return false;
}

function applyFile(file) {
    const hits = findInventory(file);
    const entries = mapping[file];
    if (!hits || !entries) return null;

    const source = fs.readFileSync(file, 'utf8');
    const edits = [];
    const notes = [];

    for (const hit of hits) {
        const text = hit.value.trim();
        // A call inside the options of `defineProps` cannot be built
        // there: the options are hoisted out of `setup()`. The copy is
        // left alone and reported instead.
        if (hit.block === 'script' && insideDefineProps(source, hit.start)) {
            notes.push(
                `line ${hit.line}: the copy is inside defineProps() — it must be translated where it is shown`
            );
            continue;
        }
        // An interpolation carries the whole expression, so its Arabic
        // literal is matched inside the case that handles it.
        if (hit.kind === 'interpolation_literal') {
            // `{{ `${x} ر.س` }}` — the Arabic is the literal part of a
            // template literal. Only that part is replaced; the
            // interpolations inside it stay as they are.
            const entry3 = entries[hit.value.trim()];
            if (!entry3) continue;

            const inner = source.slice(hit.start, hit.end);
            const literalMatch = inner.match(/`([^`]*)`/);
            if (!literalMatch) continue;

            // The literal text with its interpolations taken out is what
            // the mapping is keyed by.
            const literal = literalMatch[1];
            const literalStart = hit.start + literalMatch.index + 1;

            // The literal text becomes one call; the interpolations it
            // carries are passed along as named parameters.
            const placeholders = [...literal.matchAll(/\$\{([^}]*)\}/g)];
            const parameters = placeholders.map((p) => parameterName(p[1].trim()));

            let replacement = `$t('${entry3.key}'`;
            if (parameters.length > 0) {
                replacement +=
                    ', { ' +
                    placeholders
                        .map((p, index) => `${parameters[index]}: ${p[1].trim()}`)
                        .join(', ') +
                    ' }';
            }
            replacement += ')';

            edits.push({
                start: literalStart,
                end: literalStart + literal.length,
                replacement,
                kind: hit.kind,
                block: hit.block,
                line: hit.line,
            });
            continue;
        }

        if (hit.kind === 'interpolation_branch') {
            // `cond ? 'نص' : other` — only the copy of the branch is
            // replaced; the condition and the other branch stay.
            const inner = source.slice(hit.start, hit.end);
            // The Arabic branch of the ternary, whichever side of the
            // `:` it sits on. It is found by its own text, so the
            // surrounding expression does not have to be understood.
            const text = hit.value.trim();
            const entry6 = entries[text];
            if (!entry6) continue;
            const offset = inner.indexOf(text);
            if (offset === -1) continue;
            const start = hit.start + offset;
            const end = start + text.length;

            // When the copy sits inside quotes, the quotes go too:
            // `'نص'` must become `t('key')`, not `'t('key')'`.
            const before = inner.slice(0, offset);
            const after = inner.slice(offset + text.length);
            // The last character of `before` is the opening quote.
            const quoted = /['"`]\s*$/.test(before) && /^['"`]/.test(after);

            edits.push({
                start: quoted ? start - 1 : start,
                end: quoted ? end + 1 : end,
                replacement: quoted
                    ? `$t('${entry6.key}')`
                    : `$t('${entry6.key}')`,
                kind: hit.kind,
                block: hit.block,
                line: hit.line,
            });
            continue;
        }

        if (hit.kind === 'interpolation_fallback') {
            // `cond ? value : 'نص احتياطي'` — only the fallback copy is
            // replaced; the condition and the real value stay as they are.
            const inner = source.slice(hit.start, hit.end);
            const fallback = inner.match(/\|\|\s*(')([^']*)\1/);
            if (!fallback) continue;
            const entry4 = entries[fallback[2].trim()];
            if (!entry4) continue;
            const start = hit.start + fallback.index;
            const end = start + fallback[0].length;
            edits.push({
                start,
                end,
                replacement: `|| $t('${entry4.key}')`,
                kind: hit.kind,
                block: hit.block,
                line: hit.line,
            });
            continue;
        }

        const entry = entries[text];
        if (!entry) continue;

        const key = entry.key;
        const original = source.slice(hit.start, hit.end);
        let replacement = null;

        if (hit.kind === 'interpolation') {
            // The Arabic branches are read from the AST, so a condition
            // that is itself a ternary is handled correctly.
            const branches = arabicBranches(hit.value);
            const arabic = branches.find((b) => entries[b.trim()]);
            if (!arabic) continue;
            const entry2 = entries[arabic.trim()];
            // A key that carries a placeholder keeps the interpolation.
            const hasPlaceholder = /\$\{[\s\S]*?\}/.test(arabic);
            const replacementText = hasPlaceholder ? entry2.key : `$t('${entry2.key}')`;
            edits.push({
                start: hit.start,
                end: hit.end,
                replacement: `{{ ${replacementText} }}`,
                kind: hit.kind,
                block: hit.block,
                line: hit.line,
            });
            continue;
        }

        switch (hit.kind) {
            case 'template_text': {
                // Keep the surrounding whitespace of the text node.
                const leading = original.match(/^\s*/)[0];
                const trailing = original.match(/\s*$/)[0];
                // The node may hold an interpolation of its own
                // (`صالح حتى: {{ date }}`) — only the copy is replaced.
                const interpolation = original.match(/{{[\s\S]*?}}/);
                if (interpolation) {
                    const copy = original.slice(0, interpolation.index);
                    const copyTrimmed = copy.trim();
                    const entry5 = entries[copyTrimmed];
                    if (!entry5) break;
                    const copyLeading = copy.match(/^\s*/)[0];
                    const copyTrailing = copy.slice(copyTrimmed.length + copyLeading.length);
                    replacement = `${copyLeading}{{ $t('${entry5.key}') }}${copyTrailing}${interpolation[0]}`;
                    break;
                }
                replacement = `${leading}{{ $t('${key}') }}${trailing}`;
                break;
            }
            case 'template_attr': {
                // `title="نص"` -> `:title="$t('key')"`
                const attr = hit.attr;
                if (original.startsWith('"') || original.startsWith("'")) {
                    replacement = `:${attr}="$t('${key}')"`;
                } else {
                    replacement = `:${attr}="$t('${key}')"`;
                }
                break;
            }
            case 'bilingual_pair': {
                // The extractor read the conditional expression from the
                // AST, so the span already covers `cond ? 'ar' : 'en'`.
                // Inside a template the call is `$t(...)`, inside a script
                // it is `t(...)`.
                edits.push({
                    start: hit.start,
                    end: hit.end,
                    replacement:
                        hit.block === 'template' ? `$t('${key}')` : `t('${key}')`,
                    kind: hit.kind,
                    block: hit.block,
                    line: hit.line,
                });
                break;
            }
            case 'script_literal':
            case 'ui_label': {
                replacement = `t('${key}')`;
                break;
            }
            case 'script_template': {
                // `سند: ${reference}` — the interpolations are woven into
                // the copy, so they are handed to the call as named
                // parameters instead of being dropped with the literal.
                const placeholders = [...original.matchAll(/\$\{([^}]*)\}/g)];
                if (placeholders.length === 0) {
                    replacement = `t('${key}')`;
                    break;
                }
                const parameters = placeholders.map((p) => parameterName(p[1].trim()));
                replacement =
                    `t('${key}', { ` +
                    placeholders
                        .map((p, index) => `${parameters[index]}: ${p[1].trim()}`)
                        .join(', ') +
                    ' })';
                break;
            }
            default:
                break;
        }

        if (replacement !== null) {
            edits.push({
                start: hit.start,
                end: hit.end,
                replacement,
                kind: hit.kind,
                block: hit.block,
                line: hit.line,
            });
        }
    }

    if (edits.length === 0) return null;

    // Apply from the end backwards.
    edits.sort((a, b) => b.start - a.start);
    let output = source;
    for (const edit of edits) {
        output = output.slice(0, edit.start) + edit.replacement + output.slice(edit.end);
    }

    // A file that now calls `t(...)` in its script needs `useI18n`. The
    // templates call `$t(...)`, which the i18n plugin puts in the app
    // scope on its own (globalInjection), so a template-only change does
    // not need the import.
    const needsT = edits.some((e) => e.block === 'script');
    const sourceHasT = scriptHasT(source);
    if (needsT && !sourceHasT) {
        const scriptSetupMatch = output.match(/<script setup[^>]*>/);
        if (scriptSetupMatch) {
            const insertAt = scriptSetupMatch.index + scriptSetupMatch[0].length;
            const indent = '    ';
            const hasImport = hasUseI18nImport(output);
            let block = '';
            if (!hasImport) {
                block += `\n${indent}import { useI18n } from 'vue-i18n';`;
            }
            block += `\n${indent}const { t } = useI18n();\n`;
            output = output.slice(0, insertAt) + block + output.slice(insertAt);
        } else {
            notes.push('no <script setup> block — the t() calls need a manual import');
        }
    }

    if (!dryRun) fs.writeFileSync(file, output, 'utf8');

    return { file, edits: edits.length, notes };
}

function walk(dir, out = []) {
    let entries;
    try {
        entries = fs.readdirSync(dir, { withFileTypes: true });
    } catch {
        return out;
    }
    for (const entry of entries) {
        if (entry.name.startsWith('.')) continue;
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) walk(full, out);
        else if (/\.(vue|js|ts)$/.test(entry.name)) out.push(full);
    }
    return out;
}

const roots = targets.length ? targets : Object.keys(mapping);
let totalEdits = 0;
let filesChanged = 0;

for (const root of roots) {
    const files = fs.existsSync(root) && fs.statSync(root).isDirectory() ? walk(root) : [root];
    for (const file of files) {
        const result = applyFile(file);
        if (!result) continue;
        totalEdits += result.edits;
        filesChanged++;
        for (const note of result.notes) console.error(`  ! ${file}: ${note}`);
    }
}

console.log(`${dryRun ? '[dry-run] ' : ''}${totalEdits} replacements in ${filesChanged} files`);
