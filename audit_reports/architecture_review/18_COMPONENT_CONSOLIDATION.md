# Vue Component Consolidation — What Was Actually Duplicated

**Date:** 2026-11-11
**Goal:** remove the highest-duplication component pairs **without** changing any
rendered behaviour.

---

## 1. The method: measure first, extract the logic, keep the markup

The audit listed the duplication by file pair and by line count. Before
changing anything, each pair was measured on two axes:

1. **Are the `<script>` blocks identical?** — this is the *logic*.
2. **Have the templates diverged?** — this decides whether the components
   can share a template or must keep their own.

The two axes give different answers, and they determine the right fix:

| Logic | Markup | Verdict |
| :--- | :--- | :--- |
| identical | identical | Merge into one component |
| identical | diverged | **Extract the logic into a composable**; each component keeps its markup |
| diverged | diverged | Leave it alone — the duplication is superficial |

The third row is the one the audit's line counts could not distinguish. Two
pairs turned out to be in it.

---

## 2. `HelpCenterTab.vue` ↔ `Help/Index.vue` — the video-URL resolver

**Three** byte-identical copies of `getVideoEmbed()`:

```
resources/js/Pages/System/Support/Tabs/HelpCenterTab.vue
resources/js/Pages/System/Help/Index.vue
resources/js/Pages/Help/Index.vue
```

```
$ for f in <the three>; do awk '/^const getVideoEmbed = \(url\) => \{/,/^\};/' "$f" | md5; done
52307755f970978b083aa8c10169b705
52307755f970978b083aa8c10169b705
52307755f970978b083aa8c10169b705
```

**Identical down to the MD5.** The function is pure — no component state —
so it was moved to `resources/js/Composables/useVideoEmbed.js` and the
three call sites now use `const { getVideoEmbed } = useVideoEmbed();`.

The **templates** of these files have genuinely diverged (2,890 diff lines:
one is a tab fragment, the other is a full page with its own layout), so
merging the components was **not** the right move. Only the shared pure
function was extracted.

### The behaviour is pinned by a test

`resources/js/Composables/useVideoEmbed.test.js` — **16 tests**, covering
every branch of the original implementation: the empty/non-string guards,
the "not a video" case, the five YouTube URL shapes (watch, `youtu.be`,
shorts, embed, live), the privacy-preserving `youtube-nocookie.com` host,
two Vimeo shapes, the three direct file extensions with and without a
query string, an already-embedded player URL, and whitespace trimming.

The test was written **against the original implementation's behaviour**,
before the extraction, so it could not silently change what a user sees.

---

## 3. `CompanySettings.vue` ↔ `CompanySettingsTab.vue` — the whole form

The strongest case in the audit:

```
$ diff <(awk '/<script setup/,/<\/script>/' CompanySettings.vue) \
       <(awk '/<script setup/,/<\/script>/' CompanySettingsTab.vue)
6d5
< import SystemLayout from '@/Layouts/SystemLayout.vue';
8d6
< import PageHeader from '@/Components/PageHeader.vue';
```

**Two lines of difference** — and both are layout imports used only by the
standalone page's template. The ~200 lines of behaviour were **identical**.

The templates have since diverged (637 diff lines), so the components
cannot share markup. The behaviour was moved to
`resources/js/Composables/useCompanySettingsForm.js`, and each component
keeps its own template:

```js
const {
    form, vatMethodOptions, vatPreview,
    logoInput, uploading, logoUrl, logoPreview,
    stampInput, uploadingStamp, stampUrl, stampPreview,
    onLogoSelect, confirmRemoveLogo,
    onStampSelect, confirmRemoveStamp,
    submit,
} = useCompanySettingsForm(props);
```

The composable owns: the Inertia form, the VAT method options, the live
VAT preview (computed from a 100.00 sample line), the logo and stamp
upload/remove flows (each with its own CSRF handling, blob-URL cleanup and
`window.location.reload()`), and the submit.

**Two variables were dropped from the destructuring** — `logoUrl` and
`stampUrl` — because the templates use `logoPreview` / `stampPreview` and
never the raw URLs. ESLint flagged them as unused, and removing them
silenced the warning rather than leaving dead bindings behind.

---

## 4. `PurchaseInvoiceFormModal.vue` ↔ `PurchaseOrderFormModal.vue` — **not touched**

The script blocks differ by 122 lines out of ~250 — this pair is in the
**third row** of the table in §1: the logic has diverged, so the
duplication is superficial. Merging them would change behaviour in a
purchasing flow, which is exactly the kind of change that does not belong
in a deduplication round.

**Decision: left alone.** It needs its own analysis.

---

## 5. What was NOT done

| Option | Why rejected |
| :--- | :--- |
| Merge the whole `HelpCenterTab` / `Help/Index` components | Their templates have genuinely diverged (2,890 diff lines). A merge would rewrite a 1,100-line page in each of three places. |
| Merge the `CompanySettings` components' templates | Same reason (637 diff lines), plus one of them is a standalone page with its own layout. |
| Merge the purchasing modals | The logic has already diverged. Not a deduplication task. |
| Extract the `Help` components' *default content* (the built-in FAQ/category/tutorial lists) | A candidate, but it is a separate change with its own risk, and it was not needed to close this round. |

---

## 6. The numbers

| | Before | After |
|---|---|---|
| Copies of `getVideoEmbed()` | **3** | **1** (a shared composable) |
| Copies of the company-settings form logic | **2** (~200 lines each) | **1** |
| Files changed | — | 5 Vue files + 2 new composables |
| New tests | — | **16** (`useVideoEmbed.test.js`) |
| `npm run build` | ✅ | ✅ |
| `npx eslint resources/js` | 0 errors | **0 errors** |
| `npx vitest run` | 47 files / 168 tests | **48 files / 184 tests** |

The two Vue files that kept a `logoUrl` / `stampUrl` binding would have
carried two new ESLint warnings; they were removed instead.

---

## 7. The rule this round followed

> **Duplicate logic is a defect. Duplicate markup is not — until the markup
> is what makes the logic diverge.**

The audit's line counts measured *files*. What actually mattered was
*logic*. Two of the three top pairs turned out to be "identical logic,
diverged markup", which the right fix for is a composable — not a merge,
and not a 1,100-line rewrite.

This is the same lesson the tax round produced, from the other direction:
**measure the thing that matters, not the thing that is easy to count.**
