# CLAUDE.md
# Carag V2 / Khidmah Pro — Claude Code Operational Context & Protocol

> **MANDATORY SUPREME RULE:**  
> This workspace is strictly governed by the **Unified AI Agent Master Constitution** in `AGENTS.md`.  
> Every rule in `AGENTS.md` is absolute and non-negotiable. Read `AGENTS.md` before inspecting or altering any code.

---

## 1. Project Context & Nature
- **Stack:** Laravel 12 (PHP 8.2+) · Vue 3 (`<script setup>`) · Inertia.js · Vite · Tailwind CSS · MySQL 8.0+.
- **Domain:** Multi-tenant automotive ERP (Work Orders, Customers, Vehicles, Invoicing/ZATCA, Purchasing, Inventory, HR).
- **Environment:** Production-grade system. No MVP shortcuts. Maintain backward compatibility.

---

## 2. Mandatory 5-Step Workflow
1. **Discovery First:** Inspect existing models, migrations, policies, services, components, and translations BEFORE writing any code. Never assume column names or create duplicate classes.
2. **Strict Multi-Tenancy & Security:**
   - Always adhere to `TenantScoped` and `CenterScoped`. Never bypass tenant/center isolation or hardcode IDs.
   - Every mutation must have a server-side Policy/Gate check (`$this->authorize(...)`). Hiding UI elements is NOT authorization.
3. **No Reinventing the Wheel & Approved Components:**
   - **Invoicing & Payments:** `InvoiceService`, `PaymentManager`, `TaxCalculator`.
   - **Labor & Timers:** `WorkOrderLaborService`, `WorkOrderLaborEntry`.
   - **Inventory:** `InventoryService`, `InventoryMove`.
   - **Printing:** Unified print engine (`usePrint`, `PrintEngine.vue`).
   - **Standard UI Components:**
     - Searchable Dropdown: ALWAYS use `SearchableSelect.vue` (never raw `<select>` for dynamic data).
     - Print Button: ALWAYS use `AppPrintButton.vue`.
     - View Mode (Grid/List): ALWAYS use `AppViewModeToggle.vue`.
     - Tabs: ALWAYS use `AppTabs.vue`.
     - Vehicle Plates: `SaudiPlateDisplay.vue` and `SaudiPlateInput.vue`.
     - Signatures: `SignaturePad.vue`.
     - Dates: `CustomDatePicker.vue`.
     - Destructive Confirmations: `ConfirmModal.vue`.
4. **Typography & UI Standards:**
   - Unified Font: **`IBM Plex Sans Arabic`** across all views and print layouts.
   - Header Order in RTL: `[Title/Badge]` (Right) ... `[Toolbar Glass Container]` then `[Primary CTA Button]` (Far Left).
   - Zero Hardcoded Strings: All user-facing text MUST go through `resources/js/i18n/lang/ar.json` & `en.json` (front-end) and `lang/ar/` & `lang/en/` (back-end).
   - Activity Logging: Record events via `logActivity()`.
5. **Mandatory Verification Before Complete:**
   - Run affected tests: `php artisan test tests/Feature/YourTest.php`
   - Build front-end: `npm run build`
   - Clear cache: `php artisan optimize:clear`

---

## 3. Strict Prohibitions
1. NEVER edit previously executed migration files; always create new migrations with a rollback (`down()`).
2. NEVER create duplicate print templates or bypass the unified print engine.
3. NEVER invent new schema fields or bypass database type constraints (e.g. `unsignedInteger` cannot be negative).
4. NEVER use raw HTML `<select>` elements for dynamic selections.
5. NEVER declare a task done without verifying tests and running `npm run build`.

---

## 4. Key Commands
```bash
composer run dev
php artisan test
npm run build
php artisan optimize:clear
```
