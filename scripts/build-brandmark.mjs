/**
 * Brandmark builder for Khidmah Pro / خدمة برو.
 * ------------------------------------------------------------------
 * THE MARK: a gear ring with a bold arrow rising through a channel cut
 * in the ring — "التقدّم في قلب الآلة" (progress at the heart of the
 * machine). The arrowhead bursts through the gap between two teeth.
 *
 * WHY IT IS BUILT HERE AND NOT DRAWN
 * ----------------------------------
 * The teeth, the bore and the arrow are all computed from exact angles,
 * so the shape never self-intersects, never leaves a loose chip, and
 * stays crisp at 16px. Nothing in this file is a sampled polygon.
 *
 * THE TWO CONSTRAINTS THAT DECIDE THE PARAMETERS
 * ----------------------------------------------
 *  1. The gear has 8 teeth with phase 22.5°. That puts exactly ONE
 *     tooth on each side of the arrow, so the arrowhead bursts through
 *     the gap between two teeth. Any other phase puts a tooth under the
 *     arrowhead, and the 10px halo clips its tip and leaves a loose
 *     gold chip floating beside the arrow.
 *  2. The arrowhead is 100px wide (halfW 50). The gap between the two
 *     flanking tooth tips is ~116px. A wider head overlaps the teeth
 *     and the halo bites them; a narrower one looks undersized against
 *     the gear.
 *
 * Palette comes from resources/js/Design/tokens.css:
 *   --color-accent-gold   #d97706 → #f59e0b → #fbbf24
 *   --color-brand-primary #2563eb (the blue app-tile shell)
 *   --color-surface       #1e293b → #0f172a (the navy app-tile shell)
 *
 * Outputs (into public/images/brand/):
 *   logo.svg        app tile, navy shell + gold mark
 *   logo-blue.svg   app tile, brand-primary shell + gold mark
 *   logo-mark.svg   the mark alone, tight viewBox, transparent
 *   logo-mono.svg   the mark alone in one colour (currentColor)
 *
 * Run: node scripts/build-brandmark.mjs [outDir]
 */

import { writeFileSync, mkdirSync } from 'fs';
import { dirname, resolve } from 'path';

const SIZE = 512;
const deg = (d) => (d * Math.PI) / 180;
const n = (v) => Math.round(v * 100) / 100;
const pt = (cx, cy, r, a) => [cx + r * Math.cos(deg(a)), cy + r * Math.sin(deg(a))];

/* ------------------------------------------------------------------ gear */

/**
 * A gear with a central bore.
 *
 * The outline is one closed path; the bore is a second sub-path, so the
 * whole shape renders with fill-rule="evenodd" and the bore stays a
 * clean circle instead of a pac-man.
 */
function gearPath(cx, cy, teeth, rTip, rRoot, rHole, phase = 0) {
    const step = 360 / teeth;
    const tipHalf = step * 0.16;
    const flank = step * 0.26;
    let d = '';
    for (let i = 0; i < teeth; i++) {
        const a = phase + i * step;
        const p0 = pt(cx, cy, rRoot, a - step / 2 + flank);
        d += i === 0 ? `M${n(p0[0])} ${n(p0[1])} ` : '';
        const f1 = pt(cx, cy, rRoot, a - tipHalf - flank);
        const t0 = pt(cx, cy, rTip, a - tipHalf);
        const f2 = pt(cx, cy, rRoot, a + tipHalf + flank);
        d += `L${n(f1[0])} ${n(f1[1])} L${n(t0[0])} ${n(t0[1])} `;
        for (let s = 1; s <= 8; s++) {
            const p = pt(cx, cy, rTip, a - tipHalf + (2 * tipHalf * s) / 8);
            d += `L${n(p[0])} ${n(p[1])} `;
        }
        d += `L${n(f2[0])} ${n(f2[1])} `;
    }
    d += 'Z';

    // the bore
    let h = `M${n(cx + rHole)} ${n(cy)} `;
    for (let i = 1; i <= 48; i++) {
        const p = pt(cx, cy, rHole, (360 * i) / 48);
        h += `L${n(p[0])} ${n(p[1])} `;
    }
    return d + h + 'Z';
}

/* ----------------------------------------------------------------- arrow */

/** A straight arrow pointing along `angle`, tip at (tipX, tipY). */
function arrowPath(tipX, tipY, angle, { length, halfW, shaftHalf }) {
    const a = deg(angle);
    const ux = Math.cos(a);
    const uy = Math.sin(a);
    const px = -uy;
    const py = ux;
    const headLen = halfW * 1.35;
    const baseX = tipX - ux * headLen;
    const baseY = tipY - uy * headLen;
    const barbX = baseX + ux * halfW * 0.26;
    const barbY = baseY + uy * halfW * 0.26;
    const tailX = tipX - ux * length;
    const tailY = tipY - uy * length;
    const P = (x, y) => `${n(x)} ${n(y)}`;
    return [
        `M${P(tipX, tipY)}`,
        `L${P(barbX + px * halfW, barbY + py * halfW)}`,
        `L${P(baseX + px * shaftHalf, baseY + py * shaftHalf)}`,
        `L${P(tailX + px * shaftHalf, tailY + py * shaftHalf)}`,
        `L${P(tailX - px * shaftHalf, tailY - py * shaftHalf)}`,
        `L${P(baseX - px * shaftHalf, baseY - py * shaftHalf)}`,
        `L${P(barbX - px * halfW, barbY - py * halfW)}`,
        'Z',
    ].join(' ');
}

/* -------------------------------------------------------------- assembly */

const GEAR = { cx: 256, cy: 268, teeth: 8, rTip: 150, rRoot: 116, rHole: 62, phase: 22.5 };
const TIP_Y = 74;
const SHAFT_HALF = 30;
const HEAD_HALF_W = 50;
const HALO = 10;          // the channel cut around the arrow
const SHAFT_BOTTOM = 312; // the arrow stops inside the bore, clear of its floor

const gearD = gearPath(GEAR.cx, GEAR.cy, GEAR.teeth, GEAR.rTip, GEAR.rRoot, GEAR.rHole, GEAR.phase);
const arrowD = arrowPath(256, TIP_Y, -90, {
    length: SHAFT_BOTTOM - TIP_Y,
    halfW: HEAD_HALF_W,
    shaftHalf: SHAFT_HALF,
});

/* --- tight bounding box of the artwork ------------------------------- */

/**
 * The mark variant is cropped tight to the artwork, so the box has to be
 * exact. Arc endpoints alone do not bound a curve, so the tooth tips and
 * the arrow's corners are sampled.
 */
function bbox() {
    const xs = [];
    const ys = [];

    // every tooth tip, sampled along its arc
    const step = 360 / GEAR.teeth;
    const tipHalf = step * 0.16;
    for (let i = 0; i < GEAR.teeth; i++) {
        const a = GEAR.phase + i * step - tipHalf;
        for (let s = 0; s <= 8; s++) {
            const p = pt(GEAR.cx, GEAR.cy, GEAR.rTip, a + (2 * tipHalf * s) / 8);
            xs.push(p[0]);
            ys.push(p[1]);
        }
    }

    // the arrow's polygon corners
    for (const t of arrowD.matchAll(/(-?[\d.]+) (-?[\d.]+)/g)) {
        xs.push(parseFloat(t[1]));
        ys.push(parseFloat(t[2]));
    }

    const x = Math.min(...xs);
    const y = Math.min(...ys);
    return { x, y, w: Math.max(...xs) - x, h: Math.max(...ys) - y };
}

const BOX = bbox();

/* ---------------------------------------------------------------- shells */

const SHELLS = {
    navy: { from: '#1e293b', to: '#0f172a' },
    blue: { from: '#3b82f6', to: '#1d4ed8' },
};

/**
 * variant:
 *   'app'  — rounded-square shell + mark
 *   'mark' — the mark only, tight viewBox, transparent
 *   'mono' — the mark only, single colour (currentColor)
 */
function buildSvg(variant, shellKey = 'navy') {
    const isApp = variant === 'app';
    const isMono = variant === 'mono';
    const shell = SHELLS[shellKey];

    const markFill = isMono ? 'currentColor' : 'url(#kpGold)';

    const viewBox = isApp
        ? `0 0 ${SIZE} ${SIZE}`
        : `${n(BOX.x)} ${n(BOX.y)} ${n(BOX.w)} ${n(BOX.h)}`;
    const width = isApp ? SIZE : n(BOX.w);
    const height = isApp ? SIZE : n(BOX.h);

    const defs = isMono
        ? `  <mask id="kpInterlock">
    <rect x="${n(BOX.x - 10)}" y="${n(BOX.y - 10)}" width="${n(BOX.w + 20)}" height="${n(
            BOX.h + 20
        )}" fill="#fff"/>
    <path d="${arrowD}" fill="#000" stroke="#000" stroke-width="${HALO}" stroke-linejoin="round"/>
  </mask>
`
        : `  <linearGradient id="kpSurface" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="${shell.from}"/>
    <stop offset="1" stop-color="${shell.to}"/>
  </linearGradient>
  <!-- userSpaceOnUse so the gear and the arrow share ONE continuous
       gradient across the whole mark; with the default objectBoundingBox
       each path would get its own gradient and the seam would show. -->
  <linearGradient id="kpGold" gradientUnits="userSpaceOnUse"
                  x1="${n(BOX.x)}" y1="${n(BOX.y + BOX.h)}"
                  x2="${n(BOX.x + BOX.w * 0.7)}" y2="${n(BOX.y)}">
    <stop offset="0" stop-color="#d97706"/>
    <stop offset="0.42" stop-color="#f59e0b"/>
    <stop offset="1" stop-color="#fbbf24"/>
  </linearGradient>
  <mask id="kpInterlock">
    <rect x="${n(BOX.x - 10)}" y="${n(BOX.y - 10)}" width="${n(BOX.w + 20)}" height="${n(
        BOX.h + 20
    )}" fill="#fff"/>
    <path d="${arrowD}" fill="#000" stroke="#000" stroke-width="${HALO}" stroke-linejoin="round"/>
  </mask>
`;

    const shellMarkup = isApp
        ? `  <rect x="16" y="16" width="480" height="480" rx="108" fill="url(#kpSurface)"/>
  <rect x="16.75" y="16.75" width="478.5" height="478.5" rx="107.25" fill="none" stroke="#fbbf24" stroke-opacity="0.16" stroke-width="1.5"/>
`
        : '';

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${viewBox}" width="${width}" height="${height}" role="img" aria-label="Khidmh Pro">
  <title>Khidmh Pro</title>
  <defs>
${defs}  </defs>

${shellMarkup}  <path d="${gearD}" fill="${markFill}" fill-rule="evenodd" mask="url(#kpInterlock)"/>
  <path d="${arrowD}" fill="${markFill}"/>
</svg>
`;
}

/* ------------------------------------------------------------------ main */

const outDir = process.argv[2] || 'public/images/brand';
mkdirSync(outDir, { recursive: true });

const targets = [
    ['logo.svg', buildSvg('app', 'navy')],
    ['logo-blue.svg', buildSvg('app', 'blue')],
    ['logo-mark.svg', buildSvg('mark', 'navy')],
    ['logo-mono.svg', buildSvg('mono', 'navy')],
];

for (const [name, content] of targets) {
    const file = resolve(outDir, name);
    writeFileSync(file, content);
    console.log('wrote', file);
}
