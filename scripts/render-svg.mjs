import { readFileSync, writeFileSync } from 'fs';
import { resolve } from 'path';

import { createRequire } from 'module';
import { execSync } from 'child_process';

const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const require = createRequire(import.meta.url);
const globalRoot = execSync('npm root -g').toString().trim();
const { chromium } = require(globalRoot + '/playwright');

const svgPath = resolve(process.argv[2]);
const outDir = resolve(process.argv[3] || '.');
const sizes = (process.argv[4] || '1024').split(',').map(Number);
const transparent = process.argv[5] !== 'opaque';

const svg = readFileSync(svgPath, 'utf8');

(async () => {
    const browser = await chromium.launch({ executablePath: CHROME });
    const page = await browser.newPage({ deviceScaleFactor: 1 });

    for (const size of sizes) {
        const html = `<!doctype html><html><head><meta charset="utf-8"><style>
            html,body{margin:0;padding:0;background:${transparent ? 'transparent' : '#0f172a'};}
            .wrap{width:${size}px;height:${size}px;display:flex;align-items:center;justify-content:center;${transparent ? 'background:transparent;' : ''}}
            svg{width:${size}px;height:${size}px;display:block;}
        </style></head><body><div class="wrap">${svg}</div></body></html>`;

        await page.setViewportSize({ width: size, height: size });
        await page.setContent(html, { waitUntil: 'load' });
        const out = resolve(outDir, `render-${size}.png`);
        await page.screenshot({ path: out, omitBackground: transparent });
        console.log('wrote', out);
    }

    await browser.close();
})();
