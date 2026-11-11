# خدمة برو — دليل الهوية البصرية (Brand Guidelines)

> **Brand identity for Khidmh Pro / خدمة برو**
> The official name is **خدمة برو** (Arabic) / **Khidmh Pro** (English).
> The name is NEVER baked into the artwork — it is always rendered
> dynamically from `config('app.name_ar')` / `config('app.name')` in the
> backend and `$t('common.app_name')` in the frontend.

---

## 1. The brandmark

A single diagonal gesture from the lower-left to the upper-right that
combines three ideas:

| Element   | Meaning |
| :--- | :--- |
| **Open-end spanner** | The craft: maintenance, service, the technician's tool. |
| **Cog** | The system: inventory, workflow, the machine behind the work. |
| **Upward arrow** | The outcome: growth, progress, moving the business forward. |

The spanner's shaft runs into the arrow's tail, so the eye travels one
continuous line: *tool → motion → result*. The cog interlocks with the
shaft and is punched through by it, so it reads as passing **behind** the
tool rather than sitting on top.

### Source files

| File | Purpose |
| :--- | :--- |
| `public/images/brand/logo.svg` | The app-shell mark (rounded-square, gold on navy). Primary. |
| `public/images/brand/logo-mark.svg` | The bare mark, tight viewBox, transparent background. |
| `public/images/brand/logo-mono.svg` | The bare mark in a single colour (`currentColor`). Use for one-colour print, watermarks, or dark backgrounds. |
| `public/images/brand/logo-*.png` | Raster exports, 16 → 1024. |
| `public/images/brand/favicon.ico` | Multi-size ICO (16 / 32 / 48). |
| `public/images/brand/apple-touch-icon.png` | 180×180, iOS home screen. |
| `public/images/brand/og-image.png` | 1200×630 social share card. |

**The mark carries no text.** The platform name always sits beside it,
rendered from the translation layer.

### Regenerating

The artwork is computed, not hand-plotted:

```bash
node scripts/build-brandmark.mjs public/images/brand   # SVG source
node scripts/build-brand-assets.mjs                     # all PNG / ICO / OG
```

`scripts/build-brandmark.mjs` holds every geometric parameter (head
radius, jaw depth, gear teeth, arrow length) as named constants near the
bottom of the file. Change a constant, re-run both scripts, and the whole
size ladder regenerates consistently.

---

## 2. Colour

The palette is taken from the existing design tokens — **no new colours
were introduced**. Source of truth: `resources/js/Design/tokens.css`.

| Role | Token | Hex | Use |
| :--- | :--- | :--- | :--- |
| Brand gold | `--color-brand-primary` | `#fbbf24` | The mark's highlight, accents. |
| Brand gold mid | `--color-brand-primary-hover` | `#f59e0b` | Gradient mid-stop. |
| Brand gold deep | `--color-brand-primary-active` | `#d97706` | Gradient deep stop, hover. |
| Deep navy | `--color-background` / `--color-brand-on` | `#0f172a` | The mark's shell, the mark's ground. |
| Slate | `--color-surface` | `#1e293b` | Shell gradient top. |

The mark's gradient runs **bottom-left → top-right** in gold, on a navy
rounded square. The gradient uses `gradientUnits="userSpaceOnUse"` so the
spanner and the arrow share one continuous gradient — with the default
`objectBoundingBox` each path gets its own gradient and a seam appears at
the join.

---

## 3. Typography

**IBM Plex Sans Arabic** — the only approved typeface in the system, for
the interface, the reports, and the print screens.

The wordmark is set in the same face. Never substitute Cairo, Figtree, or
Tajawal.

---

## 4. Clear space and minimum size

- **Clear space:** keep at least **25% of the mark's height** clear on all
  sides of the logo lockup.
- **Minimum sizes:** the app-shell mark stays legible down to **16 px**
  (the favicon). Below 16 px, use a simplified single-colour variant.

---

## 5. Where the logo is used

| Context | Asset | Path |
| :--- | :--- | :--- |
| Browser tab | `favicon.ico` + `favicon-32.png` | `/images/brand/` |
| iOS home screen | `apple-touch-icon.png` | `/images/brand/` |
| Auth screens | `logo-128.png` / `logo-256.png` | `/images/brand/` |
| System panel header | `logo-64.png` | `/images/brand/` |
| Tenant logo fallback | `logo-256.png` | via `Tenant::getLogoUrlAttribute()` |
| Print headers | `logo-256.png` | via `logo_url` prop |
| Social sharing | `og-image.png` | `/images/brand/` |

A tenant's own uploaded logo always wins over the platform mark. The
platform mark is only the fallback.

---

## 6. Rules

1. **Never** bake the name into the artwork. Use `$t('common.app_name')`.
2. **Never** re-colour the mark outside the palette above.
3. **Never** stretch, rotate, or add effects to the mark.
4. **Never** place the gold mark on a light background without its navy
   shell — the gold has no contrast against white.
5. Keep the golden clear space; do not crowd the mark with text.
6. The one-colour (`logo-mono.svg`) variant exists for single-colour
   reproduction. Use it rather than desaturating the full-colour mark.
