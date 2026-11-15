import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { inspectFile, parseArgs, runConstitutionCheck } from './check-ui-constitution.mjs';

function makeTempProject(files, withGit = false) {
    const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'ui-const-'));
    const locales = {
        'resources/js/i18n/lang/ar.json': JSON.stringify({
            common: { save: 'حفظ', app_name: 'خدمة برو' },
            work_orders: { title: 'أوامر العمل' },
        }),
        'resources/js/i18n/lang/en.json': JSON.stringify({
            common: { save: 'Save', app_name: 'Khidmah Pro' },
            work_orders: { title: 'Work Orders' },
        }),
    };
    for (const [rel, content] of Object.entries({ ...locales, ...files })) {
        const full = path.join(tmp, rel);
        fs.mkdirSync(path.dirname(full), { recursive: true });
        fs.writeFileSync(full, content, 'utf8');
    }
    if (withGit) {
        execFileSync('git', ['init', '-b', 'main'], { cwd: tmp, stdio: 'ignore' });
        execFileSync('git', ['config', 'user.email', 'test@example.com'], { cwd: tmp, stdio: 'ignore' });
        execFileSync('git', ['config', 'user.name', 'Test'], { cwd: tmp, stdio: 'ignore' });
        execFileSync('git', ['add', '.'], { cwd: tmp, stdio: 'ignore' });
        execFileSync('git', ['commit', '-m', 'init'], { cwd: tmp, stdio: 'ignore' });
    }
    return tmp;
}

const sampleLocales = {
    arKeys: new Set(['common.save', 'common.app_name', 'work_orders.title']),
    enKeys: new Set(['common.save', 'common.app_name', 'work_orders.title']),
};

test('parseArgs supports --changed, --all, --files, and --base', () => {
    assert.equal(parseArgs([]).mode, 'changed');
    assert.equal(parseArgs(['--all']).mode, 'all');
    assert.deepEqual(parseArgs(['--files', 'a.vue,b.vue']), {
        mode: 'files',
        base: null,
        files: ['a.vue', 'b.vue'],
        includeLegacy: false,
        json: false,
    });
    assert.deepEqual(parseArgs(['--base', 'origin/main']), {
        mode: 'base',
        base: 'origin/main',
        files: [],
        includeLegacy: false,
        json: false,
    });
});

test('compliant Vue SFC passes with zero errors and zero warnings', () => {
    const tmp = makeTempProject({
        'resources/js/Pages/Warranties/Index.vue': `<script setup>
import { useI18n } from 'vue-i18n';
import AppButton from '@/Components/App/AppButton.vue';
const { t } = useI18n();
const title = t('work_orders.title');
</script>
<template>
    <div class="bg-surface text-text-primary ms-4 text-start">
        <AppButton variant="primary">{{ $t('common.save') }}</AppButton>
    </div>
</template>`,
    });

    const findings = inspectFile('resources/js/Pages/Warranties/Index.vue', sampleLocales, tmp);
    assert.equal(findings.length, 0);
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('violating Vue SFC detects ERROR and WARNING rules accurately', () => {
    const tmp = makeTempProject({
        'resources/js/Pages/Bad/Index.vue': `<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DashHeader from '@/Components/DashboardV2/common/PageHeader.vue';
const label = isRtl ? 'عربي' : 'English';
const cur = 'SAR';
const badKey = t('nonexistent.missing_key');
const appName = page.props.app.name;
</script>
<template>
    <div class="bg-gray-100 ml-4" v-html="rawHtml">
        Carag نص عربي ثابت
        <table><tr><td>1</td></tr></table>
    </div>
</template>
<style>
.x { font-family: 'Cairo', sans-serif; }
</style>`,
    });

    const findings = inspectFile('resources/js/Pages/Bad/Index.vue', sampleLocales, tmp);
    const rules = new Set(findings.map((f) => `${f.severity}:${f.rule}`));

    assert.ok(rules.has('ERROR:UI-006'));
    assert.ok(rules.has('ERROR:UI-021-DUP'));
    assert.ok(rules.has('ERROR:UI-016-KEY'));
    assert.ok(rules.has('ERROR:ARCH-APP-NAME'));
    assert.ok(rules.has('ERROR:SEC-001'));
    assert.ok(rules.has('ERROR:UI-001'));
    assert.ok(rules.has('ERROR:UI-002'));
    assert.ok(rules.has('WARNING:DUP-008'));
    assert.ok(rules.has('WARNING:DUP-006'));
    assert.ok(rules.has('WARNING:UI-003'));
    assert.ok(rules.has('WARNING:UI-005'));
    assert.ok(rules.has('WARNING:UI-016-TEXT'));
    assert.ok(rules.has('WARNING:UI-012'));
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('Vue HTML comments and CSS block comments do not trigger false positives while real CSS fonts are caught', () => {
    const tmp = makeTempProject({
        'resources/js/Design/typography.css': `/* Do not use Tajawal or Cairo here */\nhtml { font-family: var(--font-family-sans); }\n`,
        'resources/js/Design/bad-font.css': `/* comment */\nbody { font-family: 'Tajawal', sans-serif; }\n`,
        'resources/js/Pages/Commented.vue': `<template>
    <!-- Carag نص عربي في تعليق v-html -->
    <div class="bg-surface">{{ $t('common.save') }}</div>
</template>
<style>
/* Tajawal mention in comment */
.ok { color: inherit; }
</style>`,
    });

    assert.equal(inspectFile('resources/js/Design/typography.css', sampleLocales, tmp).length, 0);
    assert.equal(inspectFile('resources/js/Pages/Commented.vue', sampleLocales, tmp).length, 0);
    const badCss = inspectFile('resources/js/Design/bad-font.css', sampleLocales, tmp);
    assert.equal(badCss.length, 1);
    assert.equal(badCss[0].rule, 'UI-002');
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('exemptions NC-001, NC-002, NC-003, and NC-004 are respected', () => {
    const tmp = makeTempProject({
        'resources/js/Components/BaseModal.vue': `<script setup>
import Modal from '@/Components/Modal.vue';
</script>
<template><Modal :show="true" /></template>`,
        'resources/js/Pages/Print/InvoicePrint.vue': `<template><table><tr><td>{{ $t('common.save') }}</td></tr></table></template>`,
        'resources/js/Pages/Public/landing/HeroSection.vue': `<template><div class="bg-slate-900">{{ $t('common.app_name') }}</div></template>`,
        'resources/js/Pages/Test/LtrAndRtl.vue': `<template><div class="rtl:mr-2" dir="ltr"><input class="text-left ml-2" /></div></template>`,
    });

    assert.equal(inspectFile('resources/js/Components/BaseModal.vue', sampleLocales, tmp).length, 0);
    assert.equal(inspectFile('resources/js/Pages/Print/InvoicePrint.vue', sampleLocales, tmp).length, 0);
    assert.equal(inspectFile('resources/js/Pages/Public/landing/HeroSection.vue', sampleLocales, tmp).length, 0);
    assert.equal(inspectFile('resources/js/Pages/Test/LtrAndRtl.vue', sampleLocales, tmp).length, 0);
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('partially modified legacy file isolates untouched legacy violations and catches multi-line new additions', () => {
    const tmp = makeTempProject(
        {
            'resources/js/Pages/Legacy.vue': `<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
</script>
<template>
    <div class="bg-gray-100">
        <PrimaryButton>{{ $t('common.save') }}</PrimaryButton>
    </div>
</template>`,
        },
        true
    );

    // Edit only template lines by adding a multi-line element with a missing i18n key on line 7-8
    const updated = `<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
</script>
<template>
    <div class="bg-gray-100">
        <PrimaryButton>{{ $t('common.save') }}</PrimaryButton>
        <span
            class="bg-slate-200">{{
                $t('missing.new_multiline_key')
            }}</span>
    </div>
</template>`;
    fs.writeFileSync(path.join(tmp, 'resources/js/Pages/Legacy.vue'), updated, 'utf8');

    const report = runConstitutionCheck(parseArgs(['--changed']), tmp);
    assert.equal(report.filesChecked.length, 1);
    assert.equal(report.errors.length, 1);
    assert.equal(report.errors[0].rule, 'UI-016-KEY');
    assert.ok(report.legacySkippedCount >= 2); // legacy PrimaryButton + bg-gray-100 isolated
    assert.ok(report.warnings.some((w) => w.rule === 'UI-003')); // new bg-slate-200 caught
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('handles --base (PR diff), untracked, renamed, deleted files, and ignores unrelated branches', () => {
    const tmp = makeTempProject(
        {
            'resources/js/Pages/ToDelete.vue': `<template><div class="bg-gray-100">x</div></template>`,
            'resources/js/Pages/ToRename.vue': `<template><div class="bg-surface">{{ $t('common.save') }}</div></template>`,
        },
        true
    );

    // Create an unrelated branch with violations that must NOT affect main/PR branch
    execFileSync('git', ['checkout', '-b', 'unrelated-other-branch'], { cwd: tmp, stdio: 'ignore' });
    fs.writeFileSync(
        path.join(tmp, 'resources/js/Pages/OtherBranchOnly.vue'),
        `<template><div v-html="x">Carag</div></template>`,
        'utf8'
    );
    execFileSync('git', ['add', '.'], { cwd: tmp, stdio: 'ignore' });
    execFileSync('git', ['commit', '-m', 'other'], { cwd: tmp, stdio: 'ignore' });

    // Switch back to main and create feature/pr branch
    execFileSync('git', ['checkout', 'main'], { cwd: tmp, stdio: 'ignore' });
    execFileSync('git', ['checkout', '-b', 'feature/pr'], { cwd: tmp, stdio: 'ignore' });

    // Delete ToDelete.vue, rename ToRename.vue, add BrandNew.vue
    fs.unlinkSync(path.join(tmp, 'resources/js/Pages/ToDelete.vue'));
    fs.renameSync(
        path.join(tmp, 'resources/js/Pages/ToRename.vue'),
        path.join(tmp, 'resources/js/Pages/Renamed.vue')
    );
    fs.writeFileSync(
        path.join(tmp, 'resources/js/Pages/BrandNew.vue'),
        `<template><div class="bg-surface">{{ $t('common.save') }}</div></template>`,
        'utf8'
    );
    execFileSync('git', ['add', '-A'], { cwd: tmp, stdio: 'ignore' });
    execFileSync('git', ['commit', '-m', 'pr changes'], { cwd: tmp, stdio: 'ignore' });

    // --base main should check Renamed.vue and BrandNew.vue, skip deleted ToDelete.vue, ignore unrelated-other-branch
    const prReport = runConstitutionCheck(parseArgs(['--base', 'main']), tmp);
    assert.deepEqual(prReport.filesChecked, [
        'resources/js/Pages/BrandNew.vue',
        'resources/js/Pages/Renamed.vue',
    ]);
    assert.equal(prReport.errors.length, 0);
    assert.equal(prReport.warnings.length, 0);

    // Also test untracked file in --changed mode
    fs.writeFileSync(
        path.join(tmp, 'resources/js/Pages/UntrackedBad.vue'),
        `<template><div v-html="x"></div></template>`,
        'utf8'
    );
    const changedReport = runConstitutionCheck(parseArgs(['--changed']), tmp);
    assert.deepEqual(changedReport.filesChecked, ['resources/js/Pages/UntrackedBad.vue']);
    assert.equal(changedReport.errors.length, 1);
    assert.equal(changedReport.errors[0].rule, 'SEC-001');

    // --all mode
    const allReport = runConstitutionCheck(parseArgs(['--all']), tmp);
    assert.equal(allReport.filesChecked.length, 3);

    fs.rmSync(tmp, { recursive: true, force: true });
});

test('detects hardcoded hex colors (UI-003-HEX) while exempting tokens.css, marketing landing, CSS IDs, and url(#clip)', () => {
    const tmp = makeTempProject({
        'resources/js/Design/tokens.css': `:root {\n  --color-brand-primary: #2563eb;\n}\n`,
        'resources/js/Pages/Public/landing/Hero.vue': `<template><div class="bg-[#0f172a]">{{ $t('common.app_name') }}</div></template>`,
        'resources/js/Pages/SafeCss.vue': `<template><div class="bg-surface" style="color: var(--color-text-primary)">{{ $t('common.save') }}</div></template>
<style>
#app-shell {
  background: var(--color-surface);
  clip-path: url(#clip0);
}
</style>`,
        'resources/js/Pages/HexBad.vue': `<template>
    <div class="bg-[#2563eb] text-[#fff]" style="border-color: #dc2626">{{ $t('common.save') }}</div>
</template>
<style>
.bad { color: #0f172a; }
</style>`,
    });

    assert.equal(inspectFile('resources/js/Design/tokens.css', sampleLocales, tmp).length, 0);
    assert.equal(inspectFile('resources/js/Pages/Public/landing/Hero.vue', sampleLocales, tmp).length, 0);
    assert.equal(inspectFile('resources/js/Pages/SafeCss.vue', sampleLocales, tmp).length, 0);

    const bad = inspectFile('resources/js/Pages/HexBad.vue', sampleLocales, tmp);
    const hexFindings = bad.filter((f) => f.rule === 'UI-003-HEX');
    assert.equal(hexFindings.length, 3);
    assert.ok(hexFindings.every((f) => f.severity === 'ERROR'));
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('detects design token definitions outside tokens.css (UI-TOKEN-OUTSIDE) without flagging var() usage', () => {
    const tmp = makeTempProject({
        'resources/js/Design/tokens.css': `:root {\n  --color-surface: #ffffff;\n  --space-4: 1rem;\n}\n`,
        'resources/js/Design/custom.css': `:root {\n  --color-brand-alt: #111111;\n  --space-custom: 2rem;\n}\n.box { background: var(--color-surface); }\n`,
        'resources/js/Pages/TokenInVue.vue': `<template><div class="bg-surface">{{ $t('common.save') }}</div></template>
<style>
.card {
  --radius-card: 12px;
  padding: var(--space-4);
}
</style>`,
    });

    assert.equal(inspectFile('resources/js/Design/tokens.css', sampleLocales, tmp).length, 0);

    const badCss = inspectFile('resources/js/Design/custom.css', sampleLocales, tmp);
    assert.equal(badCss.filter((f) => f.rule === 'UI-TOKEN-OUTSIDE').length, 2);

    const badVue = inspectFile('resources/js/Pages/TokenInVue.vue', sampleLocales, tmp);
    assert.equal(badVue.filter((f) => f.rule === 'UI-TOKEN-OUTSIDE').length, 1);
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('detects unauthorized React/UI libraries in imports and package.json (UI-UNAUTHORIZED-LIB)', () => {
    const tmp = makeTempProject({
        'package.json': JSON.stringify({
            name: 'test',
            dependencies: { vue: '^3.4.0', react: '^19.0.0' },
            devDependencies: { '@radix-ui/react-dialog': '^1.0.0', vitest: '^2.1.5' },
        }, null, 2),
        'resources/js/Pages/ReactBad.vue': `<script setup>
import React from 'react';
import { Dialog } from '@radix-ui/react-dialog';
import { Button } from 'shadcn-ui';
</script>
<template><div class="bg-surface">{{ $t('common.save') }}</div></template>`,
    });

    const pkgFindings = inspectFile('package.json', sampleLocales, tmp);
    assert.equal(pkgFindings.filter((f) => f.rule === 'UI-UNAUTHORIZED-LIB').length, 2);

    const vueFindings = inspectFile('resources/js/Pages/ReactBad.vue', sampleLocales, tmp);
    assert.equal(vueFindings.filter((f) => f.rule === 'UI-UNAUTHORIZED-LIB').length, 3);
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('fails explicitly when --base ref is missing instead of silent PASS', () => {
    const tmp = makeTempProject({
        'resources/js/Pages/Safe.vue': `<template><div class="bg-surface">{{ $t('common.save') }}</div></template>`,
    }, true);

    assert.throws(
        () => runConstitutionCheck(parseArgs(['--base', 'origin/nonexistent-branch']), tmp),
        /unavailable or cannot be resolved/
    );
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('CI --base mode catches deliberate new violations while isolating untouched legacy errors', () => {
    const tmp = makeTempProject({
        'resources/js/Pages/LegacyWithErrors.vue': `<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
</script>
<template>
    <div v-html="legacyHtml" style="color: #111111">
        <PrimaryButton>{{ $t('common.save') }}</PrimaryButton>
    </div>
</template>`,
    }, true);

    execFileSync('git', ['checkout', '-b', 'feature/safe-pr'], { cwd: tmp, stdio: 'ignore' });
    fs.writeFileSync(
        path.join(tmp, 'resources/js/Pages/LegacyWithErrors.vue'),
        `<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
</script>
<template>
    <div v-html="legacyHtml" style="color: #111111">
        <PrimaryButton>{{ $t('common.save') }}</PrimaryButton>
        <p class="text-text-primary">{{ $t('work_orders.title') }}</p>
    </div>
</template>`,
        'utf8'
    );
    execFileSync('git', ['add', '.'], { cwd: tmp, stdio: 'ignore' });
    execFileSync('git', ['commit', '-m', 'safe addition on legacy file'], { cwd: tmp, stdio: 'ignore' });

    const safePrReport = runConstitutionCheck(parseArgs(['--base', 'main']), tmp);
    assert.equal(safePrReport.errors.length, 0);
    assert.ok(safePrReport.legacySkippedCount >= 3);

    // Now introduce a deliberate new violation on a new file in the PR
    fs.writeFileSync(
        path.join(tmp, 'resources/js/Pages/NewViolation.vue'),
        `<template><div class="bg-[#ff0000]">{{ $t('common.save') }}</div></template>`,
        'utf8'
    );
    execFileSync('git', ['add', '.'], { cwd: tmp, stdio: 'ignore' });
    execFileSync('git', ['commit', '-m', 'deliberate hex violation'], { cwd: tmp, stdio: 'ignore' });

    const badPrReport = runConstitutionCheck(parseArgs(['--base', 'main']), tmp);
    assert.equal(badPrReport.errors.length, 1);
    assert.equal(badPrReport.errors[0].rule, 'UI-003-HEX');
    fs.rmSync(tmp, { recursive: true, force: true });
});

test('detects parallel design sources (UI-PARALLEL-DESIGN-SOURCE) in changed files without flagging .agents/skills', () => {
    const tmp = makeTempProject({}, true);
    fs.mkdirSync(path.join(tmp, 'design-system/khidmah-pro'), { recursive: true });
    fs.mkdirSync(path.join(tmp, '.agents/skills/design-system'), { recursive: true });
    fs.writeFileSync(path.join(tmp, 'design-system/khidmah-pro/MASTER.md'), '# Master\n', 'utf8');
    fs.writeFileSync(path.join(tmp, 'DESIGN.md'), '# Design\n', 'utf8');
    fs.writeFileSync(path.join(tmp, '.agents/skills/design-system/SKILL.md'), '# Skill\n', 'utf8');

    const report = runConstitutionCheck(parseArgs(['--changed']), tmp);
    const parallel = report.errors.filter((f) => f.rule === 'UI-PARALLEL-DESIGN-SOURCE');
    assert.equal(parallel.length, 2);
    fs.rmSync(tmp, { recursive: true, force: true });
});
