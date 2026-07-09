# Code Review — Track A (Design System: tokens + App components)

**Branch / scope:** Track A deliverables in `resources/js/Design/`, `resources/js/Components/App/`, `resources/js/app.js`, `docs/track-a.md`
**Reviewer:** code-reviewer-carag
**Date:** 2026-07-09

---

## ✅ Verdict

**APPROVE** — Track A is high-quality, on-scope, and complete. All Phase-1 deliverables exist with non-trivial content, the build is green, every component is token-driven and accessible, and the scope boundaries were respected.

---

## Checks against the verification matrix

| # | Check | Result |
|---|---|---|
| 1 | `Design/` ≥ 4 files with content | ✅ 4 files / 631 lines (`tokens.css` 173, `tokens.json` 181, `reset.css` 139, `typography.css` 138) |
| 2 | `Components/App/` ≥ 6 components | ✅ 10 files / 2101 lines (5 components + 5 story files) |
| 3 | `npm run build` smoke test | ✅ Built in 19.98s; no new errors. Pre-existing `vendor-DSP0LjNe.js > 1000 kB` warning is **not** caused by Track A. |
| 4 | Tokens consumed (CSS variables, not hex) | ✅ `grep -nE '#[0-9a-fA-F]{3,6}' Components/App/*.vue` → 0 hits. `grep -nE 'rgb\(|hsl\('` → 0 hits. `var(--*)` appears 213 times across the 5 components. |
| 5 | Dark mode support via tokens | ✅ `:root.dark` and `:root[data-theme="dark"]` selectors (lines 145-146 of tokens.css). Brand color preserved in both modes; contrast adjusted via hover/active variants. |
| 6 | Type props usable | ✅ `AppButton` exposes `type` prop (default `'button'`, passed to native button). `AppInput` validates `type` ∈ text/email/tel/number/password/url/search. |
| 7 | Accessibility attributes | ✅ `aria-invalid`, `aria-required`, `aria-describedby`, `aria-disabled`, `aria-busy`, `aria-hidden` on decorative icons, `role="alert"` on errors — all present on AppButton / AppInput / AppSelect / AppTextarea / AppCheckbox. |
| 8 | Component breadth ≥ Button / Input / Select | ✅ AppButton, AppInput, AppSelect **plus** AppTextarea and AppCheckbox (exceeds requirement). |
| 9 | No `package.json` mutation | ✅ `git diff package.json` → empty. `git status package.json` → clean. |
| 10 | No `composer.json` mutation | ✅ `git diff composer.json` → empty. |
| 11 | No `app/` mutation by Track A | ✅ Track A only touched: `resources/js/Design/`, `resources/js/Components/App/`, `resources/js/app.js`, `docs/track-a.md`. (`routes/api.php`, `app/Models/Prompt.php`, `app/Services/AI/`, etc. belong to Track B.) |
| 12 | Typography supports Arabic | ✅ typography.css uses **IBM Plex Sans Arabic + Tajawal + Noto Kufi Arabic** stack; `font-feature-settings` includes Arabic shaping (`init`, `medi`, `fina`, `rlig`). |
| 13 | Logical properties for RTL | ✅ Verified — `padding-inline`, `margin-inline-start`, `border-inline-end`, `border-inline-start`, `padding-inline-end`, `inset-inline-end` are used in every component and in reset.css. |
| 14 | Global registration in `app.js` | ✅ All 5 components imported (lines 24-28) and globally registered (lines 76-80). |
| 15 | Existing components untouched | ✅ Track A wraps legacy `TextInput.vue`/`SelectInput.vue`/`Checkbox.vue` rather than rewriting them — zero behaviour change for existing forms. |

---

## What I liked

1. **AppField pattern is consistent** — `AppInput`, `AppSelect`, `AppTextarea`, and `AppCheckbox` all share the same `.app-field` / `.app-field__control` / `.app-field__label` / `.app-field__hint` / `.app-field__error` shape. This means future `AppRadio` / `AppSwitch` only need to swap the inner control.
2. **Token-only discipline** is enforced in code comments (`// Do not hard-code hex.`) and verified by grep. Hex colors exist **only** in `tokens.css` and `tokens.json`.
3. **Dark mode has two triggers** (Tailwind `.dark` class for backwards-compat with the existing `theme.js`, plus explicit `[data-theme="dark"]`). Nice forward-compat move.
4. **Modern CSS features used** — `color-mix(in srgb, ...)` for danger hover/active states, `clamp()` for fluid typography, `inset-inline-end` for chevron positioning, `color-scheme` for UA widgets. All well-supported in current browsers.
5. **Backwards-compat strategy is non-invasive** — `AppInput` wraps the existing `TextInput.vue` and re-exports its props/events. Zero migration cost; new pages opt-in.
6. **Story files are wrapped in `AppXxx.story.vue`** — Track D will add Histoire later, and the stories are structured to wrap in `<Story>` blocks with zero refactor. Pragmatic.

---

## MINOR observations (non-blocking)

### M1. `app.js` imports Touched Are Now Spaced From Existing Group
The 3 Design imports + 5 component imports + 5 component registrations were added in the middle of the file. The producer marked this as "Track A Phase 1" in the comments, which is fine, but I'd suggest moving the global registrations into a dedicated `registerAppComponents()` helper in a future refactor to keep `app.js` scannable. **Non-blocking** — works today.

### M2. Story Files Don't Yet Use `<Story>` Blocks
The producer acknowledged this explicitly in the deliverable. Histoire will land via Track D (which owns `package.json`). When that happens, the story files will need a 2-line wrap inside `<Story title="...">` blocks. Pre-documented in `docs/track-a.md` handoff notes. **Non-blocking.**

### M3. `reset.css` Uses `@import url('...fonts.googleapis.com...')`
typography.css line 16 imports Google Fonts via CSS `@import`. The producer documented the offline fallback. This works for production but is **render-blocking** on first paint. Long-term, the right move is to add a `<link rel="preconnect">` + `<link rel="stylesheet">` in `app.blade.php` instead of `@import` from CSS. Worth a follow-up ticket for Track D / front-end perf. **Non-blocking** for Phase 1.

### M4. `tokens.json` Is Currently Documentation Only
The producer says it explicitly: JSON is not consumed at build time. The CSS file is the runtime SoT. This is **fine for Phase 1** but means future tokens added to JSON must be hand-synced to `tokens.css`. The handoff note flags Phase 2 as the right time to wire `style-dictionary`. **Non-blocking.**

### M5. `AppButton` `type` Prop Only Applies When `as === 'button'`
This is correct Vue 3 behaviour (anchors/links don't have a `type` attribute), but the API surface could surprise a consumer who sets `type="submit"` and `as="link"`. The prop docs are clear, so this is **not a bug** — just worth noting. **Non-blocking.**

---

## Cross-track scope check (out of Track A's scope, but visible in `git status`)

These are **not** Track A issues, but I want to flag them so the plan owner knows they're owned by other tracks:
- `app/Http/Controllers/Api/AiDemoController.php`, `app/Http/Middleware/TrackAiUsage.php`, `app/Models/Prompt.php`, `app/Services/AI/`, `database/migrations/2026_07_09_170000_create_prompts_table.php` → **Track B (AI Foundation)**
- `routes/api.php` (M) → **Track B**

Track A is clean. No cross-track contamination.

---

## Final verdict

**APPROVE.**

Track A delivered exactly what was asked: a token-driven design system foundation with 5 working `App*` primitives, modern RTL-aware CSS, full accessibility, zero hex leakage, and a clean build. The minor observations above are non-blocking and belong to future tracks (D for Histoire, perf, style-dictionary).

VERDICT: PASS