# Migrations

> 209 files. Yes, really. Read this before adding a new one.

## Layout

```
database/
├── migrations/                  # default — schema-only, fast on deploy
│   ├── 0001_01_01_*             # Laravel base (users, cache, jobs)
│   ├── 2025_12_20_*             # initial schema
│   ├── 2025_*                   # phase 0 (multi-tenant, 2FA, ZATCA)
│   ├── 2026_01_*                # phase 1 (work orders, invoicing)
│   ├── 2026_*                   # phase 2+ (incremental)
│   └── backups/                 # OLD migrations superseded by
│                                # rewrites. Kept because some prod
│                                # databases ran them. NEVER modify
│                                # a file in here — the migration
│                                # hash is recorded in `migrations` table.
├── seeders/                     # idempotent data seeds (roles,
│                                # permissions, tax rates, etc)
└── factories/                   # model factories for tests
```

## How to add a new migration

```bash
php artisan make:migration add_loyalty_points_to_customers_table
# edit the file, then commit
```

Rules:

1. **One change per migration.** Don't bundle "add column X and
   drop table Y" in the same file. Splitting makes partial rollbacks
   possible and review easier.
2. **Schema-only in `up()`.** No raw `DB::table('users')->update(...)`
   for data backfills. Use a separate data migration — see below.
3. **Always provide `down()`.** A `down()` that throws
   `LogicException('irreversible')` is acceptable, but most should
   be reversible.
4. **Never edit a committed migration.** If you need to fix a bug,
   add a follow-up migration. The `migrations` table records the
   hash of the SQL that ran on prod, so editing a committed file
   silently breaks the next deploy.

## Data migrations (Laravel 11+)

For anything that touches existing rows, create a separate
migration that depends on the schema migration:

```php
// 2026_07_21_120000_backfill_loyalty_points.php
public function up(): void
{
    DB::table('customers')
        ->whereNull('loyalty_points')
        ->update(['loyalty_points' => 0]);
}
```

This pattern is already used elsewhere in this project (search for
`BackfillTenantDefaults` and `FixInvoiceAddressSnapshots`).

## The `backups/` subfolder

Migrations that have been superseded — e.g. a 2025 migration that
renamed a column, and a 2026 migration that did it differently. We
keep the old file because some prod databases ran it during their
initial setup. The new migration checks `Schema::hasColumn` before
applying the rename, so running both is safe.

If you need to roll back past a `backups/` migration, run the new
schema migration's `down()` first to undo its forward changes,
then the old `backups/` migration's `up()` to re-apply.

## When the count becomes a problem

We're at 209. Laravel doesn't care; the migration runner just
sorts by filename and applies them in order. The cost is human:
new devs have to scroll past 200 files to find what changed. The
"split into schema + data" rule (above) keeps the next 50 from
making this worse.

If we cross ~400, the right move is a "compressed history"
migration: a single new file that does
`Schema::create('...', function (Blueprint $table) { ... })`
for every table, marked as a no-op when the table already exists.
This is a one-time ops move, not a regular pattern.
