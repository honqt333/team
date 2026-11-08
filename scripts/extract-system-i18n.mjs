/**
 * Extract the bilingual inline strings from the /system Vue pages.
 *
 * WHY A PARSER AND NOT A REGEX
 *
 * A regex over these files is wrong in ways that cost a full debugging
 * cycle last time:
 *
 *   - `//` inside a URL is read as a comment, so the scan swallows
 *     everything after it;
 *   - `>text<` across tags matches fragments of HTML as copy;
 *   - a ternary inside a ternary only yields the FIRST branch, so the
 *     other half stays hardcoded;
 *   - a bilingual pair inside a template (`locale === 'ar' ? 'a' : 'b'`)
 *     is easy to miss entirely — a pattern matches the ENGLISH half
 *     and the Arabic check then never fires.
 *
 * THE TWO PARSERS
 *
 *   - TEMPLATE: `@vue/compiler-sfc` splits the file into blocks and
 *     `@vue/compiler-dom` walks the template as an AST with exact node
 *     offsets. Every offset the compiler reports is relative to the
 *     BLOCK content, so the template offsets need the block's own
 *     start offset added.
 *
 *   - SCRIPT: `acorn` parses the real JavaScript. A regex cannot tell
 *     an Arabic string literal in a ternary apart from one in a data
 *     map, and cannot find the branches of a nested ternary in order.
 *     The AST gives every literal its exact span and its parent
 *     context, which is what the classification below needs.
 *
 * THE CLASSIFICATION (this is the actual work)
 *
 * Not every Arabic string is copy. Some are DATA the server owns:
 * a `name_ar` column value, a status the backend writes in Arabic.
 * Translating those breaks the form, because the server only ever
 * knows them in Arabic.
 *
 *   - `template_text`      static text between tags          -> COPY
 *   - `static_attr`        placeholder="..." title="..."    -> COPY
 *   - `bound_attr`         :title="cond ? 'a' : 'b'"        -> COPY
 *   - `interpolation`      {{ ... }}                        -> COPY
 *   - `script_literal`     a plain string in a ternary/if    -> COPY
 *   - `bilingual_pair`     { ar: '...', en: '...' }         -> COPY
 *   - `data_map`           { status: 'ملغي' }               -> DATA
 *   - `data_field`         'name_ar', 'description_ar'      -> DATA
 *
 * A lone letter is DATA (the Saudi plate alphabet). Translating it
 * breaks the plate.
 *
 * Usage:
 *     node scripts/extract-system-i18n.mjs            # writes /tmp/system-i18n.json
 *     node scripts/extract-system-i18n.mjs --stats    # counts only
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { globSync } from 'node:fs';
import { parse as parseSfc } from '@vue/compiler-sfc';
import { parse as parseTemplate } from '@vue/compiler-dom';
import * as acorn from 'acorn';

const ARABIC = /[؀-ۿ]/;

// ── The files ──────────────────────────────────────────────────────────────
const files = globSync('resources/js/Pages/System/**/*.vue').sort();

// ── Collectors ─────────────────────────────────────────────────────────────
const findings = [];

/** Record a string that must become a translation key. */
function report(file, kind, value, start, end, context) {
    if (typeof value !== 'string' || value.length === 0) {
        return;
    }

    if (!ARABIC.test(value)) {
        return;
    }

    // A lone letter is data (the Saudi plate alphabet), not copy.
    if (value.trim().length < 2 && !/\s/.test(value.trim())) {
        return;
    }

    findings.push({ file, kind, value, start, end, context });
}

// ── 1. Template blocks ─────────────────────────────────────────────────────
// The template is walked as an AST so we see every literal that is
// rendered, including the ones nested in a ternary inside an
// interpolation.
function walkTemplate(file, source, blockStart) {
    let ast;

    try {
        ast = parseTemplate(source, { comments: false });
    } catch {
        return;
    }

    const visit = (node) => {
        if (!node || typeof node !== 'object') {
            return;
        }

        // A static text node: the copy is the node's content.
        if (node.type === 2 /* TEXT */) {
            const decoded = node.content;
            const trimmed = decoded.trim();

            if (trimmed && ARABIC.test(trimmed)) {
                // `node.content` is the DECODED text (whitespace
                // collapsed, entities resolved) but `node.loc` covers
                // the RAW source, where the same text is broken across
                // lines and indentation. So the span must come from
                // the raw source, not from the decoded string.
                const rawText = source.slice(node.loc.start.offset, node.loc.end.offset);
                const rawTrimmed = rawText.trim();
                const rawOffset = node.loc.start.offset + rawText.indexOf(rawTrimmed);

                // The VALUE is the raw text exactly as it appears in
                // the source, so the value and the span always
                // describe the same characters. A line break inside a
                // paragraph is folded into a space by the migration.
                const value = rawTrimmed;

                report(
                    file,
                    'template_text',
                    value,
                    blockStart + rawOffset,
                    blockStart + rawOffset + rawTrimmed.length,
                    'static template text'
                );
            }
        }

        // An interpolation: {{ ... }} — its expression may hold a
        // bilingual pair or a ternary.
        if (node.type === 5 /* INTERPOLATION */ && node.content) {
            const code = node.content.content ?? '';

            if (typeof code === 'string') {
                collectExpression(file, code, blockStart + node.content.loc.start.offset);
            }
        }

        // An element: its attributes may hold copy.
        if (node.type === 1 /* ELEMENT */) {
            for (const prop of node.props ?? []) {
                // A bound attribute: :title="isAr ? 'a' : 'b'" — the
                // value is an expression node with its own location.
                if (prop.type === 7 /* DIRECTIVE */ && prop.exp) {
                    const code = prop.exp.content ?? '';

                    if (typeof code === 'string') {
                        collectExpression(
                            file,
                            code,
                            blockStart + prop.exp.loc.start.offset,
                            'bound_attr'
                        );
                    }
                }

                // A static attribute: placeholder="اختر القسم..." —
                // the value is a plain string, no expression.
                if (prop.type === 6 /* ATTRIBUTE */ && prop.value) {
                    const value = prop.value.content;

                    if (typeof value === 'string' && ARABIC.test(value)) {
                        // The compiler's span INCLUDES the quotes, so
                        // the content starts one character in.
                        const quote = value.length && prop.value.loc.end.offset - prop.value.loc.start.offset === value.length + 2 ? 1 : 0;

                        report(
                            file,
                            'static_attr',
                            value,
                            blockStart + prop.value.loc.start.offset + quote,
                            blockStart + prop.value.loc.end.offset - quote,
                            `attribute ${prop.name}`
                        );
                    }
                }
            }
        }

        for (const child of node.children ?? []) {
            visit(child);
        }
    };

    visit(ast);
}

// ── 2. Expressions (template interpolations and bound attributes) ────────
// These are JavaScript expressions, so they go through acorn too — a
// ternary in an interpolation has exactly the same shape as one in a
// script.
function collectExpression(file, code, base, kind = 'interpolation') {
    if (typeof code !== 'string' || code.trim().length === 0) {
        return;
    }

    let expr;

    try {
        // `parseExpressionAt` keeps the offsets in the SOURCE string.
        // Wrapping the code in parentheses would shift every offset by
        // one (the `(`), which silently mis-places every span.
        expr = acorn.Parser.parseExpressionAt(code, 0, { ecmaVersion: 2022 });
    } catch {
        // Not parseable on its own (rare) — fall back to a scan so the
        // string is still reported rather than silently lost.
        scanTernaryFallback(file, code, base, kind);

        return;
    }

    if (!expr) {
        return;
    }

    walkExpression(file, expr, base, kind);
}

/**
 * Walk an expression tree and report the string literals in it.
 *
 * The parent context decides whether a literal is COPY or DATA.
 */
function walkExpression(file, node, base, kind) {
    if (!node || typeof node !== 'object') {
        return;
    }

    switch (node.type) {
        case 'ConditionalExpression': {
            // Both branches are rendered — report both.
            walkExpression(file, node.test, base, kind);
            walkExpression(file, node.consequent, base, kind);
            walkExpression(file, node.alternate, base, kind);

            return;
        }

        case 'LogicalExpression': {
            walkExpression(file, node.left, base, kind);
            walkExpression(file, node.right, base, kind);

            return;
        }

        case 'Literal': {
            if (typeof node.value !== 'string') {
                return;
            }

            // `raw` carries the quotes; the value is what we translate,
            // so the span must cover the CONTENT only.
            const raw = node.raw ?? '';
            const quote = raw[0] === '"' || raw[0] === "'" ? 1 : 0;

            report(
                file,
                kind,
                node.value,
                base + node.start + quote,
                base + node.end - quote,
                'expression literal'
            );

            return;
        }

        case 'TemplateLiteral': {
            // A template literal: the Arabic may be a static chunk or
            // the tail of the literal (`${x} ر.س`).
            for (const quasi of node.quasis ?? []) {
                const cooked = quasi.value.cooked ?? '';

                if (!ARABIC.test(cooked)) {
                    continue;
                }

                // For a TemplateElement, `start`/`end` already point
                // at the CONTENT — there is no quote to strip.
                report(
                    file,
                    'script_literal',
                    cooked,
                    base + quasi.start,
                    base + quasi.end,
                    'template literal chunk'
                );
            }

            for (const expr of node.expressions ?? []) {
                walkExpression(file, expr, base, kind);
            }

            return;
        }

        case 'BinaryExpression':
        case 'UnaryExpression':
        case 'UpdateExpression':
        case 'AwaitExpression':
        case 'SpreadElement': {
            for (const key of ['left', 'right', 'argument', 'expression']) {
                if (node[key]) {
                    walkExpression(file, node[key], base, kind);
                }
            }

            return;
        }

        case 'MemberExpression': {
            walkExpression(file, node.object, base, kind);

            if (node.computed) {
                walkExpression(file, node.property, base, kind);
            }

            return;
        }

        case 'CallExpression': {
            walkExpression(file, node.callee, base, kind);

            for (const arg of node.arguments ?? []) {
                walkExpression(file, arg, base, kind);
            }

            return;
        }

        case 'ObjectExpression': {
            const names = node.properties
                .filter((p) => p.type === 'Property')
                .map((p) => p.key?.name ?? p.key?.value);

            const isBilingual = names.includes('ar') && names.includes('en');

            for (const prop of node.properties ?? []) {
                if (prop.type !== 'Property') {
                    walkExpression(file, prop, base, kind);

                    continue;
                }

                const keyName = prop.key?.name ?? prop.key?.value;

                if (isBilingual && (keyName === 'ar' || keyName === 'en')) {
                    walkExpression(file, prop.value, base, 'bilingual_pair');

                    continue;
                }

                walkExpression(file, prop.value, base, isCopyContext(keyName) ? kind : 'data_map');
            }

            return;
        }

        case 'ArrayExpression': {
            for (const el of node.elements ?? []) {
                walkExpression(file, el, base, kind);
            }

            return;
        }

        default: {
            // Unknown node: walk the generic child keys so nothing is
            // silently dropped.
            for (const key of Object.keys(node)) {
                if (key === 'type' || key === 'start' || key === 'end' || key === 'loc' || key === 'raw') {
                    continue;
                }

                const value = node[key];

                if (Array.isArray(value)) {
                    for (const item of value) {
                        if (item && typeof item === 'object' && typeof item.type === 'string') {
                            walkExpression(file, item, base, kind);
                        }
                    }
                } else if (value && typeof value === 'object' && typeof value.type === 'string') {
                    walkExpression(file, value, base, kind);
                }
            }
        }
    }
}

/**
 * Decide whether a value under this property key is copy or data.
 *
 * A property whose key is a translation-ish name (`title`, `label`,
 * `message`, `confirmText`) is copy. A property whose key names a
 * data field (`name_ar`, `description_ar`, `status`, `value`) is
 * data the server owns — the backend only ever knows it in Arabic,
 * so translating it would break the form.
 */
function isCopyContext(keyName) {
    if (typeof keyName !== 'string') {
        return true;
    }

    const dataKeys = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'status',
        'value',
        'type',
        'code',
        'key',
        'id',
        'slug',
        'color',
        'icon',
        'role',
        'permissions',
        'field',
        'column',
    ];

    return !dataKeys.includes(keyName);
}

/** A last-resort scan for an expression acorn could not parse. */
function scanTernaryFallback(file, code, base, kind) {
    const ternary = /(['"])((?:[^'"\\]|\\.)*?)\1\s*:\s*(['"])((?:[^'"\\]|\\.)*?)\3/g;
    let match;

    while ((match = ternary.exec(code)) !== null) {
        const ar = match[2];
        const en = match[4];

        if (!ARABIC.test(ar) && !ARABIC.test(en)) {
            continue;
        }

        const start = base + match.index;

        report(file, kind, ar, start + match[0].indexOf(ar), start + match[0].indexOf(ar) + ar.length, 'ternary branch');
        report(file, kind, en, start + match[0].lastIndexOf(en), start + match[0].lastIndexOf(en) + en.length, 'ternary branch');
    }
}

// ── 3. Script blocks ───────────────────────────────────────────────────────
/**
 * Walk a script AST and report the Arabic strings in it.
 *
 * The walk never returns early on a node it does not fully handle:
 * an early return would skip the node's children, and a string inside
 * a function body (a `confirm('...')` call, say) would be lost.
 *
 * The parent context decides whether a literal is COPY or DATA.
 */
function collectScript(file, source, blockStart) {
    let ast;

    try {
        ast = acorn.parse(source, {
            ecmaVersion: 2022,
            sourceType: 'module',
            locations: true,
        });
    } catch {
        return;
    }

    walkScript(file, ast, blockStart);
}

/** The children of an AST node, in a stable order. */
function childNodes(node) {
    const out = [];

    for (const key of Object.keys(node)) {
        if (key === 'type' || key === 'start' || key === 'end' || key === 'loc' || key === 'raw') {
            continue;
        }

        const value = node[key];

        if (Array.isArray(value)) {
            for (const item of value) {
                if (item && typeof item === 'object' && typeof item.type === 'string') {
                    out.push(item);
                }
            }
        } else if (value && typeof value === 'object' && typeof value.type === 'string') {
            out.push(value);
        }
    }

    return out;
}

/**
 * Walk a script node.
 *
 * `context` carries the classification of the enclosing property, so a
 * literal inside `{ name_ar: '...' }` is DATA while the same literal
 * inside `{ title: '...' }` is COPY.
 */
function walkScript(file, node, blockStart, context = 'script_literal') {
    if (!node || typeof node !== 'object') {
        return;
    }

    switch (node.type) {
        case 'Literal': {
            if (typeof node.value !== 'string') {
                return;
            }

            const raw = node.raw ?? '';
            const quote = raw[0] === '"' || raw[0] === "'" ? 1 : 0;

            report(
                file,
                context,
                node.value,
                blockStart + node.start + quote,
                blockStart + node.end - quote,
                'script literal'
            );

            return;
        }

        case 'TemplateLiteral': {
            for (const quasi of node.quasis ?? []) {
                const cooked = quasi.value.cooked ?? '';

                if (!ARABIC.test(cooked)) {
                    continue;
                }

                // A TemplateElement's span already covers the content.
                report(file, context, cooked, blockStart + quasi.start, blockStart + quasi.end, 'template literal chunk');
            }

            for (const expr of node.expressions ?? []) {
                walkScript(file, expr, blockStart, context);
            }

            return;
        }

        case 'ConditionalExpression': {
            walkScript(file, node.test, blockStart, context);
            walkScript(file, node.consequent, blockStart, context);
            walkScript(file, node.alternate, blockStart, context);

            return;
        }

        case 'ObjectExpression': {
            // A bilingual pair `{ ar: '...', en: '...' }` — both halves
            // are copy and both must be translated.
            const names = node.properties
                .filter((p) => p.type === 'Property')
                .map((p) => p.key?.name ?? p.key?.value);

            const isBilingual = names.includes('ar') && names.includes('en');

            for (const prop of node.properties ?? []) {
                if (prop.type !== 'Property') {
                    walkScript(file, prop, blockStart, context);

                    continue;
                }

                if (isBilingual && (prop.key?.name ?? prop.key?.value) === 'ar') {
                    walkScript(file, prop.value, blockStart, 'bilingual_pair');

                    continue;
                }

                if (isBilingual && (prop.key?.name ?? prop.key?.value) === 'en') {
                    walkScript(file, prop.value, blockStart, 'bilingual_pair');

                    continue;
                }

                const keyName = prop.key?.name ?? prop.key?.value;
                const childContext = isCopyContext(keyName) ? context : 'data_map';

                walkScript(file, prop.value, blockStart, childContext);
            }

            return;
        }

        default: {
            for (const child of childNodes(node)) {
                walkScript(file, child, blockStart, context);
            }
        }
    }
}

// ── 4. Walk every file ─────────────────────────────────────────────────────
for (const file of files) {
    const source = readFileSync(file, 'utf8');
    const { descriptor } = parseSfc(source);

    if (descriptor.template) {
        walkTemplate(file, descriptor.template.content, descriptor.template.loc.start.offset);
    }

    if (descriptor.script) {
        collectScript(file, descriptor.script.content, descriptor.script.loc.start.offset);
    }

    if (descriptor.scriptSetup) {
        collectScript(file, descriptor.scriptSetup.content, descriptor.scriptSetup.loc.start.offset);
    }
}

// ── 5. Report ──────────────────────────────────────────────────────────────
const byFile = new Map();

for (const f of findings) {
    if (!byFile.has(f.file)) {
        byFile.set(f.file, []);
    }

    byFile.get(f.file).push(f);
}

const summary = {
    total: findings.length,
    files: byFile.size,
    kinds: {},
    detail: [],
};

for (const f of findings) {
    summary.kinds[f.kind] = (summary.kinds[f.kind] ?? 0) + 1;
}

for (const [file, items] of byFile) {
    summary.detail.push({
        file,
        count: items.length,
        strings: items.map((i) => ({
            kind: i.kind,
            value: i.value,
            start: i.start,
            end: i.end,
            context: i.context,
        })),
    });
}

writeFileSync('/tmp/system-i18n.json', JSON.stringify(summary, null, 2));

console.error(`extracted ${summary.total} strings across ${summary.files} files`);
console.error(`  kinds: ${JSON.stringify(summary.kinds)}`);

for (const d of summary.detail) {
    console.error(`  ${d.file.padEnd(58)} ${d.count}`);
}
