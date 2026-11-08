import { readdirSync, statSync, writeFileSync } from 'fs';
import { join, relative } from 'path';

const ROOT = 'resources/js/Pages';
const OUT = 'resources/js/Pages/page-manifest.js';

function walk(dir) {
    const out = [];
    for (const entry of readdirSync(dir)) {
        const full = join(dir, entry);
        if (statSync(full).isDirectory()) out.push(...walk(full));
        else if (entry.endsWith('.vue')) out.push(full);
    }
    return out;
}

const files = walk(ROOT).sort();
const lines = files.map((f) => {
    const name = relative(ROOT, f).replace(/\\/g, '/').replace(/\.vue$/, '');
    return `    '${name}': () => import('./${relative('resources/js/Pages', f).replace(/\\/g, '/')}'),`;
});

const content = `/**
 * GENERATED FILE — do not edit by hand.
 *
 * Run: node scripts/generate-page-manifest.mjs
 *
 * Why this exists instead of an import.meta.glob call over Pages:
 *
 * A glob makes Vite enumerate every page at build time and attach a
 * \`__vite__mapDeps([...])\` dependency list to each entry. The browser then
 * fetches every one of those dependencies the moment the map is touched.
 *
 * Measured on the public landing page: 362 requests and ~2.1 MB transferred,
 * including TenantHealthScore, Payments, Proforma, PrintEngine and the whole
 * Website CMS — none of which a landing-page visitor ever opens.
 *
 * An explicit manifest gives the same lazy per-page loading without the
 * dependency fan-out: only the page that is actually requested is fetched.
 *
 * The trade-off is that a typo in an Inertia component name now fails at
 * runtime instead of at build time, so \`npm run build\` also runs the
 * generator and the manifest is committed.
 */
export const pageManifest = {
${lines.join('\n')}
};
`;

writeFileSync(OUT, content, 'utf-8');
console.log(`wrote ${OUT} with ${files.length} pages`);
