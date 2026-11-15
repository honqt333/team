#!/usr/bin/env node
/**
 * Khidmah Pro — UI Constitution Checker
 *
 * Uses real AST parsing:
 *   - `@vue/compiler-sfc` (SFC blocks + Vue Template AST)
 *   - `typescript` compiler API (JS/TS AST for imports, t()/$t() calls, literals, member access)
 *
 * Supports:
 *   --changed              Inspect git working tree / untracked UI files (default)
 *   --base <ref>           Inspect files changed relative to <ref> (e.g. origin/main for PR CI)
 *   --files <p1,p2...>     Inspect specific comma-separated or repeated --files paths
 *   --all                  Inspect all files under resources/js/
 *   --include-legacy       Report pre-existing lines inside modified files too (otherwise scoped to diff hunks)
 *   --json                 Emit machine-readable JSON output
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { parse as parseSfc } from '@vue/compiler-sfc';
import ts from 'typescript';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT_DIR = path.resolve(__dirname, '..');

const DEPRECATED_IMPORTS = new Map([
    ['@/Components/PrimaryButton.vue', 'AppButton.vue (variant="primary")'],
    ['@/Components/SecondaryButton.vue', 'AppButton.vue (variant="secondary")'],
    ['@/Components/DangerButton.vue', 'AppButton.vue (variant="danger")'],
    ['@/Components/TextInput.vue', 'AppInput.vue'],
    ['@/Components/InputLabel.vue', 'FormField.vue or AppInput :label'],
    ['@/Components/InputError.vue', 'FormField.vue or AppInput :error'],
    ['@/Components/Checkbox.vue', 'AppCheckbox.vue'],
    ['@/Components/Toggle.vue', 'AppToggle.vue'],
    ['@/Components/Modal.vue', 'BaseModal.vue or ConfirmModal.vue'],
    ['@/Components/DialogModal.vue', 'BaseModal.vue or ConfirmModal.vue'],
]);

const PARALLEL_DASHBOARD_IMPORTS = [
    '@/Components/DashboardV2/common/PageHeader.vue',
    '@/Components/DashboardV2/common/StatusBadge.vue',
    '@/Components/DashboardV2/common/DashboardTabs.vue',
];

const FORBIDDEN_FONTS_RE = /\b(Cairo|Figtree|Tajawal)\b/i;
const CARAG_BRAND_RE = /\bCarag\b/i;
const ARABIC_TEXT_RE = /[\u0600-\u06FF]/;
const HARDCODED_COLOR_RE =
    /\b(?:slate|gray|zinc|neutral|stone|indigo|violet|purple|blue|red|green|yellow|orange|pink|teal|cyan|sky)-(?:50|100|200|300|400|500|600|700|800|900|950)\b/g;
const DIRECTIONAL_CLASS_RE =
    /\b(?:ml|mr|pl|pr|left|right)-(?:\d+|px|auto|full|1\/2)\b|\btext-(?:left|right)\b/g;

// NC-001: Deprecated component definitions & internal modal wrappers
const NC001_EXEMPT_FILES = new Set([
    'resources/js/Components/PrimaryButton.vue',
    'resources/js/Components/SecondaryButton.vue',
    'resources/js/Components/DangerButton.vue',
    'resources/js/Components/TextInput.vue',
    'resources/js/Components/InputLabel.vue',
    'resources/js/Components/InputError.vue',
    'resources/js/Components/Checkbox.vue',
    'resources/js/Components/Toggle.vue',
    'resources/js/Components/Modal.vue',
    'resources/js/Components/DialogModal.vue',
    'resources/js/Components/BaseModal.vue',
    'resources/js/Components/ConfirmModal.vue',
]);

// NC-003: Complex line-item editing tables and print layouts exempt from UI-012 <table> warning
const NC003_EXEMPT_TABLE_PATTERNS = [
    'resources/js/Components/Print/',
    'resources/js/Pages/Print/',
    'PurchaseOrderFormModal.vue',
    'PurchaseInvoiceFormModal.vue',
    'SalesInvoiceFormModal.vue',
    'GrnModal.vue',
    'WorkOrderCreditNoteModal.vue',
];

export function parseArgs(argv) {
    const opts = {
        mode: 'changed',
        base: null,
        files: [],
        includeLegacy: false,
        json: false,
    };

    for (let i = 0; i < argv.length; i++) {
        const arg = argv[i];
        if (arg === '--all') opts.mode = 'all';
        else if (arg === '--changed') opts.mode = 'changed';
        else if (arg === '--include-legacy') opts.includeLegacy = true;
        else if (arg === '--json') opts.json = true;
        else if (arg === '--base' && argv[i + 1]) {
            opts.mode = 'base';
            opts.base = argv[++i];
        } else if (arg === '--files' && argv[i + 1]) {
            opts.mode = 'files';
            opts.files.push(...argv[++i].split(',').map((s) => s.trim()).filter(Boolean));
        } else if (!arg.startsWith('--')) {
            opts.mode = 'files';
            opts.files.push(arg);
        }
    }
    return opts;
}

function flattenJson(obj, prefix = '', out = new Set()) {
    if (!obj || typeof obj !== 'object') return out;
    for (const [k, v] of Object.entries(obj)) {
        const next = prefix ? `${prefix}.${k}` : k;
        if (v !== null && typeof v === 'object' && !Array.isArray(v)) {
            flattenJson(v, next, out);
        } else {
            out.add(next);
        }
    }
    return out;
}

export function loadLocaleKeys(rootDir = ROOT_DIR) {
    const arPath = path.join(rootDir, 'resources/js/i18n/lang/ar.json');
    const enPath = path.join(rootDir, 'resources/js/i18n/lang/en.json');
    const arKeys = fs.existsSync(arPath) ? flattenJson(JSON.parse(fs.readFileSync(arPath, 'utf8'))) : new Set();
    const enKeys = fs.existsSync(enPath) ? flattenJson(JSON.parse(fs.readFileSync(enPath, 'utf8'))) : new Set();
    return { arKeys, enKeys };
}

function isUiSourceFile(relPath) {
    const normalized = relPath.replace(/\\/g, '/');
    if (!normalized.startsWith('resources/js/')) return false;
    if (normalized.endsWith('.test.js') || normalized.endsWith('.test.ts') || normalized.endsWith('.spec.ts')) {
        return false;
    }
    if (normalized.startsWith('resources/js/i18n/') || normalized.startsWith('resources/js/types/')) {
        return false;
    }
    if (normalized.endsWith('ziggy.js') || normalized.endsWith('page-manifest.js')) {
        return false;
    }
    return /\.(vue|js|ts|css)$/.test(normalized);
}

function walkDir(dir, rootDir, out = []) {
    if (!fs.existsSync(dir)) return out;
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            if (entry.name === 'node_modules') continue;
            walkDir(full, rootDir, out);
        } else {
            const rel = path.relative(rootDir, full).replace(/\\/g, '/');
            if (isUiSourceFile(rel)) out.push(rel);
        }
    }
    return out;
}

function gitLines(args, rootDir) {
    try {
        const out = execFileSync('git', args, { cwd: rootDir, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
        return out.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
    } catch {
        return [];
    }
}

export function getAddedLinesForFile(relPath, baseRef, rootDir = ROOT_DIR) {
    // Returns null when the whole file should be checked (untracked/new file or --all/--files/--include-legacy)
    const untracked = gitLines(['ls-files', '--others', '--exclude-standard', '--', relPath], rootDir);
    if (untracked.length > 0) return null;

    const diffArgs = baseRef
        ? ['diff', '-U0', `${baseRef}...HEAD`, '--', relPath]
        : ['diff', '-U0', 'HEAD', '--', relPath];

    let diffText = '';
    try {
        diffText = execFileSync('git', diffArgs, { cwd: rootDir, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
    } catch {
        return null;
    }

    if (!diffText.trim()) return null;

    const addedLines = new Set();
    let currentLine = 0;
    for (const line of diffText.split(/\r?\n/)) {
        const hunk = line.match(/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/);
        if (hunk) {
            currentLine = Number(hunk[1]);
            continue;
        }
        if (line.startsWith('+') && !line.startsWith('+++')) {
            addedLines.add(currentLine);
            currentLine++;
        }
    }
    return addedLines;
}

function resolveTargetFiles(opts, rootDir) {
    if (opts.mode === 'all') {
        return walkDir(path.join(rootDir, 'resources/js'), rootDir).sort();
    }
    if (opts.mode === 'files') {
        return [...new Set(opts.files.map((f) => f.replace(/\\/g, '/')))]
            .filter((f) => fs.existsSync(path.join(rootDir, f)) && isUiSourceFile(f))
            .sort();
    }
    if (opts.mode === 'base' && opts.base) {
        const changed = gitLines(['diff', '--name-only', '--diff-filter=ACMR', `${opts.base}...HEAD`], rootDir);
        return [...new Set(changed)].filter((f) => fs.existsSync(path.join(rootDir, f)) && isUiSourceFile(f)).sort();
    }
    // Default: --changed (staged + unstaged + untracked)
    const staged = gitLines(['diff', '--cached', '--name-only', '--diff-filter=ACMR'], rootDir);
    const unstaged = gitLines(['diff', '--name-only', '--diff-filter=ACMR'], rootDir);
    const untracked = gitLines(['ls-files', '--others', '--exclude-standard'], rootDir);
    return [...new Set([...staged, ...unstaged, ...untracked])]
        .filter((f) => fs.existsSync(path.join(rootDir, f)) && isUiSourceFile(f))
        .sort();
}

function isTestOnlyString(node) {
    return ts.isStringLiteral(node) || ts.isNoSubstitutionTemplateLiteral(node);
}

function stripCssComments(css) {
    return css.replace(/\/\*[\s\S]*?\*\//g, (m) => m.replace(/[^\n]/g, ' '));
}

function analyzeScriptAst(code, filePath, lineOffset, localeKeys, findings) {
    const sf = ts.createSourceFile(filePath, code, ts.ScriptTarget.Latest, true, ts.ScriptKind.TS);

    const addFinding = (severity, rule, node, message) => {
        const { line } = sf.getLineAndCharacterOfPosition(node.getStart(sf));
        const { line: endLine } = sf.getLineAndCharacterOfPosition(node.getEnd());
        findings.push({
            severity,
            rule,
            file: filePath,
            line: line + 1 + lineOffset,
            endLine: endLine + 1 + lineOffset,
            message,
            engine: 'ts-ast',
        });
    };

    const visit = (node) => {
        // 1. UI-006 & UI-021-DUP: ImportDeclaration AST
        if (ts.isImportDeclaration(node) && isTestOnlyString(node.moduleSpecifier)) {
            const spec = node.moduleSpecifier.text;
            if (!NC001_EXEMPT_FILES.has(filePath) && DEPRECATED_IMPORTS.has(spec)) {
                addFinding('ERROR', 'UI-006', node, `Deprecated import "${spec}". Use ${DEPRECATED_IMPORTS.get(spec)}.`);
            }
            if (!filePath.startsWith('resources/js/Components/DashboardV2/') && PARALLEL_DASHBOARD_IMPORTS.includes(spec)) {
                addFinding('ERROR', 'UI-021-DUP', node, `Parallel DashboardV2 component "${spec}" must not be imported in new/other modules.`);
            }
        }

        // 2. UI-016-KEY: t('key') / $t('key') static key lookup
        if (ts.isCallExpression(node)) {
            const expr = node.expression;
            const isTCall =
                (ts.isIdentifier(expr) && (expr.text === 't' || expr.text === '$t')) ||
                (ts.isPropertyAccessExpression(expr) && (expr.name.text === 't' || expr.name.text === '$t'));
            if (isTCall && node.arguments.length > 0 && isTestOnlyString(node.arguments[0])) {
                const key = node.arguments[0].text;
                if (key.includes('.')) {
                    const missingAr = !localeKeys.arKeys.has(key);
                    const missingEn = !localeKeys.enKeys.has(key);
                    if (missingAr || missingEn) {
                        const missingIn = [missingAr ? 'ar.json' : null, missingEn ? 'en.json' : null].filter(Boolean).join(' & ');
                        addFinding('ERROR', 'UI-016-KEY', node.arguments[0], `Missing i18n key "${key}" in ${missingIn}.`);
                    }
                }
            }
        }

        // 3. ARCH-APP-NAME: page.props.app?.name or page.props.app.name
        if (ts.isPropertyAccessExpression(node) && node.name.text === 'name') {
            const txt = node.getText(sf).replace(/\s+/g, '');
            if (txt.includes('props.app.name') || txt.includes('props.app?.name')) {
                addFinding('ERROR', 'ARCH-APP-NAME', node, 'Use page.props.app_name instead of page.props.app.name.');
            }
        }

        // 4. DUP-008: isRtl ? '...' : '...'
        if (ts.isConditionalExpression(node)) {
            const condText = node.condition.getText(sf);
            if (/\bisRtl\b|\blocale\s*===?\s*['"]ar['"]/.test(condText) && (isTestOnlyString(node.whenTrue) || isTestOnlyString(node.whenFalse))) {
                addFinding('WARNING', 'DUP-008', node, 'Avoid inline isRtl/locale ternary translations; move strings to ar.json/en.json.');
            }
        }

        // 5. DUP-006: Hardcoded 'SAR' fallback literal
        if (isTestOnlyString(node) && node.text.trim() === 'SAR') {
            addFinding('WARNING', 'DUP-006', node, 'Avoid hardcoded "SAR" currency literal; read from tenant currency context.');
        }

        ts.forEachChild(node, visit);
    };

    visit(sf);
}

function analyzeVueTemplateAst(astNode, filePath, localeKeys, findings, inLtr = false) {
    if (!astNode) return;

    const addFinding = (severity, rule, loc, message) => {
        findings.push({
            severity,
            rule,
            file: filePath,
            line: loc?.start?.line || 1,
            endLine: loc?.end?.line || loc?.start?.line || 1,
            message,
            engine: 'vue-template-ast',
        });
    };

    // NodeTypes: 1 = ELEMENT, 2 = TEXT, 5 = INTERPOLATION
    if (astNode.type === 1) {
        let currentLtr = inLtr;
        const isMarketingLanding = filePath.startsWith('resources/js/Pages/Public/landing/');
        const isNc003TableExempt = NC003_EXEMPT_TABLE_PATTERNS.some((p) => filePath.includes(p));

        if (astNode.tag === 'table' && !isNc003TableExempt) {
            addFinding('WARNING', 'UI-012', astNode.loc, 'Raw <table> detected; prefer DataTable.vue for data listings (NC-003 exempts print/complex edit modals).');
        }

        for (const prop of astNode.props || []) {
            // Static attribute (type 6)
            if (prop.type === 6) {
                if (prop.name === 'dir' && prop.value?.content === 'ltr') {
                    currentLtr = true;
                }
                if (prop.name === 'class' && prop.value?.content) {
                    const cls = prop.value.content;
                    if (!isMarketingLanding) {
                        const colors = cls.match(HARDCODED_COLOR_RE);
                        if (colors) {
                            addFinding('WARNING', 'UI-003', prop.loc, `Hardcoded Tailwind color(s): ${[...new Set(colors)].join(', ')}. Prefer tokens.css semantic classes.`);
                        }
                    }
                    if (!currentLtr) {
                        const tokens = cls.split(/\s+/);
                        const badDir = tokens.filter((t) => !t.startsWith('rtl:') && !t.startsWith('ltr:') && DIRECTIONAL_CLASS_RE.test(t));
                        if (badDir.length > 0) {
                            addFinding('WARNING', 'UI-005', prop.loc, `Directional class(es) [${[...new Set(badDir)].join(', ')}]; prefer logical properties (ms-/me-/ps-/pe-/start-/end-/text-start).`);
                        }
                    }
                }
            }

            // Directive (type 7)
            if (prop.type === 7) {
                if (prop.name === 'html') {
                    addFinding('ERROR', 'SEC-001', prop.loc, 'Forbidden v-html directive; use v-safe-html instead.');
                }
                if (prop.exp?.content) {
                    // Parse JS expression inside template directive/binding using TS AST
                    analyzeScriptAst(prop.exp.content, filePath, (prop.exp.loc?.start?.line || 1) - 1, localeKeys, findings);
                }
            }
        }

        for (const child of astNode.children || []) {
            analyzeVueTemplateAst(child, filePath, localeKeys, findings, currentLtr);
        }
        return;
    }

    if (astNode.type === 2) {
        const text = (astNode.content || '').trim();
        if (text) {
            if (CARAG_BRAND_RE.test(text)) {
                addFinding('ERROR', 'UI-001', astNode.loc, 'Forbidden legacy brand name "Carag" in template text.');
            }
            if (ARABIC_TEXT_RE.test(text)) {
                addFinding('WARNING', 'UI-016-TEXT', astNode.loc, `Hardcoded Arabic text in template: "${text.slice(0, 40)}". Move to ar.json/en.json.`);
            }
        }
        return;
    }

    if (astNode.type === 5 && astNode.content?.content) {
        analyzeScriptAst(astNode.content.content, filePath, (astNode.content.loc?.start?.line || 1) - 1, localeKeys, findings);
        return;
    }

    for (const child of astNode.children || []) {
        analyzeVueTemplateAst(child, filePath, localeKeys, findings, inLtr);
    }
}

export function inspectFile(relPath, localeKeys, rootDir = ROOT_DIR) {
    const absPath = path.join(rootDir, relPath);
    const source = fs.readFileSync(absPath, 'utf8');
    const findings = [];

    if (relPath.endsWith('.vue')) {
        const { descriptor, errors } = parseSfc(source, { filename: absPath });
        if (errors && errors.length > 0) {
            for (const err of errors) {
                findings.push({
                    severity: 'ERROR',
                    rule: 'SFC-PARSE',
                    file: relPath,
                    line: err.loc?.start?.line || 1,
                    message: `Vue SFC parse error: ${err.message}`,
                    engine: 'vue-sfc',
                });
            }
        }
        if (descriptor.template?.ast) {
            analyzeVueTemplateAst(descriptor.template.ast, relPath, localeKeys, findings, false);
        }
        if (descriptor.script?.content) {
            analyzeScriptAst(descriptor.script.content, relPath, (descriptor.script.loc?.start?.line || 1) - 1, localeKeys, findings);
        }
        if (descriptor.scriptSetup?.content) {
            analyzeScriptAst(descriptor.scriptSetup.content, relPath, (descriptor.scriptSetup.loc?.start?.line || 1) - 1, localeKeys, findings);
        }
        for (const style of descriptor.styles || []) {
            const lines = stripCssComments(style.content).split(/\r?\n/);
            lines.forEach((ln, idx) => {
                const m = ln.match(FORBIDDEN_FONTS_RE);
                if (m) {
                    const lineNo = (style.loc?.start?.line || 1) + idx;
                    findings.push({
                        severity: 'ERROR',
                        rule: 'UI-002',
                        file: relPath,
                        line: lineNo,
                        endLine: lineNo,
                        message: `Forbidden font "${m[1]}" in <style>; use IBM Plex Sans Arabic.`,
                        engine: 'style-scan',
                    });
                }
            });
        }
    } else if (relPath.endsWith('.js') || relPath.endsWith('.ts')) {
        analyzeScriptAst(source, relPath, 0, localeKeys, findings);
    } else if (relPath.endsWith('.css')) {
        stripCssComments(source).split(/\r?\n/).forEach((ln, idx) => {
            const m = ln.match(FORBIDDEN_FONTS_RE);
            if (m) {
                findings.push({
                    severity: 'ERROR',
                    rule: 'UI-002',
                    file: relPath,
                    line: idx + 1,
                    endLine: idx + 1,
                    message: `Forbidden font "${m[1]}" in CSS; use IBM Plex Sans Arabic.`,
                    engine: 'style-scan',
                });
            }
        });
    }

    return findings;
}

export function runConstitutionCheck(opts, rootDir = ROOT_DIR) {
    const localeKeys = loadLocaleKeys(rootDir);
    const files = resolveTargetFiles(opts, rootDir);
    const activeFindings = [];
    const legacyFindings = [];

    for (const relPath of files) {
        const fileFindings = inspectFile(relPath, localeKeys, rootDir);
        let addedLines = null;
        if (!opts.includeLegacy && (opts.mode === 'changed' || opts.mode === 'base')) {
            addedLines = getAddedLinesForFile(relPath, opts.base, rootDir);
        }

        for (const f of fileFindings) {
            let touchesAdded = addedLines === null;
            if (!touchesAdded) {
                const end = f.endLine ?? f.line;
                for (let ln = f.line; ln <= end; ln++) {
                    if (addedLines.has(ln)) {
                        touchesAdded = true;
                        break;
                    }
                }
            }
            if (touchesAdded) {
                activeFindings.push(f);
            } else {
                legacyFindings.push(f);
            }
        }
    }

    const errors = activeFindings.filter((f) => f.severity === 'ERROR');
    const warnings = activeFindings.filter((f) => f.severity === 'WARNING');

    return {
        mode: opts.mode,
        base: opts.base,
        filesChecked: files,
        errors,
        warnings,
        legacySkippedCount: legacyFindings.length,
        notVerified: [
            'UI-004 (Light/Dark visual contrast in browser)',
            'UI-019 (Responsive layout & overflow in browser)',
        ],
        notApplicable: files.length === 0 ? ['No UI files in scope'] : [],
    };
}

const isMain = process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url);
if (isMain) {
    const opts = parseArgs(process.argv.slice(2));
    const report = runConstitutionCheck(opts, ROOT_DIR);

    if (opts.json) {
        console.log(JSON.stringify(report, null, 2));
    } else {
        console.log(`Khidmah Pro UI Constitution Check (mode=${report.mode}${report.base ? `, base=${report.base}` : ''})`);
        console.log(`Files checked: ${report.filesChecked.length}`);
        if (report.filesChecked.length === 0) {
            console.log('Status: NOT APPLICABLE (no UI files in target scope).');
        } else {
            for (const f of [...report.errors, ...report.warnings]) {
                console.log(`[${f.severity}] ${f.rule} (${f.engine}) ${f.file}:${f.line} — ${f.message}`);
            }
            console.log(`\nSummary: ${report.errors.length} ERROR(s), ${report.warnings.length} WARNING(s), ${report.legacySkippedCount} legacy untouched-line finding(s) isolated.`);
            console.log(`NOT VERIFIED (requires browser/visual check): ${report.notVerified.join('; ')}`);
        }
    }

    if (report.errors.length > 0) {
        process.exitCode = 1;
    }
}
