# Refactor Plan: Company/Index.vue (3637 lines → 9 files)

> **Status:** stub created, plan documented, lift in progress.
> **Owner:** TBD
> **Risk class:** medium — touches a page that runs the Settings
> area, but each step keeps the parent component behaviorally
> identical (the refactor is purely a code-organization change).

## Why

`resources/js/Pages/Settings/Company/Index.vue` is the largest Vue
file in the project at 3637 lines. It owns 9 tabs (Profile, Contact,
VAT, ZATCA, Admin, Subscription, Subscription History, Revenue,
Invoices) and 1 modal (Password Check). Each tab has its own
section, its own state, and its own save handler. The file
takes ~10 seconds to lint and a real risk to navigate.

## Plan

Split the file into 9 single-file components, one per tab. Each
component receives the data it needs as props and emits events
back to the parent for cross-tab state changes (e.g. a save
refreshing the page-level toast).

### Target structure

```
resources/js/Pages/Settings/Company/
├── Index.vue                          # parent (orchestration only, ~200 lines)
├── Tabs/
│   ├── ProfileTab.vue                 # Logo + entity profile
│   ├── ContactTab.vue                 # Phone/email + address
│   ├── VatSettingsTab.vue            # VAT rate, settings, explanation modal
│   ├── ZatcaTab.vue                  # ZATCA onboarding, CSID, certificates
│   ├── AdminUserTab.vue              # Admin user management
│   ├── SubscriptionTab.vue           # Active plan, upgrade
│   ├── SubscriptionHistoryTab.vue     # ✅ stub created
│   ├── RevenueExpensesTab.vue        # Revenue/expense dashboard
│   └── CompanyInvoicesTab.vue        # Recent invoices
└── Modals/
    └── PasswordCheckModal.vue        # Re-auth before sensitive ops
```

### Why tabs as components, not composables?

- Each tab is a *layout*: it has a header, a form, and a save
  button. Composables are for *behavior*; layouts want
  encapsulation.
- The parent stays as a thin shell: layout (AppLayout, page
  header, tab navigation) + activeTab state + toast handling.
- Each tab's "save" can call the same parent method via emit, or
  have its own composable. Decide per tab as we go.

### Migration order

Order is from the simplest (lowest risk, fewest dependencies) to
the most complex (highest risk, most cross-tab coupling).

1. **SubscriptionHistoryTab** ✅ stub created (just placeholder text,
   the real body still lives inline; we can lift at any time).
2. **ContactTab** — pure form, no API beyond PATCH /settings/system.
3. **ProfileTab** — same, plus a logo upload (multipart) that's
   already isolated via LogoUploadModal.
4. **VatSettingsTab** — has an inline modal; lift the modal
   separately first, then the tab.
5. **AdminUserTab** — depends on the employee/role list; lift
   last among "form" tabs.
6. **SubscriptionTab** — has a "current plan" card + upgrade flow;
   medium complexity.
7. **RevenueExpensesTab** — has its own data fetches
   (useInvoices, useExpenses); the largest of the tabs.
8. **CompanyInvoicesTab** — depends on RevenueExpensesTab's data
   shape. Lift after #7.
9. **ZatcaTab** — has the most domain logic; saved for last.

### Process

For each tab:

1. Create `Tabs/<Name>Tab.vue` with the full section as-is.
2. Move the section's local state (refs, computed) into the new
   component. Lift save handlers to a composable if reused.
3. In `Index.vue`, replace the `<div v-if="activeTab === 'foo'">`
   block with `<FooTab @saved="..." />`.
4. Run the full test suite + the i18n coverage test to make sure
   nothing regressed.
5. Commit as a single refactor commit per tab — easy to bisect if
   a regression slips in.

### Acceptance criteria

- Each tab component is **under 500 lines**.
- The parent `Index.vue` is **under 300 lines** (just layout +
  navigation + tab orchestration).
- No behavior change. Every existing test still passes.
- The i18n coverage test still passes (no new missing keys).
- `npm run lint` (when ESLint is wired) and `php artisan test`
  both pass.

### Out of scope

- Changing the visual design of any tab. The lift is purely a
  code-organization change; visual diffs should be identical
  before/after.
- Splitting `useInvoices`, `useExpenses`, etc. into separate
  composables. That's a Tier 2 refactor (state management).
- Adding tests for each tab. We have E2E coverage via Playwright
  in the future; per-tab unit tests would be nice but aren't
  required to land the split.
