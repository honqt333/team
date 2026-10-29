#!/usr/bin/env node
/**
 * Extract the Arabic literals that are still hardcoded in the Vue sources,
 * and classify them by the context they appear in.
 *
 * The script part is parsed with acorn, so a `//` inside a template
 * literal (a URL, for instance) is never mistaken for a comment and the
 * offsets are exact. The template part is parsed with the official Vue
 * compiler, so the text nodes and the bound attributes are known
 * precisely.
 *
 * The context decides whether a string can become a translation key:
 *
 *   template_text     a text node in the template        -> {{ $t('ns.key') }}
 *   template_attr     a static attribute (placeholder,     -> :placeholder="$t('ns.key')"
 *                     title, label, aria-label ...)
 *   interpolation     `{{ cond ? 'ar' : 'en' }}`           -> {{ $t('ns.key') }}
 *   script_literal    a string inside <script>            -> t('ns.key')
 *   script_template   the literal parts of a template      -> t('ns.key')
 *   ui_label          the value of a label-ish property    -> t('ns.key')
 *   bilingual_pair    `cond ? 'ar' : 'en'` in a script      -> t('ns.key')
 *   data_field        a domain value the server compares   -> NOT translatable
 *   data_value        a value compared against server data -> NOT translatable
 *
 * Usage:
 *   node scripts/i18n-extract.mjs [--json] [paths ...]
 */

import fs from 'fs';
import path from 'path';
import * as acorn from 'acorn';
import { parse as parseSFC } from '@vue/compiler-sfc';
import { parse as parseTemplate } from '@vue/compiler-dom';

const ARABIC = /[\u0600-\u06FF\u0750-\u077F\uFB50-\uFDFF\uFE70-\uFEFF]/;
const SKIP_DIRS = new Set(['i18n', 'node_modules', 'dist', 'build', '.git']);

/**
 * Properties whose value is UI copy, as opposed to a domain value the
 * server also knows (a status slug, a bilingual database column, the
 * key of a lookup map).
 */
const UI_LABEL_PROPERTIES = new Set([
    'title',
    'label',
    'message',
    'summary',
    'description',
    'desc',
    'tip',
    'confirmText',
    'cancelText',
    'okText',
    'text',
    'placeholder',
    'heading',
    'subtitle',
    'name',
    'helper',
    'hint',
    'note',
    'emptyText',
    'emptyMessage',
    'buttonText',
    'statusText',
    'displayName',
    'tooltip',
    'successMessage',
    'errorMessage',
    'warningMessage',
    'infoMessage',
    'confirmMessage',
    'bodyText',
    'body',
]);

/** Properties that hold a domain value rather than visible copy. */
const DATA_PROPERTIES = new Set([
    'value',
    'key',
    'id',
    'type',
    'status',
    'method',
    'code',
    'slug',
    'badge_ar',
    'badge_en',
    'name_ar',
    'name_en',
    'description_ar',
    'description_en',
    'title_ar',
    'title_en',
    'q',
    'a',
]);

/** Keys of the lookup maps that map a document type to its printed name. */
const MAP_KEYS = new Set([
    'closed',
    'open',
    'quotation',
    'proforma_invoice',
    'work_order',
    'receipt',
    'checklist',
    'delivery_note',
    'condition_report',
    'payments',
    'invoice',
    'sales_invoice',
    'purchase_invoice',
    'credit_note',
]);

/** The property a literal is assigned to, when it is an object value. */
function assignedProperty(source, start) {
    const lineStart = source.lastIndexOf('\n', start - 1) + 1;
    const before = source.slice(lineStart, start);
    const match = before.match(/([\w.]+)\s*:\s*$/);
    return match ? match[1] : null;
}

function contextBefore(source, start) {
    const lineStart = source.lastIndexOf('\n', start - 1) + 1;
    return source.slice(lineStart, start);
}

function contextAfter(source, end) {
    const lineEnd = source.indexOf('\n', end);
    return source.slice(end, lineEnd === -1 ? source.length : lineEnd);
}

/**
 * A literal that is compared against server data is a value, not a
 * label. It must not become a translation key.
 */
function looksLikeComparison(source, start, end) {
    const before = contextBefore(source, start);
    const after = contextAfter(source, end);
    return (
        /===\s*$/.test(before) ||
        /!==\s*$/.test(before) ||
        /^\s*==\s*/.test(after) ||
        /^\s*!=\s*/.test(after) ||
        /^\s*case\s+/.test(before) ||
        /\bin\s*\[\s*$/.test(before) ||
        /^\s*:\s*[^:]*$/.test(after) && /^\s*[?:]\s*$/.test(before)
    );
}

/** `cond ? 'arabic' : 'english'` — the English half is right there. */
function isBilingualPair(source, start, end) {
    const before = contextBefore(source, start);
    const after = contextAfter(source, end);
    const questionBefore = /(\?|:\s*)\s*$/.test(before);
    const colonAfter = /^\s*:/.test(after);
    return questionBefore && colonAfter;
}

function classifyScriptLiteral(source, start, end) {
    if (isBilingualPair(source, start, end)) return 'bilingual_pair';
    if (looksLikeComparison(source, start, end)) return 'data_value';

    const property = assignedProperty(source, start);
    if (property) {
        if (UI_LABEL_PROPERTIES.has(property)) return 'ui_label';
        if (DATA_PROPERTIES.has(property)) return 'data_field';
        if (MAP_KEYS.has(property)) return 'data_field';
        return 'ui_label';
    }

    return 'script_literal';
}

/**
 * A literal that is only a numeral, a punctuation mark or an emoji is
 * not user-facing copy — it is data (a digit map, a separator). It must
 * not become a translation key.
 */
const NUMERAL_ONLY = /^[\u0660-\u0669\u06F0-\u06F9\u0030-\u0039\s]+$/;
const PUNCTUATION_ONLY = /^[^\p{L}\p{N}]+$/u;

function isUserFacingCopy(value) {
    const trimmed = value.trim();
    if (trimmed.length === 0) return false;
    if (NUMERAL_ONLY.test(trimmed)) return false;
    if (PUNCTUATION_ONLY.test(trimmed)) return false;
    return true;
}

/** Every node of the acorn tree, with its parent. */
function walkAst(node, parent, visit) {
    if (!node || typeof node !== 'object') return;
    if (Array.isArray(node)) {
        for (const child of node) walkAst(child, parent, visit);
        return;
    }
    if (typeof node.type === 'string') visit(node, parent);
    for (const key of Object.keys(node)) {
        if (key === 'loc' || key === 'start' || key === 'end' || key === 'type') continue;
        const value = node[key];
        if (value && typeof value === 'object') walkAst(value, node, visit);
    }
}

/**
 * The conditional expressions of a script, read from the AST. Each one
 * gives the exact span of `cond ? 'ar' : 'en'`, so the whole expression
 * can be replaced without guessing where it starts.
 */
function conditionalExpressions(ast) {
    const found = [];
    walkAst(ast, null, (node) => {
        if (node.type !== 'ConditionalExpression') return;
        const consequent = node.consequent;
        const alternate = node.alternate;
        if (!consequent || !alternate) return;
        if (consequent.type !== 'Literal' || alternate.type !== 'Literal') return;
        if (typeof consequent.value !== 'string' || typeof alternate.value !== 'string') return;
        if (!ARABIC.test(consequent.value)) return;
        found.push({
            start: node.start,
            end: node.end,
            arabic: consequent.value,
            english: alternate.value,
        });
    });
    return found;
}

function extractScript(block, raw) {
    const found = [];
    const content = block.content;
    const blockStart = block.loc.start.offset;

    let ast;
    try {
        ast = acorn.parse(content, { ecmaVersion: 'latest', sourceType: 'module' });
    } catch {
        return found;
    }

    // The conditional expressions are collected first: they own the
    // whole `cond ? 'ar' : 'en'` span, so the literals inside them must
    // not be reported a second time.
    const conditionals = conditionalExpressions(ast);
    const conditionalRanges = conditionals.map((c) => [c.start, c.end]);
    const insideConditional = (start, end) =>
        conditionalRanges.some(([from, to]) => start >= from && end <= to);

    for (const conditional of conditionals) {
        const start = blockStart + conditional.start;
        const end = blockStart + conditional.end;
        found.push({
            kind: 'bilingual_pair',
            start,
            end,
            value: conditional.arabic,
            line: raw.slice(0, start).split('\n').length,
            block: 'script',
            snippet: content
                .slice(Math.max(0, conditional.start - 40), Math.min(content.length, conditional.end + 40))
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, 160),
        });
    }

    walkAst(ast, null, (node, parent) => {
        // A plain string literal.
        if (node.type === 'Literal' && typeof node.value === 'string') {
            if (!ARABIC.test(node.value)) return;
            if (!isUserFacingCopy(node.value)) return;
            if (insideConditional(node.start, node.end)) return;
            const start = blockStart + node.start;
            const end = blockStart + node.end;
            found.push({
                kind: classifyScriptLiteral(content, node.start, node.end),
                start,
                end,
                value: node.value,
                line: raw.slice(0, start).split('\n').length,
                block: 'script',
                snippet: content
                    .slice(Math.max(0, node.start - 40), Math.min(content.length, node.end + 40))
                    .replace(/\s+/g, ' ')
                    .trim()
                    .slice(0, 160),
            });
            return;
        }

        // The literal parts of a template literal.
        if (node.type === 'TemplateElement') {
            const raw_ = node.value.raw;
            if (!ARABIC.test(raw_)) return;
            if (!isUserFacingCopy(raw_)) return;
            if (insideConditional(node.start, node.end)) return;
            const start = blockStart + node.start;
            const end = blockStart + node.end;
            // The template element sits between two backticks; the whole
            // template is replaced, so the span covers the backticks too.
            const fullStart = content.lastIndexOf('`', node.start) + 1;
            const fullEnd = content.indexOf('`', node.end) + 1;
            found.push({
                kind: 'script_template',
                start: blockStart + (fullStart > 0 ? fullStart - 1 : node.start),
                end: blockStart + fullEnd,
                value: raw_,
                line: raw.slice(0, start).split('\n').length,
                block: 'script',
                snippet: content
                    .slice(Math.max(0, node.start - 40), Math.min(content.length, node.end + 40))
                    .replace(/\s+/g, ' ')
                    .trim()
                    .slice(0, 160),
            });
        }
    });

    return found;
}

/**
 * The Arabic branches of the conditional expressions in an expression
 * string, read from the AST. A condition may itself be a ternary, so
 * the branches are found by walking the tree rather than by matching a
 * pattern.
 */
function arabicBranches(expression) {
    const found = [];
    let ast;
    try {
        ast = acorn.parse(`(${expression})`, { ecmaVersion: 'latest' });
    } catch {
        return found;
    }

    walkAst(ast, null, (node) => {
        if (node.type !== 'ConditionalExpression') return;
        for (const branch of [node.consequent, node.alternate]) {
            if (!branch || branch.type !== 'Literal') continue;
            if (typeof branch.value !== 'string') continue;
            if (!ARABIC.test(branch.value)) continue;
            if (!isUserFacingCopy(branch.value)) continue;
            found.push(branch.value);
        }
    });

    return found;
}

function extractTemplate(templateContent, raw, templateStart) {
    const found = [];
    let ast;
    try {
        ast = parseTemplate(templateContent, { comments: false });
    } catch {
        return found;
    }

    const walk = (node) => {
        if (node.type === 2 /* TEXT */) {
            const text = node.content;
            if (ARABIC.test(text) && text.trim().length > 0 && isUserFacingCopy(text)) {
                // The text may carry an interpolation of its own
                // (`صالح حتى: {{ date }}`) — the copy is the part before it.
                const copy = text.split('{{')[0].trim();
                if (!copy || !ARABIC.test(copy)) return;
                found.push({
                    kind: 'template_text',
                    start: templateStart + node.loc.start.offset,
                    end: templateStart + node.loc.end.offset,
                    value: copy,
                    block: 'template',
                });
            }
        }
        if (node.type === 5 /* INTERPOLATION */) {
            // The expression keeps its raw source, so the literals inside
            // it are still visible.
            const expression = node.content && node.content.content;
            if (typeof expression === 'string' && ARABIC.test(expression)) {
                // `t('key', 'نص احتياطي')` is already a translation call:
                // the key exists and the Arabic is only a fallback.
                if (/^\s*t\(\s*['"`][^'"`]+['"`]\s*,/.test(expression)) {
                    return;
                }
                // `cond ? value : 'نص احتياطي'` — the copy that shows when
                // the condition does not hold.
                const fallback = expression.match(/\|\|\s*'([^']*)'/);
                if (fallback && ARABIC.test(fallback[1]) && isUserFacingCopy(fallback[1])) {
                    found.push({
                        kind: 'interpolation_fallback',
                        start: templateStart + node.loc.start.offset,
                        end: templateStart + node.loc.end.offset,
                        value: fallback[1],
                        block: 'template',
                    });
                }
                // `cond ? 'نص' : other` — the copy of the ternary branch.
                // The branch may sit after another expression:
                // `cond ? $t('other') : 'نص'`.
                // The Arabic branch of a ternary: it may sit right after
                // the `?` or after the `:` of another ternary.
                const branch = expression.match(
                    /\?[^?:]*?:\s*'([^']*)'|\?\s*'([^']*)'\s*:/
                );
                const branchText = branch && (branch[1] || branch[2]);
                if (branchText && ARABIC.test(branchText) && isUserFacingCopy(branchText)) {
                    found.push({
                        kind: 'interpolation_branch',
                        start: templateStart + node.loc.start.offset,
                        end: templateStart + node.loc.end.offset,
                        value: branchText,
                        block: 'template',
                    });
                }
                // `{{ `${x} ر.س` }}` — the Arabic is the tail of a template
                // literal.
                const literal = expression.match(/`([^`]*)`/);
                if (literal && ARABIC.test(literal[1]) && isUserFacingCopy(literal[1])) {
                    found.push({
                        kind: 'interpolation_literal',
                        start: templateStart + node.loc.start.offset,
                        end: templateStart + node.loc.end.offset,
                        value: literal[1],
                        block: 'template',
                    });
                }
                // The plain expression is only reported when it carries
                // no more specific copy of its own.
                if (!fallback && !literal && !branch) {
                    found.push({
                        kind: 'interpolation',
                        start: templateStart + node.loc.start.offset,
                        end: templateStart + node.loc.end.offset,
                        value: expression,
                        block: 'template',
                    });
                }
            }
        }
        if (node.type === 6 /* ELEMENT */) {
            for (const prop of node.props || []) {
                if (
                    prop.type === 6 /* ATTRIBUTE */ &&
                    prop.value &&
                    ARABIC.test(prop.value.content)
                ) {
                    found.push({
                        kind: 'template_attr',
                        attr: prop.name,
                        start: templateStart + prop.value.loc.start.offset,
                        end: templateStart + prop.value.loc.end.offset,
                        value: prop.value.content,
                        block: 'template',
                    });
                }
            }
        }
        for (const child of node.children || []) walk(child);
        if (node.type === 1 /* ELEMENT */) {
            for (const branch of node.branches || []) {
                for (const child of branch.children || []) walk(child);
            }
        }
    };

    walk(ast);
    return found;
}

function scanFile(file) {
    const raw = fs.readFileSync(file, 'utf8');
    const { descriptor } = parseSFC(raw, { filename: file });
    const results = [];

    if (descriptor.template) {
        results.push(...extractTemplate(descriptor.template.content, raw, descriptor.template.loc.start.offset));
    }
    for (const block of [descriptor.script, descriptor.scriptSetup]) {
        if (!block) continue;
        results.push(...extractScript(block, raw));
    }

    // The same span may be reported twice (a template element and its
    // enclosing literal). Keep the most specific one: a fallback copy or
    // a literal tail says more than the raw expression.
    const specificity = {
        interpolation_fallback: 0,
        interpolation_branch: 0,
        interpolation_literal: 1,
        interpolation: 2,
    };
    results.sort((a, b) => {
        const byStart = a.start - b.start;
        if (byStart !== 0) return byStart;
        const aSpec = specificity[a.kind] ?? 3;
        const bSpec = specificity[b.kind] ?? 3;
        if (aSpec !== bSpec) return aSpec - bSpec;
        return a.end - b.end;
    });
    const unique = [];
    let lastEnd = -1;
    for (const hit of results) {
        if (hit.start < lastEnd) continue;
        unique.push(hit);
        lastEnd = hit.end;
    }

    return unique.map((hit) => ({
        ...hit,
        line: hit.line || raw.slice(0, hit.start).split('\n').length,
    }));
}

function walk(dir, out = []) {
    let entries;
    try {
        entries = fs.readdirSync(dir, { withFileTypes: true });
    } catch {
        return out;
    }
    for (const entry of entries) {
        if (entry.name.startsWith('.') || SKIP_DIRS.has(entry.name)) continue;
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) walk(full, out);
        else if (/\.(vue|js|ts)$/.test(entry.name)) out.push(full);
    }
    return out;
}

function main() {
    const args = process.argv.slice(2);
    const asJson = args.includes('--json');
    const targets = args.filter((a) => a !== '--json');
    const roots = targets.length ? targets : ['resources/js'];

    const inventory = {};
    const counts = {};
    let total = 0;

    for (const root of roots) {
        const files = fs.statSync(root).isDirectory() ? walk(root) : [root];
        for (const file of files) {
            let hits;
            try {
                hits = scanFile(file);
            } catch (err) {
                console.error(`ERROR ${file}: ${err.message}`);
                continue;
            }
            if (hits.length === 0) continue;
            inventory[file] = hits;
            total += hits.length;
            for (const hit of hits) {
                counts[hit.kind] = (counts[hit.kind] || 0) + 1;
            }
        }
    }

    if (asJson) {
        console.log(JSON.stringify(inventory, null, 2));
        return;
    }

    console.log(`${total} Arabic literals in ${Object.keys(inventory).length} files\n`);
    console.log('by context:');
    for (const [kind, count] of Object.entries(counts).sort((a, b) => b[1] - a[1])) {
        console.log(`  ${kind.padEnd(18)} ${count}`);
    }

    const perFile = Object.entries(inventory)
        .map(([file, hits]) => [hits.length, file])
        .sort((a, b) => b[0] - a[0]);
    console.log('\nper file:');
    for (const [count, file] of perFile) {
        console.log(`  ${String(count).padStart(4)}  ${file}`);
    }
}

main();
