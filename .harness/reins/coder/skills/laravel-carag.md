# Skill: Laravel Developer - Carag V2

## Overview

This skill equips the coder agent to work effectively on the Carag V2 project (Laravel ERP for auto repair shops).

## Project Context

- **Framework:** Laravel 12 + PHP 8.2+
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS
- **Database:** MySQL 8.0+ (multi-tenant)
- **Auth:** Laravel Sanctum + Google 2FA
- **Language:** Arabic (RTL) + English

## Core Capabilities

### 1. Understanding Project Structure

```
carag-v2/
├── app/
│   ├── Http/Controllers/App/    # Application controllers (45+)
│   ├── Http/Controllers/System/ # System controllers
│   ├── Http/Controllers/Api/    # API controllers
│   ├── Models/                  # Eloquent models (65+)
│   ├── Services/                # Business logic services
│   ├── Actions/                 # Action classes (CreateWorkOrderAction, etc.)
│   ├── Http/Middleware/         # Custom middleware
│   └── Http/Requests/           # Form request validation
├── resources/js/Pages/          # Inertia Vue components
├── routes/web.php              # Web routes (~300 routes)
└── tests/Feature/              # Feature tests
```

### 2. Key Modules

| Module | Description | Key Models |
|--------|-------------|------------|
| **Customers** | Customer & vehicle management | Customer, Vehicle |
| **WorkOrders** | Job orders management | WorkOrder, WorkOrderItem, WorkOrderPhoto |
| **Quotes** | Price quotes with approval | Quote, QuoteLine |
| **Invoices** | Billing & payments | Invoice, Payment |
| **Purchasing** | Suppliers & purchase orders | PurchaseOrder, Supplier |
| **Inventory** | Parts & stock management | Part, Warehouse, InventoryBalance |
| **HR** | Employee management | HR/Employee, Attendance, Leave |

### 3. Coding Patterns

#### Controller Pattern
```php
class ExampleController
{
    use AuthorizesRequests;

    public function index(): Response { /* list view */ }
    public function store(Request $request): RedirectResponse { /* create */ }
    public function show(Model $model): Response { /* detail view */ }
    public function update(Request $request, Model $model): RedirectResponse { /* update */ }
    public function destroy(Model $model): RedirectResponse { /* delete */ }
}
```

#### Service Pattern
```php
// app/Services/ExampleService.php
class ExampleService
{
    public function doSomething(array $data): Model
    {
        // Business logic
    }
}
```

#### Action Pattern
```php
// app/Actions/Module/ActionName.php
class CreateAction
{
    public function execute(User $user, array $data): Model
    {
        // Implementation
    }
}
```

#### Model Pattern
```php
class WorkOrder extends Model
{
    use BelongsToTenant;

    // Constants
    const STATUS_OPEN = 'open';
    const STATUS_DONE = 'done';

    // Relationships
    public function customer(): BelongsTo { /* ... */ }
    public function items(): HasMany { /* ... */ }

    // Scopes
    public function scopeActive($query) { /* ... */ }
    public function scopeByStatus($query, $status) { /* ... */ }

    // Methods
    public function calculateTotal(): float { /* ... */ }
    public function logActivity(string $type, string $description): void { /* ... */ }
}
```

### 4. Multi-Tenancy Rules

**CRITICAL:** Always include tenant_id and center_id:

```php
// ✅ Correct
WorkOrder::where('tenant_id', $request->user()->tenant_id)
    ->where('center_id', $request->user()->center_id)

// ❌ Wrong
WorkOrder::all()
```

### 5. Authorization Pattern

```php
// Always use policies
$this->authorize('view', $workOrder);
$this->authorize('create', WorkOrder::class);
$this->authorize('update', $workOrder);

// Check permissions
auth()->user()->can('inventory.override_negative_stock');
```

### 6. Activity Logging

```php
// Log important actions
$workOrder->logActivity('status_changed', 'تم تغيير الحالة إلى ' . $newStatus);
```

### 7. Print Views

Use PrintEngine for all print templates:

```php
return Inertia::render('WorkOrders/Print/TemplateName', [
    'workOrder' => $workOrder,
]);
```

### 8. Form Request Validation

```php
// app/Http/Requests/ExampleRequest.php
class ExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('create', Example::class);
    }

    public function rules(): array
    {
        return [
            'field' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ];
    }
}
```

## Best Practices

### DO ✅

1. Use Eager Loading (`with()`) to prevent N+1 queries
2. Use Scopes for common query patterns
3. Use Form Requests for validation
4. Use Actions for complex business logic
5. Log important activities
6. Use constants instead of magic strings
7. Add PHPDoc comments for complex methods
8. Write tests for new features

### DON'T ❌

1. Don't use raw SQL without justification
2. Don't hardcode tenant_id (use auth context)
3. Don't create fat controllers (>300 lines)
4. Don't duplicate query logic (use scopes)
5. Don't skip authorization checks
6. Don't leave magic strings/numbers

## Common Operations

### Creating a Work Order
```php
$workOrder = $createWorkOrderAction->execute($user, $data);
NotificationService::notifyOwner(/* ... */);
$workOrder->logActivity('created', 'تم إنشاء أمر عمل جديد');
```

### Adding Item to Work Order
```php
$line = $workOrder->items()->create([
    'tenant_id' => $workOrder->tenant_id,
    'title' => $data['title'],
    'qty' => $data['qty'],
    'unit_price' => $data['unit_price'],
]);

// Auto-update expected end date
if ($line->due_date > $workOrder->expected_end_date) {
    $workOrder->update(['expected_end_date' => $line->due_date]);
}
```

### Inventory Stock Check
```php
$allowNegative = auth()->user()->can('inventory.override_negative_stock');
$partsService = app(WorkOrderPartsService::class);
$part = $partsService->addPart($data, $allowNegative);
```

### Status Change Rules
```php
// R7: Cannot put on hold if has active technicians
if (!$workOrder->canBeOnHold()) {
    return back()->withErrors(['error' => __('messages.cannot_put_on_hold')]);
}

// R8: Cannot complete until all items done
if (!$workOrder->markAsCompleted()) {
    return back()->withErrors(['error' => __('messages.cannot_complete_items_pending')]);
}
```

## Testing Patterns

```php
// tests/Feature/ExampleTest.php
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_example(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/app/examples', [
            'title' => 'Test',
            'amount' => 100,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('examples', ['title' => 'Test']);
    }
}
```

## File Paths Reference

| Type | Path |
|------|------|
| Controllers (App) | `app/Http/Controllers/App/` |
| Controllers (System) | `app/Http/Controllers/System/` |
| Models | `app/Models/` |
| Services | `app/Services/` |
| Actions | `app/Actions/` |
| Middleware | `app/Http/Middleware/` |
| Requests | `app/Http/Requests/` |
| Views | `resources/js/Pages/` |
| Routes | `routes/web.php` |

## Key Files

- `app/Models/WorkOrder.php` - Work order model with status logic
- `app/Models/Customer.php` - Customer with vehicle relation
- `app/Services/NotificationService.php` - Notification handling
- `app/Services/Inventory/WorkOrderPartsService.php` - Inventory integration
- `app/Http/Middleware/EnsureTwoFactorEnabled.php` - 2FA enforcement

---

*Created: 2026-05-31*