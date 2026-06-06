# Skill: PHPUnit Testing - Carag V2

دليل سريع لقواعد الاختبار في Carag V2. كل feature جديد **لازم** يجي مع tests.

## Test Stack

- **PHPUnit** (Laravel default) — primary
- **Pest** — optional, supported
- **RefreshDatabase** — for tests that need clean DB
- **DatabaseTransactions** — faster, but no schema changes mid-test
- **Test DB:** SQLite in-memory (افتراضي من `phpunit.xml`)

## القاعدة رقم 1: كل Feature يجي مع 3 tests على الأقل

```php
// 1. Happy path — admin can do it
public function test_admin_can_create_work_order(): void

// 2. Auth — non-admin cannot
public function test_employee_cannot_create_work_order(): void

// 3. Tenant isolation — data from another tenant is hidden
public function test_work_order_isolated_to_tenant(): void
```

## القاعدة رقم 2: استخدم Factories، لا Hardcoded IDs

```php
// ❌ Hardcoded
$customer = Customer::find(1);
$response = $this->post('/work-orders', ['customer_id' => 1]);

// ✅ Factory
$customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
$response = $this->post(route('work-orders.store'), [
    'customer_id' => $customer->id,
]);
```

## القاعدة رقم 3: Test Isolation

```php
namespace Tests\Feature\WorkOrder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateWorkOrderTest extends TestCase
{
    use RefreshDatabase;  // ← مهم: كل test يبدأ بـ DB نظيفة

    // ...
}
```

## القاعدة رقم 4: Tenant Helpers (أنشئها لو ما موجودة)

```php
// tests/Traits/MultiTenantTestHelpers.php
namespace Tests\Traits;

use App\Models\Tenant;
use App\Models\Center;
use App\Models\User;

trait MultiTenantTestHelpers
{
    protected function setupTenant(array $attrs = []): array
    {
        $tenant = Tenant::factory()->create($attrs);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        return [$tenant, $center];
    }

    protected function actingAsAdmin($tenant, $center, array $attrs = []): User
    {
        $user = User::factory()->admin()->create(array_merge([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ], $attrs));
        $this->actingAs($user);
        return $user;
    }

    protected function actingAsEmployee($tenant, $center, array $attrs = []): User
    {
        $user = User::factory()->employee()->create(array_merge([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ], $attrs));
        $this->actingAs($user);
        return $user;
    }
}
```

## القاعدة رقم 5: Auth Tests إلزامية

```php
public function test_guest_cannot_access_endpoint(): void
{
    $response = $this->post(route('work-orders.store'), []);
    $response->assertRedirect(route('login'));
}

public function test_employee_cannot_create_work_order(): void
{
    [$tenant, $center] = $this->setupTenant();
    $this->actingAsEmployee($tenant, $center);

    $response = $this->post(route('work-orders.store'), [
        'customer_id' => Customer::factory()->create(['tenant_id' => $tenant->id])->id,
    ]);
    $response->assertForbidden(); // 403
}
```

## القاعدة رقم 6: Tenant Isolation Tests إلزامية

```php
public function test_work_order_from_another_tenant_is_invisible(): void
{
    // Setup tenant A + work order
    [$tenantA, $centerA] = $this->setupTenant();
    $woA = WorkOrder::factory()->create(['tenant_id' => $tenantA->id]);

    // Setup tenant B user
    [$tenantB, $centerB] = $this->setupTenant();
    $this->actingAsAdmin($tenantB, $centerB);

    // Try to access
    $response = $this->get(route('work-orders.show', $woA->id));
    $response->assertNotFound(); // 404 — لا 403، حتى لا نكشف وجود الـ ID
}
```

## القاعدة رقم 7: Validation Tests

```php
public function test_missing_customer_returns_validation_error(): void
{
    [$tenant, $center] = $this->setupTenant();
    $this->actingAsAdmin($tenant, $center);

    $response = $this->post(route('work-orders.store'), [
        // customer_id مفقود
        'items' => [],
    ]);
    $response->assertSessionHasErrors(['customer_id', 'items']);
}
```

## القاعدة رقم 8: Inertia Assertions (للـ frontend pages)

```php
public function test_index_page_renders_with_props(): void
{
    [$tenant, $center] = $this->setupTenant();
    $workOrder = WorkOrder::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAsAdmin($tenant, $center);

    $response = $this->get(route('work-orders.index'));
    $response->assertInertia(fn (Assert $page) => $page
        ->component('WorkOrders/Index')
        ->has('workOrders', 1)
        ->where('workOrders.0.id', $workOrder->id)
    );
}
```

## القاعدة رقم 9: Database Assertions

```php
// Created
$this->assertDatabaseHas('work_orders', [
    'tenant_id' => $tenant->id,
    'customer_id' => $customer->id,
]);

// Deleted
$this->assertDatabaseMissing('work_orders', ['id' => $workOrder->id]);

// Count
$this->assertDatabaseCount('work_orders', 1);
```

## القاعدة رقم 10: Factories قوية

```php
// database/factories/WorkOrderFactory.php
class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        $tenant = Tenant::factory();
        return [
            'tenant_id' => $tenant,
            'center_id' => Center::factory()->for($tenant, 'tenant'),
            'customer_id' => Customer::factory()->for($tenant, 'tenant'),
            'status' => 'open',
            'created_by' => User::factory(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => 'closed', 'closed_at' => now()]);
    }
}
```

## الأوامر الأساسية

```bash
# كل الاختبارات
php artisan test

# class معيّن
php artisan test --filter=CreateWorkOrderTest

# test واحد بالاسم
php artisan test --filter=test_admin_can_create_work_order

# مع coverage
php artisan test --coverage

# أسرع — parallel
php artisan test --parallel

# stop on first failure
php artisan test --stop-on-failure
```

## Test Coverage Targets

| Module | Target | Current (حسب AGENTS.md) |
|---|---|---|
| HR | 70% | 5% |
| Invoices / Payments | 80% (ZATCA حساس) | ? |
| Work Orders | 60% | ? |
| Inventory | 60% | ? |
| Customers / Vehicles | 50% | ? |
| **Total** | **60%** | **~15%** |

## Handoff

بعد ما تخلص، اكتب في `deliverable.md`:

```markdown
## Test Report
- Tests added: 12 (4 auth, 4 tenant, 4 validation)
- All passing: ✓
- Coverage delta: 5% → 12% on WorkOrder module
- Edge cases covered: missing data, max items, invalid status transitions

## Handoff to code-reviewer
- Files: tests/Feature/WorkOrder/CreateWorkOrderTest.php
- Verdict: tests are comprehensive
- Concerns: (لو في) test environment issue
```

---

*Built for the full-stack team rollout — 2026-06-06*
