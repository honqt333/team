# Skill: General Assistant - Carag V2 Project Context

## Overview

This skill provides the general agent with context about the Carag V2 project for research, analysis, and support tasks.

## Project Summary

**Carag V2** is a Laravel ERP system for managing auto repair shops in Saudi Arabia.

### Technology Stack
- **Backend:** Laravel 12 + PHP 8.2+
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS
- **Database:** MySQL 8.0+
- **Auth:** Laravel Sanctum + Google 2FA
- **Language:** Arabic (RTL) + English

### Key Modules

1. **Customer Management** - Customers, vehicles, import/export
2. **Work Orders** - Job orders with services, parts, inspections, signatures
3. **Quotes & Approvals** - Price quotes with public approval links
4. **Invoicing** - Sales/purchase invoices, payments, ZATCA integration
5. **Purchasing** - Purchase orders, GRN, suppliers
6. **Inventory** - Parts, stock, transfers, adjustments
7. **HR** - Employees, payroll, attendance, leaves
8. **Employee Portal** - Self-service for employees

## Common Tasks

### Research Tasks

```bash
# List all controllers
ls app/Http/Controllers/App/

# List all models
ls app/Models/

# Find relevant files
grep -r "WorkOrder" app/Models/ --include="*.php"

# Check recent changes
git log --oneline -20

# Check test coverage
php artisan test --coverage
```

### Analysis Tasks

```bash
# Analyze code complexity
phploc app/Http/Controllers/App/

# Check coding standards
./vendor/bin/pint --test app/

# Run static analysis
./vendor/bin/phpstan analyse app/
```

### Support Tasks

- Finding relevant files for a feature
- Understanding business logic
- Explaining code patterns
- Generating documentation
- Analyzing test coverage

## Code Patterns Reference

### Eloquent Relationships
```php
// In models
public function customer(): BelongsTo
public function items(): HasMany
public function roles(): BelongsToMany
public function department(): BelongsTo
```

### Middleware Stack
```php
// routes/web.php
Route::middleware(['auth', 'verified', 'tenant.active', 'center.context'])->group(function () {
    // Routes requiring 2FA
    Route::middleware([EnsureTwoFactorEnabled::class])->group(function () {
        // App routes
    });
});
```

### Activity Logging
```php
$workOrder->logActivity('item_added', 'تم إضافة خدمة جديدة');
```

### Notification
```php
NotificationService::notifyOwner(
    tenantId: $request->user()->tenant_id,
    type: 'work_order.created',
    title: 'أمر عمل جديد #' . $workOrder->id,
    actionUrl: '/app/work-orders/' . $workOrder->id,
);
```

## File Structure

```
carag-v2/
├── app/
│   ├── Actions/                          # Action classes
│   │   └── Customer/MergeCustomerAction.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── App/                      # App controllers
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── WorkOrderController.php
│   │   │   │   └── InvoicesController.php
│   │   │   ├── System/                   # System controllers
│   │   │   └── Api/                      # API controllers
│   │   ├── Middleware/
│   │   │   ├── EnsureTenantActive.php
│   │   │   ├── EnsureCenterContext.php
│   │   │   └── EnsureTwoFactorEnabled.php
│   │   └── Requests/                     # Form requests
│   ├── Models/
│   │   ├── Customer.php
│   │   ├── WorkOrder.php
│   │   └── HR/Employee.php
│   └── Services/
│       ├── NotificationService.php
│       ├── InvoiceService.php
│       └── Inventory/WorkOrderPartsService.php
├── resources/js/Pages/
│   ├── Customers/
│   ├── WorkOrders/
│   ├── Invoices/
│   └── HR/
├── routes/web.php
├── tests/Feature/
└── database/migrations/
```

## Key Commands

```bash
# Development
composer dev                    # Start all services
npm run dev                     # Vite dev server

# Testing
php artisan test                # Run tests
php artisan test --filter=Test # Run specific test

# Code Quality
./vendor/bin/pint               # Format code
./vendor/bin/phpstan analyse   # Static analysis

# Database
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed
```

## Important Constants

```php
// WorkOrder statuses
WorkOrder::STATUS_DRAFT = 'draft'
WorkOrder::STATUS_OPEN = 'open'
WorkOrder::STATUS_IN_PROGRESS = 'in_progress'
WorkOrder::STATUS_ON_HOLD = 'on_hold'
WorkOrder::STATUS_READY_FOR_QC = 'ready_for_qc'
WorkOrder::STATUS_DONE = 'done'
WorkOrder::STATUS_CANCELLED = 'cancelled'

// Open statuses
WorkOrder::OPEN_STATUSES = ['open', 'in_progress', 'draft', 'on_hold', 'ready_for_qc']
WorkOrder::CLOSED_STATUSES = ['done', 'cancelled']
```

## Multi-Tenancy

All models use the `BelongsToTenant` trait:

```php
// Always include tenant_id
$workOrders = WorkOrder::where('tenant_id', auth()->user()->tenant_id)->get();

// Always include center_id
$workOrders = WorkOrder::where('center_id', auth()->user()->center_id)->get();
```

## Common Issues

### N+1 Queries
```php
// Problem
$workOrders->each(fn($wo) => $wo->customer->name);

// Solution
WorkOrder::with('customer')->get();
```

### Missing Authorization
```php
// Always check
$this->authorize('view', $workOrder);
```

### Missing Tenant Scope
```php
// Always scope
Customer::where('tenant_id', auth()->user()->tenant_id)->get();
```

## Documentation

- `README.md` - General project info
- `DEPLOYMENT.md` - Deployment instructions
- `AGENTS.md` - Agent team documentation
- `CHANGELOG.md` - Version history

---

*Created: 2026-05-31*