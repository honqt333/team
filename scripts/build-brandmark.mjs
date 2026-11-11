/**
 * Brandmark builder for Khidmh Pro / خدمة برو.
 *
 * The spanner head is constructed from real SVG arc commands rather than a
 * sampled polygon, so the jaw mouth is an exact geometric opening and the
 * shape never self-intersects.
 *
 * Local frame for the spanner:
 *   - the head centre is the ORIGIN
 *   - the jaw mouth faces -X
 *   - the shaft runs along +X and is continued by the arrow
 *
 * Run: node scripts/build-brandmark.mjs [outDir]
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

function ptsToPath(pts) {
    let d = `M${n(pts[0][0])} ${n(pts[0][1])} `;
    for (let i = 1; i < pts.length; i++) d += `L${n(pts[i][0])} ${n(pts[i][1])} `;
    return d + 'Z';
}

/* ------------------------------------------------------------------ gear */

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
            const p = pt(cx, cy, rTip, a - tipHalf + ((2 * tipHalf) * s) / 8);
            d += `L${n(p[0])} ${n(p[1])} `;
        }
        d += `L${n(f2[0])} ${n(f2[1])} `;
    }
    d += 'Z';
    const hp = arcPoints(cx, cy, rHole, 0, 360, 48);
    d += ` M${n(hp[0][0])} ${n(hp[0][1])} `;
    for (let i = 1; i < hp.length; i++) d += `L${n(hp[i][0])} ${n(hp[i][1])} `;
    d += 'Z';
    return d;
}

/* --------------------------------------------------------------- spanner */

/**
 * The spanner, built from real arc commands in the LOCAL frame.
 *
 * The head is a disc of radius `headR` whose mouth is a wedge removed from
 * the -X side. The outline is:
 *   lower jaw tip
 *   -> straight down the lower jaw wall to the mouth floor
 *   -> across the mouth floor (a shallow arc)
 *   -> straight up the upper jaw wall to the upper jaw tip
 *   -> the long way around the back of the head (through +X)
 *   -> down to the shaft
 *
 * Every segment is exact, so there are no seams and no self-intersection.
 */
function spannerLocal({ headR, mouthHalf, mouthDepth, slotDepth, shaftHalf, shaftLen }) {
    const seg = [];

    // Angles of the two jaw tips, measured on the -X side.
    const tipLow = 180 + mouthHalf;   // below the axis
    const tipHigh = 180 - mouthHalf;  // above the axis

    // The mouth floor: the deepest point of the wedge, at radius
    // `headR - mouthDepth`, sitting on the -X axis.
    const floorR = headR - mouthDepth;
    const floor = pt(0, 0, floorR, 180);

    // The two jaw tips on the rim.
    const pTipLow = pt(0, 0, headR, tipLow);
    const pTipHigh = pt(0, 0, headR, tipHigh);

    // --- Build the outline as a list of segments -------------------------
    // Each segment is {type:'L', to:[x,y]} or {type:'A', r, to:[x,y], sweep}

    // 1. Start at the LOWER jaw tip.
    seg.push({ type: 'M', to: pTipLow });

    // 2. The mouth is a SLOT cut in from the rim: the classic open-end
    //    spanner jaw — two walls running inward from the rim, joined by a
    //    U at the bottom.
    //
    // 2. The mouth is the classic open-end spanner jaw: two walls that run
    //    inward from the rim and converge, joined by a U at the bottom.
    //
    //    The walls are RADIAL — each one runs from its rim tip toward the
    //    jaw axis — so the mouth narrows from the rim gap down to the slot
    //    width, which is what a spanner jaw actually looks like.
    //
    //    The U is a half circle of radius `mouthR`. Its CHORD is the
    //    segment between the two wall ends and its centre sits on the +X
    //    side of that chord, so the arc bulges toward -X (out of the slot).
    //
    //    The arc's outermost point (chordX + offset - mouthR) must land at
    //    the slot depth, so:
    //        chordX = -slotDepth + mouthR - offset
    const mouthR = mouthDepth / 2;
    const offset = Math.sqrt(mouthR * mouthR - (mouthR * mouthR) / 4);
    const chordX = -slotDepth + mouthR - offset;

    // NOTE the signs: the tip at (180 + mouthHalf) has NEGATIVE y, so it
    // joins the wall at NEGATIVE y; the tip at (180 - mouthHalf) has
    // POSITIVE y and joins the wall at POSITIVE y.
    const pWallLow = [chordX, -mouthR];
    const pWallHigh = [chordX, mouthR];

    // 2a. lower jaw tip -> straight along the slot wall to the U
    seg.push({ type: 'L', to: pWallLow });
    // 2b. the U cap: a half circle from the lower wall round to the upper
    //     wall. With the centre on the +X side of the chord, sweep=1
    //     (clockwise) traces the outward bulge toward -X.
    seg.push({ type: 'A', r: mouthR, to: pWallHigh, sweep: 1, large: 0 });
    // 2c. straight back out to the upper jaw tip
    seg.push({ type: 'L', to: pTipHigh });

    // 4. The long way around the back of the head: from tipHigh (161deg)
    //    decreasing through 90, 0, 270 to tipLow (199deg). That is 322
    //    degrees of sweep, so the SVG arc takes the large-arc flag.
    seg.push({ type: 'A', r: headR, to: pTipLow, sweep: 0, large: 1 });
    // 5. From the lower jaw tip, down to the shaft's bottom edge.
    const shaftEndX = headR + shaftLen;
    seg.push({ type: 'L', to: [headR * 0.50, shaftHalf * 1.15] });
    seg.push({ type: 'L', to: [headR + shaftLen * 0.5, shaftHalf] });
    seg.push({ type: 'L', to: [shaftEndX, shaftHalf] });

    // 6. Across the flat end and back along the top edge.
    seg.push({ type: 'L', to: [shaftEndX, -shaftHalf] });
    seg.push({ type: 'L', to: [headR + shaftLen * 0.5, -shaftHalf] });
    seg.push({ type: 'L', to: [headR * 0.50, -shaftHalf * 1.15] });

    // 7. Close back to the upper jaw tip.
    seg.push({ type: 'L', to: pTipHigh });

    return { seg, headR, mouthHalf, mouthDepth, shaftHalf, shaftLen, floorR };
}

/**
 * Emit an SVG path string for the spanner, rotated and translated into
 * place. Because the shape is defined by arcs and lines in the local
 * frame, we transform every point and re-issue the arc with the same
 * radius (a rotation+translation preserves radii).
 */
function spannerPath(cx, cy, angle, params) {
    const { seg, headR, shaftHalf, shaftLen, floorR } = params;
    const a = deg(angle);
    const cos = Math.cos(a);
    const sin = Math.sin(a);
    const xf = ([x, y]) => [cx + x * cos - y * sin, cy + x * sin + y * cos];

    let d = '';
    for (const s of seg) {
        const [x, y] = xf(s.to);
        if (s.type === 'M') d += `M${n(x)} ${n(y)} `;
        else if (s.type === 'L') d += `L${n(x)} ${n(y)} `;
        else if (s.type === 'A') d += `A${n(s.r)} ${n(s.r)} 0 ${s.large} ${s.sweep} ${n(x)} ${n(y)} `;
    }
    d += 'Z';

    // The head stays solid. The open jaw alone identifies the tool, and the
    // cog supplies the round mechanical counterweight — a socket hole here
    // would only muddy the shape at favicon size.
    return { d, headR, floorR };
}

/* ----------------------------------------------------------------- arrow */

/**
 * A bold chevron arrow running along +X, continuing the spanner's diagonal.
 *
 * The head is a classic arrowhead with a slight tail flare (the barbs sit
 * a little further back than the shoulders), which reads as motion rather
 * than a static triangle.
 */
function arrowLocal({ tipX, halfW, shaftHalf, tailX }) {
    const headLen = halfW * 1.3;
    const baseX = tipX - headLen;
    const barbX = baseX + halfW * 0.26;   // barbs sit slightly forward
    return [
        [tipX, 0],                          // tip
        [barbX, -halfW],                    // upper barb
        [baseX, -shaftHalf],                // upper shoulder
        [tailX, -shaftHalf],                // tail, top edge
        [tailX, shaftHalf],                 // tail, bottom edge
        [baseX, shaftHalf],                 // lower shoulder
        [barbX, halfW],                     // lower barb
    ];
}

/* -------------------------------------------------------------- assembly */

const ANGLE = -45;          // the diagonal: lower-left to upper-right
const ORIGIN = [212, 288];  // the spanner head sits at the lower-left

const HEAD_R = 66;
const MOUTH_HALF = 27;      // half-angle of the jaw opening at the rim
const MOUTH_DEPTH = 26;     // width of the slot at the bottom of the U
const SLOT_DEPTH = 40;      // how far the jaw reaches in from the rim
const SHAFT_HALF = 16;
const SHAFT_LEN = 108;

const SHAFT_END = HEAD_R + SHAFT_LEN;
const TIP_X = SHAFT_END + 84;
const ARROW_HALF = 50;
const ARROW_TAIL = SHAFT_END - 8;

const GEAR = { cx: 318, cy: 350, teeth: 9, rTip: 78, rRoot: 61, rHole: 27, phase: -20 };

const spanner = spannerPath(ORIGIN[0], ORIGIN[1], ANGLE, spannerLocal({
    headR: HEAD_R,
    mouthHalf: MOUTH_HALF,
    mouthDepth: MOUTH_DEPTH,
    slotDepth: SLOT_DEPTH,
    shaftHalf: SHAFT_HALF,
    shaftLen: SHAFT_LEN,
}));

const arrowPts = transform(arrowLocal({ tipX: TIP_X, halfW: ARROW_HALF, shaftHalf: SHAFT_HALF + 1, tailX: ARROW_TAIL }), ANGLE, ORIGIN[0], ORIGIN[1]);

const gearD = gearPath(GEAR.cx, GEAR.cy, GEAR.teeth, GEAR.rTip, GEAR.rRoot, GEAR.rHole, GEAR.phase);
const spannerD = spanner.d;
const arrowD = ptsToPath(arrowPts);

/* --- tight bounding box of the artwork ------------------------------- */

function pathPoints(d) {
    const pts = [];
    const re = /(?:[ML]|A[\d.]+ [\d.]+ 0 \d \d)\s*(-?[\d.]+)\s+(-?[\d.]+)/g;
    let m;
    while ((m = re.exec(d)) !== null) pts.push([parseFloat(m[1]), parseFloat(m[2])]);
    // Arc endpoints only cover part of the arc, so add sampled arc points.
    return pts;
}

const allPts = [...pathPoints(spannerD), ...pathPoints(arrowD), ...pathPoints(gearD)];
// include the sampled head circle so the bbox is exact
const headPts = transform(arcPoints(0, 0, HEAD_R, 0, 360, 72), ANGLE, ORIGIN[0], ORIGIN[1]);
allPts.push(...headPts);

const xs = allPts.map((p) => p[0]);
const ys = allPts.map((p) => p[1]);
const bbox = {
    x: Math.min(...xs),
    y: Math.min(...ys),
    w: Math.max(...xs) - Math.min(...xs),
    h: Math.max(...ys) - Math.min(...ys),
};

/**
 * variant:
 *   'app'   — rounded-square shell, gold mark on navy  (default)
 *   'mark'  — the mark only, no shell, tight viewBox, transparent background
 *   'mono'  — the mark only, single colour (currentColor -> #ffffff)
 */
function buildSvg(variant) {
    const isApp = variant === 'app';
    const isMono = variant === 'mono';

    const markFill = isMono ? 'currentColor' : 'url(#kpGold)';
    const gearFill = isMono ? 'currentColor' : 'url(#kpGoldFlat)';

    const viewBox = isApp
        ? `0 0 ${SIZE} ${SIZE}`
        : `${n(bbox.x)} ${n(bbox.y)} ${n(bbox.w)} ${n(bbox.h)}`;
    const width = isApp ? SIZE : n(bbox.w);
    const height = isApp ? SIZE : n(bbox.h);

    const shell = isApp
        ? `  <rect x="16" y="16" width="480" height="480" rx="108" fill="url(#kpSurface)"/>
  <rect x="16.75" y="16.75" width="478.5" height="478.5" rx="107.25" fill="none" stroke="#fbbf24" stroke-opacity="0.16" stroke-width="1.5"/>
`
        : '';

    const maskRect = `<rect x="${n(bbox.x - 10)}" y="${n(bbox.y - 10)}" width="${n(bbox.w + 20)}" height="${n(bbox.h + 20)}" fill="#fff"/>`;

    const defs = isMono
        ? `  <mask id="kpInterlock">
    ${maskRect}
    <path d="${spannerD}" fill="#000"/>
    <path d="${arrowD}" fill="#000"/>
  </mask>
`
        : `  <linearGradient id="kpSurface" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="#1e293b"/>
    <stop offset="1" stop-color="#0f172a"/>
  </linearGradient>
  <!-- userSpaceOnUse so the spanner and the arrow share ONE continuous
       gradient across the whole mark; with the default objectBoundingBox
       each path would get its own gradient and the seam would show. -->
  <linearGradient id="kpGold" gradientUnits="userSpaceOnUse"
                  x1="${n(bbox.x)}" y1="${n(bbox.y + bbox.h)}"
                  x2="${n(bbox.x + bbox.w * 0.7)}" y2="${n(bbox.y)}">
    <stop offset="0" stop-color="#d97706"/>
    <stop offset="0.42" stop-color="#f59e0b"/>
    <stop offset="1" stop-color="#fbbf24"/>
  </linearGradient>
  <linearGradient id="kpGoldFlat" gradientUnits="userSpaceOnUse"
                  x1="${n(bbox.x)}" y1="${n(bbox.y + bbox.h)}"
                  x2="${n(bbox.x + bbox.w * 0.7)}" y2="${n(bbox.y)}">
    <stop offset="0" stop-color="#f59e0b"/>
    <stop offset="1" stop-color="#fbbf24"/>
  </linearGradient>
  <mask id="kpInterlock">
    ${maskRect}
    <path d="${spannerD}" fill="#000"/>
    <path d="${arrowD}" fill="#000"/>
  </mask>
`;

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${viewBox}" width="${width}" height="${height}" role="img" aria-label="Khidmh Pro">
  <title>Khidmh Pro</title>
  <defs>
${defs}  </defs>

${shell}  <path d="${gearD}" fill="${gearFill}" fill-rule="evenodd" mask="url(#kpInterlock)"/>
  <path d="${spannerD}" fill="${markFill}" fill-rule="evenodd"/>
  <path d="${arrowD}" fill="${markFill}" fill-rule="evenodd"/>
</svg>
`;
}

/* ------------------------------------------------------------------ main */

const outDir = process.argv[2] || 'public/images/brand';
mkdirSync(outDir, { recursive: true });

for (const [name, variant] of [
    ['logo.svg', 'app'],
    ['logo-mark.svg', 'mark'],
    ['logo-mono.svg', 'mono'],
]) {
    const file = resolve(outDir, name);
    writeFileSync(file, buildSvg(variant));
    console.log('wrote', file);
}
