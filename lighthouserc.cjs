// Lighthouse CI configuration for the public landing page.
//
// Run locally:   npx lhci autorun
// Budgets are deliberately set BELOW today's measured values so the build
// fails if a change makes the page heavier. Raise them only with a reason.
//
// Baseline (docs/landing-preview/baseline/baseline.json):
//   performance 49 | accessibility 75 | best-practices 81 | seo 92
//   TBT 1130ms | ITT 7.2s | LCP 1.8s | FCP 1.8s | total 3,623 KiB
module.exports = {
    ci: {
        collect: {
            url: ['http://carag-v2.test/landing-preview'],
            numberOfRuns: 3,
            settings: {
                preset: 'desktop',
                formFactor: 'desktop',
                screenEmulation: { disabled: true },
                throttlingMethod: 'provided',
            },
        },
        assert: {
            // --- Score floors -------------------------------------------------
            assertions: {
                'categories:performance': ['error', { minScore: 0.7 }],
                'categories:accessibility': ['error', { minScore: 0.95 }],
                'categories:best-practices': ['error', { minScore: 0.9 }],
                'categories:seo': ['error', { minScore: 0.95 }],

                // --- Main-thread budget (the real bottleneck today) -----------
                'total-blocking-time': ['error', { maxNumericValue: 300 }],
                'max-potential-fid': ['error', { maxNumericValue: 300 }],
                'interactive': ['error', { maxNumericValue: 5000 }],

                // --- Paint ----------------------------------------------------
                'largest-contentful-paint': ['error', { maxNumericValue: 4000 }],
                'first-contentful-paint': ['error', { maxNumericValue: 2500 }],

                // --- Layout stability ---------------------------------------
                'cumulative-layout-shift': ['error', { maxNumericValue: 0.1 }],

                // --- Payload --------------------------------------------------
                'total-byte-weight': ['error', { maxNumericValue: 1_500_000 }],
                'unused-javascript': ['warn', { maxNumericValue: 200_000 }],
                'bootup-time': ['error', { maxNumericValue: 1500 }],
            },
        },
        upload: {
            target: 'filesystem',
            outputDir: './docs/landing-preview/lhci-report',
        },
    },
};
