/**
 * Renders the social share card of the public landing page.
 *
 * WHY A BROWSER AND NOT PHP
 * -------------------------
 * GD cannot shape Arabic: it would draw the letters disconnected and in
 * the wrong order. A browser renders the line exactly as the page does,
 * which is the whole point of a share card.
 *
 * WHY THE DATA COMES FROM THE APP
 * -------------------------------
 * The brand name and the headline are read from the CMS settings and the
 * app config, so rebranding the system or editing the headline in the CMS
 * does not leave a stale card behind. Nothing is hardcoded here.
 *
 * Output: public/images/landing/og.jpg — 1200×630, JPEG.
 *
 * Usage: npm run og:image
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import puppeteer from 'puppeteer-core';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const template = path.join(root, 'scripts', 'og-card.html');
const rendered = path.join(root, 'scripts', '.og-card.rendered.html');
const output = path.join(root, 'public', 'images', 'landing', 'og.jpg');

const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

/**
 * Read the strings the card needs from the application itself.
 */
function readAppData() {
    const script = `
        $setting = function ($key, $fallback = '') {
            $row = App\\Models\\Setting::where('key', $key)->first();
            return $row && $row->value !== null && $row->value !== '' ? $row->value : $fallback;
        };

        // The proof line of the card: the same three values the stats
        // section of the page shows. Read from the CMS so the card cannot
        // advertise something the page does not.
        $stats = json_decode($setting('landing.stats.list', '[]'), true);
        $statValues = [];
        foreach (is_array($stats) ? $stats : [] as $stat) {
            if (isset($stat['value']) && $stat['value'] !== '') {
                $statValues[] = $stat['value'];
            }
        }

        echo json_encode([
            'brand_ar' => config('app.name_ar', 'خدمة برو'),
            'brand_en' => config('app.name', 'Khidmh Pro'),
            'headline_ar' => $setting('landing.hero.title.ar'),
            'headline_en' => $setting('landing.hero.title.en'),
            'subtitle_ar' => $setting('landing.hero.subtitle.ar'),
            'stats' => implode(' · ', array_slice($statValues, 0, 3)),
        ], JSON_UNESCAPED_UNICODE);
    `;

    const raw = execFileSync('php', ['artisan', 'tinker', `--execute=${script}`], {
        cwd: root,
        encoding: 'utf8',
        maxBuffer: 1024 * 1024,
    });

    // tinker prints the value; the JSON is the last line it emits.
    const line = raw
        .trim()
        .split('\n')
        .reverse()
        .find((l) => l.trim().startsWith('{'));

    if (!line) {
        throw new Error(`Could not read the card data from the app:\n${raw}`);
    }

    return JSON.parse(line);
}

const data = readAppData();

if (!data.headline_ar) {
    throw new Error('landing.hero.title.ar is empty — the card would have no headline.');
}

const escape = (value) =>
    String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

const html = fs
    .readFileSync(template, 'utf8')
    .replace('{{BRAND_AR}}', escape(data.brand_ar))
    .replace('{{BRAND_EN}}', escape(data.brand_en))
    .replace('{{HEADLINE_AR}}', escape(data.headline_ar))
    .replace('{{HEADLINE_EN}}', escape(data.headline_en))
    .replace('{{SUBTITLE_AR}}', escape(data.subtitle_ar))
    .replace('{{FOOT_AR}}', escape(data.stats))
    .replace('{{FOOT_EN}}', escape(data.headline_en));

fs.writeFileSync(rendered, html);

const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: 'new',
    args: ['--no-sandbox', '--force-device-scale-factor=1', '--hide-scrollbars'],
});

try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1200, height: 630, deviceScaleFactor: 1 });
    await page.goto(`file://${rendered}`, { waitUntil: 'networkidle0', timeout: 30000 });
    await page.evaluate(() => document.fonts.ready);

    fs.mkdirSync(path.dirname(output), { recursive: true });
    await page.screenshot({ path: output, type: 'jpeg', quality: 90 });

    const { size } = fs.statSync(output);
    console.log(`og.jpg written: ${output} (${Math.round(size / 1024)} KB, 1200×630 JPEG)`);
} finally {
    await browser.close();
    fs.rmSync(rendered, { force: true });
}
