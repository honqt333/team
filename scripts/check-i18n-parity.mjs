#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(__dirname, '..');
const arPath = path.join(rootDir, 'resources/js/i18n/lang/ar.json');
const enPath = path.join(rootDir, 'resources/js/i18n/lang/en.json');

function flatten(obj, prefix = '', out = {}) {
    for (const [key, value] of Object.entries(obj)) {
        const nextKey = prefix ? `${prefix}.${key}` : key;
        if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
            flatten(value, nextKey, out);
        } else {
            out[nextKey] = value;
        }
    }
    return out;
}

const ar = flatten(JSON.parse(fs.readFileSync(arPath, 'utf8')));
const en = flatten(JSON.parse(fs.readFileSync(enPath, 'utf8')));

const arKeys = Object.keys(ar);
const enKeys = Object.keys(en);

const missingInEn = arKeys.filter((k) => !(k in en));
const missingInAr = enKeys.filter((k) => !(k in ar));
const emptyInAr = arKeys.filter((k) => String(ar[k] ?? '').trim() === '');
const emptyInEn = enKeys.filter((k) => String(en[k] ?? '').trim() === '');

console.log(`ar.json keys: ${arKeys.length}`);
console.log(`en.json keys: ${enKeys.length}`);
console.log(`Missing in en.json (${missingInEn.length}):`, missingInEn);
console.log(`Missing in ar.json (${missingInAr.length}):`, missingInAr);
console.log(`Empty in ar.json (${emptyInAr.length}):`, emptyInAr);
console.log(`Empty in en.json (${emptyInEn.length}):`, emptyInEn);

if (missingInEn.length > 0 || missingInAr.length > 0 || emptyInAr.length > 0 || emptyInEn.length > 0) {
    process.exitCode = 1;
}
