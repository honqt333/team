# Track A — Design System Foundation

> Phase 1 / Sprint 1: tokens + base App components.
> Owner: `frontend-dev` · Status: **done**

## TL;DR

Carag V2 now has a single source of truth for its visual language
(`resources/js/Design/`) and a set of token-driven App components
(`resources/js/Components/App/`) that the rest of Phase 1 (and Phase 2)
should adopt.

| Layer | File(s) | Purpose |
| --- | --- | --- |
| Tokens (JSON) | `Design/tokens.json` | Machine-readable design tokens (DTCG-style) |
| Tokens (CSS)  | `Design/tokens.css`  | CSS custom properties consumed by every component |
| Reset         | `Design/reset.css`   | Modern, RTL-friendly CSS reset |
| Typography    | `Design/typography.css` | Arabic-first webfont stack + fluid type scale |
| App components | `Components/App/App*`  | Button, Input, Select, Textarea, Checkbox (+ stories) |
| Bootstrap     | `resources/js/app.js` | Loads tokens in order, registers App components globally |

---

## 1. The Token System

All values live in **one** file. Components never write hex — they
reference variables.

### Brand

- **Primary**: gold `#fbbf24` (used for CTAs and the brand surface).
- **Base**: slate (`#0f172a` / `#1e293b` / `#f1f5f9`) for surfaces and
  text. The slate scale matches the existing Carag splash.
- **On-brand text**: slate-900 (`#0f172a`) — gold + dark slate gives
  WCAG AA contrast.

### Available variables (full list in `tokens.css`)

#### Color
`--color-brand-primary`, `--color-brand-primary-hover`, `--color-brand-primary-active`,
`--color-brand-primary-soft`, `--color-brand-on`,
`--color-background`, `--color-surface`, `--color-surface-muted`, `--color-surface-raised`,
`--color-border`, `--color-border-strong`,
`--color-text-primary`, `--color-text-secondary`, `--color-text-muted`, `--color-text-inverse`,
`--color-success`, `--color-warning`, `--color-danger`, `--color-info` (each with `*-soft`),
`--color-focus-ring`, `--color-focus-ring-soft`.

#### Space
`--space-0` (0) → `--space-24` (6rem) — Tailwind-compatible scale.

#### Radius
`--radius-none`, `sm`, `md`, `lg`, `xl`, `2xl`, `full`.

#### Typography
`--font-family-sans`, `--font-family-display`, `--font-family-mono`,
`--font-size-xs` → `--font-size-4xl`, `--font-weight-*`, `--line-height-*`.

#### Motion
`--motion-duration-fast` (120ms) → `slower` (400ms),
`--motion-ease-standard` / `emphasized` / `decelerated`.

#### Shadow
`--shadow-xs` → `--shadow-xl`, plus `--shadow-focus` (brand-tinted focus ring).

#### Z-index
`--z-hide`, `base`, `raised`, `dropdown`, `sticky`, `modal`, `popover`, `toast`.

#### Size
`--size-control-sm` / `md` / `lg`, `--size-icon-sm` / `md` / `lg`.

### Theme switching

Two selectors work, **simultaneously**:

```css
:root,                              /* light default */
:root[data-theme='light']           /* explicit light */
:root.dark,                         /* Tailwind's <html class="dark"> */
:root[data-theme='dark']            /* explicit data attribute */
```

The existing `resources/js/theme.js` toggles `html.classList` for
`dark` — no changes required. Both selectors resolve to the same
variables, so either mechanism (or both) work.

> **Note:** we keep `dark` as the canonical class to match Tailwind's
> `darkMode: 'class'` config in `tailwind.config.js`.

### Token JSON

`tokens.json` is structured for future DTCG-style tooling. It is
**not** consumed at build time yet — it documents the same values
in a portable format and is ready for a future `style-dictionary` /
`@token-studio` pipeline. Phase 1 keeps the CSS variables as the
runtime source of truth.

---

## 2. Components

All components live in `resources/js/Components/App/` and are
**registered globally** in `app.js` so you can use them anywhere
in any page or component without an import:

```vue
<AppButton variant="primary">حفظ</AppButton>
<AppInput v-model="form.name" label="الاسم" />
<AppSelect v-model="form.city" label="المدينة" />
<AppTextarea v-model="form.notes" label="ملاحظات" />
<AppCheckbox v-model="form.agreed" label="أوافق" />
```

> Importing locally still works (preferred for tree-shaking in
> modal/page scopes): `import AppButton from '@/Components/App/AppButton.vue'`.

### 2.1 `AppButton.vue`

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `variant` | `primary \| secondary \| ghost \| danger` | `primary` | Gold / outline / text-only / red |
| `size` | `sm \| md \| lg` | `md` | 36 / 44 / 52 px control heights |
| `as` | `button \| a \| link` | `button` | `link` renders Inertia `<Link>` |
| `href` / `to` | any | — | Required for `as="a"` / `as="link"` |
| `type` | `button \| submit \| reset` | `button` | Only when `as="button"` |
| `loading` | `boolean` | `false` | Spinner, disables button, sets `aria-busy` |
| `disabled` | `boolean` | `false` | Visual + `aria-disabled` |
| `block` | `boolean` | `false` | Full-width |
| `iconLeftPath` / `iconRightPath` | SVG path `d` | — | Convenience for one-line icons |
| `iconLeft` / `iconRight` slots | — | — | For richer iconography |

Slots: `default` (label), `iconLeft`, `iconRight`.

Example:

```vue
<AppButton variant="primary" :loading="form.processing" @click="submit">
    حفظ أمر العمل
</AppButton>

<AppButton as="link" :href="route('customers.show', customer.id)" variant="secondary">
    فتح العميل
</AppButton>
```

### 2.2 `AppInput.vue`

| Prop | Type | Default |
| --- | --- | --- |
| `id` | `string` | auto |
| `v-model` | `string \| number` | — |
| `label` / `hint` / `error` | `string` | — |
| `type` | `text \| email \| tel \| number \| password \| url \| search` | `text` |
| `required` / `disabled` / `readonly` | `boolean` | `false` |
| `min` / `max` / `step` | `number` | — (for `type="number"`) |
| `suffix` | `string` | — | Currency suffix for `type="number"` |
| `prefix` | `string` | — | Inline prefix |

**A11y**: every input sets `aria-invalid`, `aria-required`, and
`aria-describedby` pointing at the hint / error elements.

Example:

```vue
<AppInput
    v-model="form.amount"
    label="المبلغ"
    type="number"
    suffix="ر.س"
    :error="form.errors.amount"
/>
```

### 2.3 `AppSelect.vue`

Same field shape as `AppInput` (label/hint/error/a11y) wrapping the
existing `SelectInput.vue`. Custom chevron, RTL-aware, tokens-only
visuals.

```vue
<AppSelect v-model="form.status" label="حالة أمر العمل" required>
    <option value="open">مفتوح</option>
    <option value="in_progress">قيد التنفيذ</option>
    <option value="done">منتهي</option>
</AppSelect>
```

### 2.4 `AppTextarea.vue`

A clean token-driven implementation (no legacy wrapper). Supports
rows, maxlength with live counter, error/hint, full a11y.

```vue
<AppTextarea
    v-model="form.notes"
    label="ملاحظات"
    :rows="4"
    :maxlength="500"
/>
```

### 2.5 `AppCheckbox.vue`

Wraps the legacy `Checkbox.vue`. Adds label/hint/error, full a11y,
and a token-styled check visual (gold-tinted when checked).

```vue
<AppCheckbox v-model="form.agreed" label="أوافق على الشروط" required />
```

---

## 3. Stories

Histoire isn't yet in the dependency tree (Track D will add it). To
make CI happy *now*, each component ships a colocated `.story.vue`
file that's a self-contained visual reference. When Histoire lands:

1. Track D adds `histoire` to devDependencies + a `pnpm story` script.
2. Track A (or the maintainer) wraps each block in `<Story title="...">`.
3. The `.story.vue` files become canonical stories.

Until then, render any story by importing it from a page or visiting
the build via the existing dev server (the stories are real Vue
components, not stub text).

---

## 4. RTL Notes

- All component styles use **logical properties** (`margin-inline-start`,
  `padding-inline-end`, `border-inline-start`, `text-align: start`). No
  `ml-*` / `mr-*` / `pl-*` / `pr-*` equivalents leak in.
- `html[dir=rtl]` flips layouts automatically because the layout primitives
  (flex, grid) are direction-agnostic.
- For digit-only contexts (plate numbers, invoice numbers) that must stay
  LTR inside an Arabic page, use the helper class `.app-ltr-numerals`
  from `typography.css`.

---

## 5. Migration / Usage Policy

1. **New code** should use `App*` components. Direct usage of
   `TextInput.vue` / `SelectInput.vue` is allowed but discouraged for
   brand-facing surfaces.
2. **Existing code** keeps working — the legacy components are untouched.
3. **No new hex colors** outside `Design/tokens.css`. If a value is
   missing, add it to `tokens.json` and `tokens.css` and reference it
   as `var(--...)`.
4. **RTL** is the default. Use logical properties everywhere.

---

## 6. Verification

| Check | Command | Result |
| --- | --- | --- |
| Files exist | `ls resources/js/Design/ resources/js/Components/App/` | ✅ 4 + 10 files |
| Build | `npm run build` | ✅ passes (vendor chunk warning is pre-existing) |
| No direct hex in components | `grep -nE '#[0-9a-fA-F]{3,6}' resources/js/Components/App/*.vue` | ✅ empty |
| No `rgb()`/`hsl()` in components | `grep -nE 'rgb\\(\|hsl\\(' resources/js/Components/App/*.vue` | ✅ empty |
| Tokens imported in `app.js` | `grep -n 'Design/tokens.css' resources/js/app.js` | ✅ present |
| Global registration | `grep -nE 'AppButton|AppInput|AppSelect|AppTextarea|AppCheckbox' resources/js/app.js` | ✅ 5 components |
| Legacy components untouched | `git diff --name-only HEAD resources/js/Components/TextInput.vue ...` | ✅ no changes |

---

## 7. Known Limitations / Hand-off

- **No Histoire yet.** Stories are visual-only Vue files; full
  storybook UI lands with Track D.
- **No CSS variable types.** `tokens.json` is documentation-grade; a
  future Phase-2 step can wire `style-dictionary` so JSON is the
  authoritative source.
- **No automated visual regression.** Snapshot tests belong to
  Track D's vitest setup. Components are tested manually for now.
- **`@import` of Google Fonts in `typography.css`** requires network
  access during the first build. If running offline, remove the
  `@import` line — `font-family` already lists local system fallbacks.
- **Track B (AI Foundation)** won't touch any of these files.
  **Track C (Observability)** may add `--color-info` consumers but
  no edits here. **Track D (DevX)** may add type declarations on the
  new components. **Track E (Multi-Tenant)** is unrelated.

---

*Built Phase 1, Sprint 1 — 2026-07-09*
