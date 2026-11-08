#!/usr/bin/env bash
#
# Measure a page against the PRODUCTION build.
#
# WHY THIS EXISTS
# ---------------
# When the Vite dev server is running, Laravel's `@vite` directive emits
# script tags pointing at http://host:5173 and the browser fetches the
# SOURCE files instead of the built ones. Every measurement taken in that
# state is wrong:
#
#   the tenant public site measured 4,900 KiB against the dev server and
#   1,271 KiB against the production build — a 4x difference for the same
#   page.
#
# The dev server also leaves `public/hot` behind when it is killed, and
# Laravel keeps serving from that dead URL until the file is removed. That
# breaks the page entirely.
#
# Usage:
#   scripts/measure.sh <url> [preset]      # e.g. scripts/measure.sh https://carag-v2.test/landing-preview desktop
#
set -euo pipefail

URL="${1:?usage: measure.sh <url> [preset]}"
PRESET="${2:-desktop}"

# Stop any dev server and clear the hot file, so @vite serves the build.
#
# The hot file is REMOVED, not restored on exit. Restoring it after killing
# the dev server points Laravel at a dead URL, and every script tag on every
# page 404s — the site renders blank. The production build is the correct
# end state for a machine that is being measured.
DEV_PIDS="$(lsof -ti:5173 2>/dev/null || true)"
if [ -n "$DEV_PIDS" ]; then
    echo "$DEV_PIDS" | xargs -r kill -9 2>/dev/null || true
    sleep 1
    echo "Stopped the Vite dev server (pid $DEV_PIDS)."
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [ -f "$ROOT/public/hot" ]; then
    rm -f "$ROOT/public/hot"
    echo "Removed public/hot — Laravel will serve the production build."
fi

cd "$ROOT"

echo "Measuring $URL (preset=$PRESET, production build)..."

# Drop the runs of the previous measurement, otherwise the median below mixes
# two different pages.
rm -f "$ROOT/.lighthouseci"/lhr-*.json "$ROOT/.lighthouseci"/lhr-*.html 2>/dev/null || true

npx lhci collect \
    --url="$URL" \
    --settings.preset="$PRESET" \
    --settings.formFactor=desktop \
    --settings.screenEmulation.disabled=true \
    --settings.throttlingMethod=provided \
    --numberOfRuns=5 >/dev/null 2>&1

node -e '
const fs = require("fs");
const path = require("path");
const dir = path.join(process.cwd(), ".lighthouseci");

/*
 * Pick the representative run by the MEDIAN performance score.
 *
 * A single run is noisy: the machine this runs on is shared, and a busy
 * machine pushed the server response time from 130 ms to 760 ms on the very
 * same page — 94 points of performance score, with the byte weight, the
 * request count and the page itself completely unchanged.
 *
 * The previous version of this script read the manifest.json that only
 * `lhci autorun` writes; `lhci collect` does not write one, so it fell
 * back to "the newest file" — which is the run that happened to finish
 * last, i.e. a coin flip. On the tenant site that reported 65 while the
 * median run was 94.
 */
const files = fs.readdirSync(dir)
    .filter((f) => f.startsWith("lhr-") && f.endsWith(".json"))
    .map((f) => ({ f, m: fs.statSync(path.join(dir, f)).mtimeMs }))
    .sort((a, b) => b.m - a.m);

if (files.length === 0) {
    console.error("No Lighthouse run was produced.");
    process.exit(1);
}

const runs = files.map(({ f }) => JSON.parse(fs.readFileSync(path.join(dir, f), "utf8")));
const score = (d) => (d.categories?.performance?.score || 0) * 100;

const sorted = [...runs].sort((a, b) => score(a) - score(b));
const d = sorted[Math.floor((sorted.length - 1) / 2)];

const perRun = sorted.map(score);
console.log(`runs:    ${runs.length} | performance per run: ${perRun.join(", ")} | median: ${perRun[Math.floor((perRun.length - 1) / 2)]}`);
console.log(`load:    ${require("os").loadavg().map((n) => n.toFixed(1)).join(" / ")} (1 / 5 / 15 min)`);
console.log("");

const scores = Object.fromEntries(
    Object.entries(d.categories).map(([k, v]) => [k, Math.round((v.score || 0) * 100)])
);
console.log("url:    ", d.finalDisplayedUrl);
console.log("scores: ", scores);
for (const id of [
    "first-contentful-paint",
    "largest-contentful-paint",
    "total-blocking-time",
    "cumulative-layout-shift",
    "interactive",
    "server-response-time",
    "total-byte-weight",
]) {
    const a = d.audits[id];
    if (a) console.log(`  ${id.padEnd(26)} ${a.displayValue}`);
}

/*
 * Guard against a polluted measurement.
 *
 * The Vite dev server can be (re)started by another process while the
 * measurement runs. When it is up, Laravel serves the SOURCE files and the
 * page weight reads several times too high — the tenant site measured
 * 4,900 KiB against the dev server and 1,271 KiB against the build.
 *
 * Rather than report a number that is not about the page, say so.
 */
const requests = d.audits?.["network-requests"]?.details?.items ?? [];
const devRequests = requests.filter((r) => (r.url || "").includes(":5173"));

console.log(`  ${"requests".padEnd(26)} ${requests.length}`);

if (devRequests.length > 0) {
    console.log("");
    console.log("  !! WARNING: the Vite dev server was serving during this run.");
    console.log("     " + devRequests.length + " request(s) went to :5173.");
    console.log("     The weight and timings below are NOT the production numbers.");
    console.log("     Stop the dev server (and delete public/hot) and re-run.");
}
'
