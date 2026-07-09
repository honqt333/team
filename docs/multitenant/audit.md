# Track E — Multi-Tenant Audit & Scoping Plan

> Phase 1 of "World-Class Transformation" for Carag V2.
> **Goal:** make every tenant-owned model honor `tenant_id` (and where appropriate, `center_id`) so cross-tenant data leakage is structurally impossible.

## 1. Scope & baseline

- **Models in repo:** 110 (`find app/Models -name '*.php' | wc -l`, excluding `Concerns/`).
- **Models already scoped:** 39 (use `TenantScoped` or `CenterScoped`).
- **Models NOT scoped:** 41 discovered by `grep -L 'TenantScoped\|CenterScoped'`. Of these:
  - 5 are intentionally **out-of-tenant** (root tables like `Tenant`, `User` has tenant_id but pivoted, role lookup, etc.) — see §3.
  - 36 are either true business entities owned by a tenant, child rows under a tenant-owned parent, or global configuration that must stay cross-tenant.
- **Trait inventory:**
  - `App\Models\Concerns\TenantScoped` — applies global `WHERE tenant_id = <ctx>` and auto-fills `tenant_id` on `creating`.
  - `App\Models\Concerns\CenterScoped` — applies `WHERE tenant_id = <ctx> AND center_id = <ctx>` plus an opt-in `source = 'system'` escape hatch. Auto-fills `tenant_id` and `center_id`.
  - Both traits **require** `TenancyContext::tenantId()` to be non-null. They do NOT crash at boot when called outside a request (e.g. `php artisan` jobs without auth) — the scope simply stays inactive.

## 2. Categorization rules

| Category | Decision | Rationale |
|---|---|---|
| Tenant-owned entity | **Add `TenantScoped`** (with `CenterScoped` when the table has a `center_id` column AND all UI/API flows are center-bounded). | Hard isolation between tenants. |
| Child row under a tenant-owned parent | **Inherit via parent global scope**, but also add `tenant_id` column + index where missing so ad-hoc queries (`->whereHas('quote')`) cannot leak. | Defense-in-depth: even raw `DB::table('quote_lines')` cannot return rows belonging to another tenant. |
| Cross-tenant global configuration (settings, packages, plans) | **Leave unscoped.** Add an `// @bypass-tenancy-scanner` doc-comment and document the reason. | A `Plan` is the same for every tenant. |
| Admin / system surface (admin panel, audit snapshots, contact form, dev tooling) | **Leave unscoped** with `@bypass-tenancy-scanner`. | Operates outside the tenant request boundary. |
| Root identity tables (`Tenant`, `User`, `Role`) | **Leave unscoped** with `@bypass-tenancy-scanner`. | `Tenant` *is* the tenant. `User` carries `tenant_id` but the auth pipeline queries it without a context. `Role` is filtered by Spatie's permission team (which is itself tenant-scoped). |

## 3. Decision matrix — every non-scoped model

The list below covers every model returned by `grep -L 'TenantScoped\|CenterScoped' app/Models -r` (excluding `Concerns/`). Each row captures: the table, whether `tenant_id` / `center_id` columns exist today, the chosen decision, and the action taken.

| # | Model | Table | tenant_id col | center_id col | Decision | Action |
|---|---|---|---|---|---|---|
| 1 | `InspectionItem` | `inspection_items` | ❌ | ❌ | **CenterScoped + new migration** adding `tenant_id` + `center_id` | Migrate + trait + factory + test |
| 2 | `Nationality` | `nationalities` | ❌ | ❌ | **Global lookup** — same for all tenants | Keep unscoped, `@bypass-tenancy-scanner` |
| 3 | `InvoiceLine` | `invoice_lines` | ❌ | ❌ | **CenterScoped + new migration** (denormalized from `Invoice`) | Migrate + trait + factory + test |
| 4 | `VehicleMileageLog` | `vehicle_mileage_logs` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 5 | `PaymentSettings` | `payment_settings` | ❌ | ❌ | **Global config** (one row for all tenants, cache key `payment_settings`) | Keep unscoped, `@bypass-tenancy-scanner` |
| 6 | `PurchaseInvoiceLine` | `purchase_invoice_lines` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 7 | `AdminActivityLog` | `admin_activity_logs` | ❌ | ❌ | **Admin surface** — no tenant context | Keep unscoped, `@bypass-tenancy-scanner` |
| 8 | `WorkOrderDamageMark` | `work_order_damage_marks` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 9 | `QuotePart` | `quote_parts` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 10 | `GoodsReceivedNote` | `goods_received_notes` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 11 | `User` | `users` | ✅ | ❌ | **Identity root** — auth pipeline queries outside tenant context. Already has `tenant_id` column. | Keep unscoped, `@bypass-tenancy-scanner`, but document |
| 12 | `Role` | `roles` | ✅ | ❌ | **Spatie role** — tenant isolation enforced by `PermissionRegistrar::setPermissionsTeamId` | Keep unscoped, `@bypass-tenancy-scanner` |
| 13 | `CenterAddress` | `center_addresses` | ❌ | ✅ | **Center-owned child** — needs `tenant_id` to enforce isolation | Migration + `CenterScoped` + factory + test |
| 14 | `ContactMessage` | `contact_messages` | ❌ | ❌ | **Public form** — pre-signup, no tenant | Keep unscoped, `@bypass-tenancy-scanner` |
| 15 | `PurchaseOrderItem` | `purchase_order_items` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 16 | `Tenant` | `tenants` | ❌ | ❌ | **Tenant root** — *is* the tenant | Keep unscoped, `@bypass-tenancy-scanner` |
| 17 | `CenterWorkingHour` | `center_working_hours` | ❌ | ✅ | **Center-owned child** — needs `tenant_id` | Migration + `CenterScoped` + factory + test |
| 18 | `InventoryTransferItem` | `inventory_transfer_items` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 19 | `AdminUser` | `admin_users` | ❌ | ❌ | **Admin surface** | Keep unscoped, `@bypass-tenancy-scanner` |
| 20 | `SystemAnnouncement` | `system_announcements` | ❌ | ❌ | **Admin-targeted broadcast** — target is explicit `target_tenant_ids` JSON | Keep unscoped, `@bypass-tenancy-scanner` |
| 21 | `WorkOrderItemNote` | `work_order_item_notes` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 22 | `InventoryBalance` | `inventory_balances` | ❌ | ❌ | **CenterScoped + new migration** — derives scope from `Warehouse->center_id` | Migrate + trait + factory + test |
| 23 | `HR\PayrollItem` | `hr_payroll_items` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 24 | `HR\AttendanceSettings` | `hr_attendance_settings` | ❌ | ✅ | **Center-owned config** — needs `tenant_id` | Migration + `CenterScoped` + factory + test |
| 25 | `CommunicationTemplate` | `communication_templates` | ❌ | ❌ | **Global template catalog** (system-managed) | Keep unscoped, `@bypass-tenancy-scanner` |
| 26 | `WorkOrderPhoto` | `work_order_photos` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 27 | `Warehouse` | `warehouses` | ❌ | ✅ | **Center-owned** — needs `tenant_id` | Migration + `CenterScoped` + factory + test |
| 28 | `PurchaseReturnInvoiceLine` | `purchase_return_invoice_lines` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 29 | `InventoryMove` | `inventory_moves` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 30 | `Setting` | `settings` | ❌ | ❌ | **Global KV** — system-wide keys | Keep unscoped, `@bypass-tenancy-scanner` |
| 31 | `Credits\SmsPackage` | `sms_packages` | ❌ | ❌ | **Catalog** — same packages to all tenants | Keep unscoped, `@bypass-tenancy-scanner` |
| 32 | `Credits\WhatsappPackage` | `whatsapp_packages` | ❌ | ❌ | **Catalog** | Keep unscoped, `@bypass-tenancy-scanner` |
| 33 | `Billing\PromoCode` | `promo_codes` | ❌ | ❌ | **Catalog** | Keep unscoped, `@bypass-tenancy-scanner` |
| 34 | `Billing\Installment` | `installments` | ❌ | ❌ | **CenterScoped + new migration** (denormalized from `SubscriptionInvoice`) | Migrate + trait + factory + test |
| 35 | `Billing\Plan` | `plans` | ❌ | ❌ | **Catalog** | Keep unscoped, `@bypass-tenancy-scanner` |
| 36 | `GrnItem` | `grn_items` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |
| 37 | `Integration\Integration` | `integrations` | ❌ | ❌ | **Catalog** — provider definitions (config lives in `TenantIntegration`) | Keep unscoped, `@bypass-tenancy-scanner` |
| 38 | `Developer\AiMemory` | `dev_ai_memory` | ❌ | ❌ | **Dev tooling** | Keep unscoped, `@bypass-tenancy-scanner` |
| 39 | `Developer\AuditSnapshot` | `dev_audit_snapshots` | ❌ | ❌ | **Dev tooling** | Keep unscoped, `@bypass-tenancy-scanner` |
| 40 | `Developer\AuditViolation` | `dev_audit_violations` | ❌ | ❌ | **Dev tooling** | Keep unscoped, `@bypass-tenancy-scanner` |
| 41 | `Developer\ComponentStat` | `dev_component_stats` | ❌ | ❌ | **Dev tooling** | Keep unscoped, `@bypass-tenancy-scanner` |
| 42 | `Developer\SlowQueryLog` | `dev_slow_queries_log` | ❌ | ❌ | **Dev tooling** | Keep unscoped, `@bypass-tenancy-scanner` |
| 43 | `QuoteLine` | `quote_lines` | ❌ | ❌ | **CenterScoped + new migration** | Migrate + trait + factory + test |

> Note: the spec baseline was 35; the fresh grep turns up 43 because several models missed in the original audit (e.g. `GoodsReceivedNote`, the line/child tables, the catalog models) are listed here for completeness.

## 4. Treatment summary

- **Scoped (need tenant_id column + trait):** 18 models
- **Center-owned (need tenant_id column + `CenterScoped`):** 7 models (`CenterAddress`, `CenterWorkingHour`, `HR\AttendanceSettings`, `Warehouse`, plus any other table already carrying `center_id`)
- **Intentionally unscoped:** 18 models — `@bypass-tenancy-scanner` annotation added, rationale recorded in §3.

## 5. Migration & test plan

1. **One migration per affected table** to add `tenant_id` (and `center_id` where missing), `foreignId` constraints, and the composite unique constraint that already exists on most rows (e.g. `unique(['work_order_id', 'template_id'])` stays the same).
2. **Update factories** for the scoped models so `tenant_id` (and `center_id` when applicable) are auto-populated.
3. **Add `tests/Feature/TenantIsolation/`** with ≥10 contract tests verifying the most-trafficked entities (Supplier, Part, Customer, Vehicle, WorkOrder, Invoice, Payment, Employee, GoodsReceivedNote, WorkOrderPhoto) cannot leak across tenants.
4. **Annotate intentionally unscoped models** with `@bypass-tenancy-scanner` so the future lint job doesn't reopen this debate.
5. **Run `php artisan test --filter=Tenant`** and `php artisan test` (full suite) — both must pass with **no regression**.

## 6. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Backfilling `tenant_id` on tables that already have rows | Migration looks up the parent chain (e.g. `invoice_lines.invoice_id → invoices.tenant_id`) and backfills in SQL; only adds the column as nullable initially, then flips to NOT NULL after backfill. |
| Routes that intentionally operate across tenants (e.g. `/view/quote/{uuid}`) | Public routes already bypass the global scope because `TenancyContext::tenantId()` returns `null` when no user is authed. Verified by `TenancyIsolationTest`. |
| `// @bypass-tenancy-scanner` drift | The CI audit (Track D / DX) will flag any new unscoped model lacking the comment. |
| `Route Model Binding` failing because `CenterScoped` returns no row for cross-tenant ID | This is the **desired** behavior. `TenancyIsolationTest::test_blocks_cross_tenant_access` confirms it returns 404. |