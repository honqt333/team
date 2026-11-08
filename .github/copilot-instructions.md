# GitHub Copilot Instructions — Carag V2 / Khidmah Pro
# Unified Master Constitution

This repository is governed by the **Unified AI Agent Master Constitution** located in `AGENTS.md`.

## Core Guidelines for Copilot:
1. **Source of Truth:** Refer to `AGENTS.md` before making any suggestion or generating code.
2. **Multi-Tenancy:** Always use `TenantScoped` and `CenterScoped` models. Never hardcode tenant or center IDs.
3. **Authorization:** Always ensure operations have server-side Policy checks (`$this->authorize(...)`).
4. **No Re-inventing the Wheel:**
   - Financial operations: use `InvoiceService`, `PaymentManager`, `TaxCalculator`.
   - Work order labor: use `WorkOrderLaborService`, `WorkOrderLaborEntry`.
   - Inventory: use `InventoryService`, `InventoryMove`.
   - Printing: use `usePrint` composable and `PrintEngine.vue`.
   - Standard components:
     - Always use `SearchableSelect.vue` (never raw HTML `<select>`).
     - Always use `AppPrintButton.vue`.
     - Always use `AppViewModeToggle.vue`.
     - Always use `AppTabs.vue`.
     - Always use `SaudiPlateDisplay.vue` and `SaudiPlateInput.vue`.
     - Always use `SignaturePad.vue` and `CustomDatePicker.vue`.
5. **Typography:** The only allowed font family is **`IBM Plex Sans Arabic`**.
6. **Header Layout (RTL):** `[Title/Badge]` (Right) ... `[Toolbar Glass Container]` then `[Primary CTA Button]` (Far Left).
7. **i18n:** Zero hardcoded strings. Use `t('...')` with `ar.json` / `en.json` and `__('...')` with `lang/ar/` / `lang/en/`.
8. **Verification:** All changes must pass `php artisan test`, `npm run build`, and `php artisan optimize:clear`.
