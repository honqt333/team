/**
 * Debug renderer for the brandmark construction.
 * Draws the spanner / arrow / gear as flat debug shapes with their key
 * construction circles, so geometry bugs are visible directly.
 *
 * Run: node scripts/debug-brandmark.mjs [out.svg]
 */
import { writeFileSync, mkdirSync } from 'fs';
import { dirname, resolve } from 'path';

const SIZE = 512;
const deg = (d) => (d * Math.PI) / 180;
const n = (v) => Math.round(v * 100) / 100;
const pt = (cx, cy, r, a) => [cx + r * Math.cos(deg(a)), cy + r * Math.sin(deg(a))];

function arcPoints(cx, cy, r, a0, a1, steps) {
    const out = [];
    for (let i = 0; i <= steps; i++) out.push(pt(cx, cy, r, a0 + ((a1 - a0) * i) / steps));
    return out;
}
function transform(pts, angle, cx, cy) {
    const a = deg(angle);
    const cos = Math.cos(a);
    const sin = Math.sin(a);
    return pts.map(([x, y]) => [cx + x * cos - y * sin, cy + x * sin + y * cos]);
}
function ptsToPath(pts, close = true) {
    let d = `M${n(pts[0][0])} ${n(pts[0][1])} `;
    for (let i = 1; i < pts.length; i++) d += `L${n(pts[i][0])} ${n(pts[i][1])} `;
    return d + (close ? 'Z' : '');
}

// ---- geometry (mirrors build-brandmark.mjs) --------------------------
const ANGLE = -45;
const ORIGIN = [230, 282];
const HEAD_R = 70;
const JAW_DEPTH = 50;
const JAW_HALF = 33;
const SHAFT_HALF = 17;
const SHAFT_LEN = 104;
const SHAFT_END = HEAD_R + SHAFT_LEN;
const TIP_X = SHAFT_END + 74;
const ARROW_HALF = 52;
const ARROW_TAIL = SHAFT_END - 40;

const notchFloorR = HEAD_R - JAW_DEPTH;
const spannerLocal = [];
spannerLocal.push(pt(0, 0, HEAD_R, 180 + JAW_HALF));
for (const p of arcPoints(0, 0, HEAD_R, 180 + JAW_HALF, 360 - JAW_HALF, 56)) spannerLocal.push(p);
spannerLocal.push(pt(0, 0, HEAD_R, -JAW_HALF));
spannerLocal.push(pt(0, 0, notchFloorR, -JAW_HALF * 0.62));
spannerLocal.push([0, -notchFloorR * 0.08]);
spannerLocal.push(pt(0, 0, notchFloorR, 180 + JAW_HALF * 0.62));
spannerLocal.push([HEAD_R * 0.55, SHAFT_HALF * 0.9]);
spannerLocal.push([SHAFT_END, SHAFT_HALF]);
spannerLocal.push([SHAFT_END, -SHAFT_HALF]);
spannerLocal.push([HEAD_R * 0.55, -SHAFT_HALF * 0.9]);

const arrowLocal = [
    [TIP_X, 0],
    [TIP_X - ARROW_HALF * 1.35, -ARROW_HALF],
    [TIP_X - ARROW_HALF * 1.35, -SHAFT_HALF - 1],
    [ARROW_TAIL, -SHAFT_HALF - 1],
    [ARROW_TAIL, SHAFT_HALF + 1],
    [TIP_X - ARROW_HALF * 1.35, SHAFT_HALF + 1],
    [TIP_X - ARROW_HALF * 1.35, ARROW_HALF],
];

const sp = transform(spannerLocal, ANGLE, ORIGIN[0], ORIGIN[1]);
const ar = transform(arrowLocal, ANGLE, ORIGIN[0], ORIGIN[1]);

// Mark every spanner vertex so the traversal order is visible.
const vertexDots = sp
    .map(([x, y], i) => `<circle cx="${n(x)}" cy="${n(y)}" r="3" fill="#fbbf24"/><text x="${n(x + 4)}" y="${n(y - 4)}" font-size="9" fill="#fbbf24">${i}</text>`)
    .join('\n  ');

const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${SIZE} ${SIZE}" width="${SIZE}" height="${SIZE}">
  <rect width="${SIZE}" height="${SIZE}" fill="#0f172a"/>
  <path d="${ptsToPath(sp)}" fill="#f472b6" fill-opacity="0.4" stroke="#f472b6" stroke-width="2"/>
  <path d="${ptsToPath(ar)}" fill="#fbbf24" fill-opacity="0.4" stroke="#fbbf24" stroke-width="2"/>
  <circle cx="${ORIGIN[0]}" cy="${ORIGIN[1]}" r="${HEAD_R}" fill="none" stroke="#22d3ee" stroke-width="1.5" stroke-dasharray="5 5"/>
  <circle cx="${ORIGIN[0]}" cy="${ORIGIN[1]}" r="${notchFloorR}" fill="none" stroke="#22d3ee" stroke-width="1.5" stroke-dasharray="3 3"/>
  ${vertexDots}
</svg>
`;

const out = process.argv[2] || '/tmp/kp-debug.svg';
mkdirSync(dirname(resolve(out)), { recursive: true });
writeFileSync(resolve(out), svg);
console.log('wrote', out, '| spanner vertices:', sp.length);
