# resources/js/types

This directory is the **canonical home** for shared TypeScript type definitions used
across the Vue 3 frontend (`resources/js/**`).

## Status: Phase 2 will populate this

The directory was created during **Phase 1 — Track D (DevX infrastructure)** to:

1. Reserve the path so the `@/types/*` import alias resolves cleanly while the
   `tsconfig.json` `strict` migration is still in progress.
2. Prevent ad-hoc `.d.ts` files from being scattered across feature folders.

**Current contents:**

| File | Purpose |
| --- | --- |
| `README.md` | This file — explains the directory's role. |

## Planned Phase 2 contents

Once the TypeScript migration begins, this folder will hold shared contracts
derived from the Inertia page props and the API resources exposed by the Laravel
backend. Expected files:

- `work-order.ts` — `WorkOrder`, `WorkOrderItem`, `WorkOrderStatus`, …
- `customer.ts` — `Customer`, `Vehicle`, …
- `invoice.ts` — `Invoice`, `InvoiceLine`, `Payment`, …
- `quote.ts` — `Quote`, `QuoteLine`, …
- `api.ts` — generic envelope types (`Paginated<T>`, `ApiError`, …)
- `index.ts` — re-exports for `@/types` convenience.

## Rules for additions

- **No runtime code** — only `type` and `interface` declarations (plus
  pure helpers like `readonly` mapped types).
- All exports must be **explicit** — no `export *`.
- Files are `.ts` (not `.d.ts`) so they can host mapped types and generics.
- Mirror the shape of the Laravel API Resource / Inertia shared props 1-to-1
  where possible; do not invent fields the backend does not send.
- If you add a type, also add a Vitest unit test under
  `resources/js/types/__tests__/` that exercises the `Brand<T, K>` nominal
  pattern (when introduced) and any `Pick`/`Omit` utilities.

## Related configuration

- `tsconfig.json` — sets `strict: true` and `@/*` path alias to `./resources/js/*`.
- `vitest.config.ts` — picks up any `*.test.ts` files inside this folder.
- `package.json` — `type-check` script (non-blocking during migration) and
  `test` script (Vitest) consume these definitions.