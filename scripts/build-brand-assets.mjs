/**
 * Renders the Khidmh Pro brandmark into every size the platform needs.
 *
 * Outputs (into public/images/brand/):
 *   logo-<n>.png        the app-shell mark at 16..1024
 *   favicon-<n>.png     the app-shell mark at 16/32/48
 *   favicon.ico         a multi-size ICO (16, 32, 48) built from the PNGs
 *   apple-touch-icon.png  180x180
 *   og-image.png        1200x630, the mark centred on the brand surface
 *
 * SVG -> PNG is done with headless Chrome so the gradients and masks are
 * rendered exactly as a browser would draw them.
 *
 * Run: node scripts/build-brand-assets.mjs
 */

import { readFileSync, writeFileSync, mkdirSync, copyFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { createRequire } from 'module';
import { execSync } from 'child_process';

const require = createRequire(import.meta.url);
const globalRoot = execSync('npm root -g').toString().trim();
const { chromium } = require(globalRoot + '/playwright');

const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT = resolve('public/images/brand');
const SRC = resolve('public/images/brand/logo.svg');
const MARK_SRC = resolve('public/images/brand/logo-mark.svg');

const NAVY = '#0f172a';
const NAVY_2 = '#1e293b';

mkdirSync(OUT, { recursive: true });

const shellSvg = readFileSync(SRC, 'utf8');
const markSvg = readFileSync(MARK_SRC, 'utf8');

/** Wrap an SVG in an HTML page and screenshot it at an exact pixel size. */
async function render(browser, svg, size, { background = 'transparent', padding = 0 } = {}) {
    const page = await browser.newPage({ deviceScaleFactor: 1 });
    const inner = size - padding * 2;
    // The source SVGs carry fixed width/height attributes; strip them so the
    // CSS box controls the rendered size.
    const sized = svg.replace(/<svg([^>]*)>/, (m, attrs) =>
        `<svg${attrs.replace(/\s(width|height)="[^"]*"/g, '')}>`
    );
    const html = `<!doctype html><html><head><meta charset="utf-8"><style>
        html,body{margin:0;padding:0;width:${size}px;height:${size}px;background:${background};overflow:hidden;}
        .wrap{width:${size}px;height:${size}px;display:flex;align-items:center;justify-content:center;overflow:hidden;}
        .art{width:${inner}px;height:${inner}px;display:flex;align-items:center;justify-content:center;}
        .art>svg{width:100%;height:100%;display:block;}
    </style></head><body><div class="wrap"><div class="art">${sized}</div></div></body></html>`;

    await page.setViewportSize({ width: size, height: size });
    await page.setContent(html, { waitUntil: 'load' });
    const buf = await page.screenshot({ omitBackground: background === 'transparent' });
    await page.close();
    return buf;
}

/**
 * Build an ICO file (PNG-compressed entries) from a set of PNG buffers.
 * ICO allows PNG payloads for sizes >= 256 in modern browsers; for
 * compatibility we embed the smaller sizes as PNG as well, which every
 * current browser accepts.
 */
function buildIco(entries) {
    const count = entries.length;
    const header = Buffer.alloc(6);
    header.writeUInt16LE(0, 0);        // reserved
    header.writeUInt16LE(1, 2);        // type = icon
    header.writeUInt16LE(count, 4);    // image count

    const dir = Buffer.alloc(16 * count);
    let offset = 6 + 16 * count;

    entries.forEach((e, i) => {
        const b = i * 16;
        dir.writeUInt8(e.size >= 256 ? 0 : e.size, b + 0);      // width
        dir.writeUInt8(e.size >= 256 ? 0 : e.size, b + 1);      // height
        dir.writeUInt8(0, b + 2);                                // palette
        dir.writeUInt8(0, b + 3);                                // reserved
        dir.writeUInt16LE(1, b + 4);                             // colour planes
        dir.writeUInt16LE(32, b + 6);                            // bits per pixel
        dir.writeUInt32LE(e.buf.length, b + 8);
        dir.writeUInt32LE(offset, b + 12);
        offset += e.buf.length;
    });

    return Buffer.concat([header, dir, ...entries.map((e) => e.buf)]);
}

(async () => {
    const browser = await chromium.launch({ executablePath: CHROME });

    /* --- the app-shell mark at every useful size ------------------------ */
    const sizes = [16, 32, 48, 64, 96, 128, 180, 192, 256, 384, 512, 1024];
    for (const size of sizes) {
        const buf = await render(browser, shellSvg, size);
        const name = size === 180 ? 'apple-touch-icon.png' : `logo-${size}.png`;
        writeFileSync(resolve(OUT, name), buf);
        console.log('wrote', name, `(${size}x${size})`);
    }

    /* --- favicons ------------------------------------------------------- */
    for (const size of [16, 32, 48]) {
        const buf = await render(browser, shellSvg, size);
        writeFileSync(resolve(OUT, `favicon-${size}.png`), buf);
        console.log('wrote', `favicon-${size}.png`);
    }

    const icoEntries = [];
    for (const size of [16, 32, 48]) {
        const buf = await render(browser, shellSvg, size);
        icoEntries.push({ size, buf });
    }
    writeFileSync(resolve(OUT, 'favicon.ico'), buildIco(icoEntries));
    console.log('wrote favicon.ico (16, 32, 48)');

    /* --- the bare mark on a transparent background ------------------------ */
    for (const size of [256, 512]) {
        const buf = await render(browser, markSvg, size);
        writeFileSync(resolve(OUT, `logo-mark-${size}.png`), buf);
        console.log('wrote', `logo-mark-${size}.png`);
    }

    /* --- the OG / social share image ------------------------------------ */
    const ogW = 1200;
    const ogH = 630;
    {
        const page = await browser.newPage({ deviceScaleFactor: 1 });
        const inner = 400;
        const sizedMark = markSvg.replace(/<svg([^>]*)>/, (m, attrs) =>
            `<svg${attrs.replace(/\s(width|height)="[^"]*"/g, '')}>`
        );
        const html = `<!doctype html><html><head><meta charset="utf-8"><style>
            html,body{margin:0;padding:0;width:${ogW}px;height:${ogH}px;overflow:hidden;}
            .og{width:${ogW}px;height:${ogH}px;display:flex;align-items:center;justify-content:center;
                background:linear-gradient(135deg, ${NAVY_2} 0%, ${NAVY} 100%);position:relative;}
            .ring{position:absolute;width:820px;height:820px;border-radius:50%;
                border:1px solid rgba(251,191,36,0.14);}
            .ring2{position:absolute;width:1080px;height:1080px;border-radius:50%;
                border:1px solid rgba(251,191,36,0.08);}
            .art{width:${inner}px;height:${inner}px;filter:drop-shadow(0 18px 40px rgba(0,0,0,0.45));}
            .art>svg{width:100%;height:100%;display:block;}
        </style></head><body>
            <div class="og"><div class="ring"></div><div class="ring2"></div>
            <div class="art">${sizedMark}</div></div>
        </body></html>`;
        await page.setViewportSize({ width: ogW, height: ogH });
        await page.setContent(html, { waitUntil: 'load' });
        await page.screenshot({ path: resolve(OUT, 'og-image.png') });
        await page.close();
        console.log('wrote og-image.png (1200x630)');
    }

    await browser.close();
})();
