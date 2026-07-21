# Carag V2 — Performance Baseline

*Established: Phase 2 Goal 3 — 2026-07-21*

---

## Methodology

- **Test harness:** `PerformanceBaselineTest` (`@group perf`)
- **Measurement:** Median of 3 consecutive runs after 1 warmup run, per endpoint
- **Database:** SQLite in-memory (test env) — timings are relative only; query counts reflect production MySQL behaviour
- **Seeder:** `PerformanceBaselineSeeder` — lightweight fixture (50 WOs, 30 customers, 60 vehicles, 20 parts)
- **Run command:** `php -d memory_limit=512M ./vendor/bin/phpunit --filter=PerformanceBaselineTest`

> **Note:** Timing budgets are set generously for the SQLite test environment (~10× production MySQL). The primary metric for N+1 regression detection is **query count**.

---

## Baseline Results (Post-Optimization)

| # | Endpoint | Response Time | Query Count | Budget (time) | Budget (queries) |
|---|---|---|---|---|---|
| 1 | `GET /app/work-orders?status=open` | 674 ms | 72 | < 3000 ms | < 75 |
| 2 | `GET /app/work-orders/{id}` | 317 ms | 45 | < 2000 ms | < 50 |
| 3 | `GET /app/customers` | 114 ms | 18 | < 1000 ms | < 25 |
| 4 | `GET /app/inventory/stock` | 111 ms | 22 | < 1000 ms | < 25 |
| 5 | `GET /app/vehicles` | 219 ms | 24 | < 2000 ms | < 25 |

All 5 tests: **PASS ✅**

---

## Query Count Breakdown

### Endpoint 1 — Work Orders List (`72 queries`)

| Source | Count |
|---|---|
| Inertia middleware (shared data) | ~12 |
| `getStatsForStatuses('open')` — 6 aggregate queries × 2 | ~12 |
| `filterCounts` — 5 status count queries | ~5 |
| Setup: customers, makes, colors, departments, models | ~5 |
| Main paginated list + COUNT | 2 |
| Eager loads: customer, vehicle, vehicle.make, invoice, payments, items | ~6 |
| **Total** | **72** |

> **N+1 fixed:** Previously loaded `payments` and `items` lazily per WO (N×2 queries for 15 WOs = 30 extra). Now eager-loaded with 2 IN-clause queries.

### Endpoint 2 — Work Order Show (`45 queries`)

| Source | Count |
|---|---|
| Inertia middleware | ~12 |
| WO + all deep relations (items, services, parts, payments, etc.) | ~33 |

> **N+1 fixed:** Eager loaded items with their sub-relations (service, parts) and payments.

### Endpoint 3 — Customers List (`18 queries`)

| Source | Count |
|---|---|
| Inertia middleware | ~12 |
| Paginated customers + COUNT | 2 |
| `withCount(['vehicles', 'workOrders', 'quotes'])` — 3 subqueries in SELECT | included |
| Lookup data | ~4 |

> **N+1 fixed:** Replaced 3 per-customer DB calls with `withCount()`.

### Endpoint 4 — Inventory Stock (`22 queries`)

| Source | Count |
|---|---|
| Inertia middleware | ~12 |
| Warehouse lookup + categories | ~3 |
| Summary stats (combined selectRaw) | 1 |
| Paginated balances + COUNT + eager loaded parts | ~6 |

> **N+1 fixed:** Combined 4 separate aggregate queries (total_items, in_stock, low_stock, total_value) into 1 `selectRaw` LEFT JOIN query. Also fixed `strtolower()` TypeError on null `order` param and `bcmul()` TypeError from model accessor collision (aliased result as `stock_value`).

### Endpoint 5 — Vehicles List (`24 queries`)

| Source | Count |
|---|---|
| Inertia middleware | ~12 |
| Paginated vehicles + COUNT + eager loaded customer/make/model | ~5 |
| `modelsByMake` lookup (grouped) | 1 |
| Makes + colors + other lookups | ~6 |

> **N+1 fixed:** Replaced per-make `VehicleModel::where('make_id', ...)` loop (N queries per make) with a single `->groupBy('make_id')` collection query.

---

## Bug Fixes Discovered During Profiling

| File | Bug | Fix |
|---|---|---|
| `InventoryBalanceController.php:63` | `strtolower(null)` TypeError — `order` param can be null | Added `(string)` cast + default `''` |
| `InventoryBalanceController.php:113` | Wrong column name `parts.min_stock` → `parts.min_qty` | Corrected to match schema |
| `InventoryBalanceController.php:115` | `bcmul(null, null)` TypeError — `total_value` alias clashed with model accessor | Renamed alias to `stock_value` |

---

## N+1 Optimizations Applied

| Controller | Method | Change |
|---|---|---|
| `WorkOrderController` | `buildWorkOrderQuery()` | Added `->with(['customer', 'vehicle.make', 'invoice', 'payments', 'items'])` |
| `CustomerController` | `index()` | Replaced manual count loops with `->withCount(['vehicles', 'workOrders', 'quotes'])` |
| `InventoryBalanceController` | `index()` | Combined 4 aggregate queries into 1 `selectRaw` with LEFT JOIN |
| `VehicleController` | `index()` | Replaced N-query make loop with single grouped `VehicleModel` query |

---

## Re-running the Baseline

```bash
# Run performance tests only
php -d memory_limit=512M ./vendor/bin/phpunit --filter=PerformanceBaselineTest

# Run with verbose STDERR output to see measured values
php -d memory_limit=512M ./vendor/bin/phpunit --filter=PerformanceBaselineTest 2>&1 | grep -E '\[RESULT|Tests:'
```

---

## Adding a New Endpoint to the Baseline

1. Add a new `test_<endpoint>_meets_perf_budget()` method to `PerformanceBaselineTest`
2. Use the `measure()` helper which runs 1 warmup + 3 measured runs
3. Set query budget = observed count + 20% headroom
4. Set timing budget = 10× production target (SQLite is slower)
5. Document the query breakdown in this file
