# 🎯 Phase 1 — Carag V2 Master Recovery Plan (Part 1)
## Foundation Reinforcement + Critical Fixes | شهر 1 | النتيجة: 68% → 75%

## 📋 السياق

أنت تعمل على **Carag V2** (Khidmh Pro) — نظام إدارة ورش سيارات SaaS مبني على Laravel 12 + Vue 3 + Inertia.js. **Phase 0 مكتمل ومُراجَع** (Score: 68/100).

**الـ Stack:**
- Laravel 12 + PHP 8.3
- Vue 3 + Composition API
- Inertia.js 2 + Vite
- MySQL 8.0 + SQLite (CI) + Redis 7
- Sanctum + Spatie Permission
- 110 Models, 121 Controllers, 293 Vue files, 462 routes

**الـ Reference:**
- 📄 `docs/audit/Carag-V2-Master-Recovery-Plan.md` (المرحلة 1)
- 📄 `docs/audit/Carag-V2-Phase-0-Review.md` (مراجعة Phase 0)
- 📄 `docs/audit/Carag-V2-Deep-Audit-Report.md` (التفاصيل الكاملة)
- 📄 `docs/audit/Carag-V2-Audit-Registry.md` (Registry)

**الـ Branch الحالي:** `feature/audit-master-plan-zxz80` (انتقل إليه أولاً)

**Target:** من **68% → 75%** (شهر واحد)
- 🔴 3 إصلاحات حرجة (من Phase 0 Review)
- 🟠 80+ FormRequests
- 🟠 200+ tests
- 🟠 Jobs + Events + Listeners foundation

---

## 🚨 CRITICAL FIXES (من Phase 0 Review)

**يجب إصلاحها قبل أي شي آخر — Issue #1 يمنع CI من تمرير migration!**

### Fix #1: SQLite Migration Compatibility (30 دقيقة)

**الملف:** `database/migrations/2026_07_25_120000_normalize_payment_types.php`

**المشكلة:** الـ migration فيه `ALTER TABLE ... ENUM` (MySQL only). الـ CI يستخدم SQLite → سيفشل.

**الحل:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // 1. Update data (both drivers)
        DB::statement("UPDATE payments SET type = LOWER(type) WHERE type IN ('Payment', 'Refund', 'Bad_debt', 'BAD_DEBT')");
        DB::statement("UPDATE payments SET type = 'payment' WHERE type = 'pay'");
        DB::statement("UPDATE payments SET type = 'refund' WHERE type = 'ref'");
        DB::statement("UPDATE payments SET type = 'bad_debt' WHERE type IN ('bad', 'debt', 'write_off', 'writeoff')");

        DB::statement("UPDATE invoices SET type = LOWER(type) WHERE type IN ('Invoice', 'Credit', 'Debit')");
        DB::statement("UPDATE invoices SET type = 'invoice' WHERE type = 'inv'");
        DB::statement("UPDATE invoices SET type = 'credit_note' WHERE type IN ('credit', 'cn', 'refund_invoice')");
        DB::statement("UPDATE invoices SET type = 'debit_note' WHERE type IN ('debit', 'dn', 'additional_charge')");

        // 2. MySQL: Add ENUM constraint
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM('payment', 'refund', 'bad_debt') NOT NULL DEFAULT 'payment'");
            DB::statement("ALTER TABLE invoices MODIFY COLUMN type ENUM('invoice', 'credit_note', 'debit_note') NOT NULL DEFAULT 'invoice'");
        }

        // SQLite: no constraint needed (TEXT column accepts any value)
        // PostgreSQL: use CHECK constraint
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_type_check CHECK (type IN ('payment', 'refund', 'bad_debt'))");
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_type_check CHECK (type IN ('invoice', 'credit_note', 'debit_note'))");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN type VARCHAR(50) NOT NULL");
            DB::statement("ALTER TABLE invoices MODIFY COLUMN type VARCHAR(50) NOT NULL");
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT payments_type_check");
            DB::statement("ALTER TABLE invoices DROP CONSTRAINT invoices_type_check");
        }
    }
};
```

**Verification:**
```bash
# 1. Reset DB
rm -f database/database.sqlite
touch database/database.sqlite

# 2. Run migration on SQLite
php artisan migrate

# 3. Verify data
php artisan tinker --execute="
\$p = \App\Models\Payment::factory()->create(['type' => 'Payment']);
echo \$p->type->value;
"

# 4. Run all tests
php artisan test
```

---

### Fix #2: Tenant Scope Script CI Improvement (1 ساعة)

**الملف:** `scripts/check-tenant-scope.sh`

**المشكلة:** `EXEMPTIONS` list فيه 99 من 110 models (90%) — defeats purpose.

**الحل:** استبدل الـ script بالكامل:

```bash
#!/bin/bash
set -e

cd "$(dirname "$0")/.."

# Only exempt true root models (no tenant_id, no center_id, no @bypass)
EXEMPTIONS="User.php|Role.php|Permission.php|Tenant.php|Center.php"

# Models with explicit @bypass-tenancy-scanner annotation are also exempt
# (developer must consciously mark them)

MODELS=$(find app/Models -name "*.php" -not -path "*/Concerns/*")

FAILED=0
TOTAL_CHECKED=0
TOTAL_EXEMPT=0

echo "=== Tenant Scope Audit ==="
echo ""

for f in $MODELS; do
    BASENAME=$(basename "$f")
    TOTAL_CHECKED=$((TOTAL_CHECKED+1))

    # Check if exempted
    if echo "$EXEMPTIONS" | grep -q "^$BASENAME$"; then
        TOTAL_EXEMPT=$((TOTAL_EXEMPT+1))
        echo "  ⊘ $BASENAME (root model - exempt)"
        continue
    fi

    # Check if has @bypass-tenancy-scanner
    if grep -q "@bypass-tenancy-scanner" "$f"; then
        TOTAL_EXEMPT=$((TOTAL_EXEMPT+1))
        echo "  ⊘ $BASENAME (explicit bypass)"
        continue
    fi

    # Check if model has tenant_id or center_id
    HAS_TENANT=$(grep -c "tenant_id" "$f" || true)
    HAS_CENTER=$(grep -c "center_id" "$f" || true)

    if [ "$HAS_TENANT" -gt 0 ] || [ "$HAS_CENTER" -gt 0 ]; then
        # Must use TenantScoped or CenterScoped
        if ! grep -q "use TenantScoped\|use CenterScoped" "$f"; then
            echo "  ❌ $BASENAME (has tenant/center_id but no scope trait)"
            FAILED=$((FAILED+1))
        else
            SCOPES=$(grep -E "use (TenantScoped|CenterScoped)" "$f" | sed 's/.*use //;s/;.*//' | tr '\n' ' ')
            echo "  ✅ $BASENAME ($SCOPES)"
        fi
    else
        echo "  · $BASENAME (no tenant/center - OK)"
    fi
done

echo ""
echo "=== Summary ==="
echo "Total models checked: $TOTAL_CHECKED"
echo "Exempt: $TOTAL_EXEMPT"
echo "Failed: $FAILED"
echo ""

if [ $FAILED -gt 0 ]; then
    echo "🚨 $FAILED models need tenant scope!"
    exit 1
fi

echo "✅ All models with tenant references have proper scope"
```

**Verification:**
```bash
chmod +x scripts/check-tenant-scope.sh
./scripts/check-tenant-scope.sh
# يجب: 0 failed (مع models كثيرة "OK" لأنها بدون tenant_id)
```

**⚠️ ملاحظة:** الـ script الجديد صارم. إذا فيه models متبقية بدون scope، **يجب إصلاحها** (مش فقط إضافتها للـ EXEMPTIONS). الـ Audit السابق أظهر أن 22 model عندهم TenantScoped + 30 CenterScoped = 52 model من 110. الباقي إما root models أو بدون tenant.

---

### Fix #3: Flaky Tests Investigation (2 ساعة، يمكن لاحقاً)

**المشكلة:** 4 tests فشلت في التشغيل الأول، نجحت في الثاني (race condition).

**التحقيق:**
```bash
# 1. شغّل tests 5 مرات
for i in 1 2 3 4 5; do
    echo "=== Run $i ==="
    php artisan test 2>&1 | tail -3
done

# 2. إذا فشلت، حدد الـ failing test
php artisan test 2>&1 | grep -B2 "FAIL\|✗" | head -20
```

**الإصلاح المحتمل:**
- استخدام `RefreshDatabase` لكل test
- استخدام `DatabaseTransactions` للـ tests الخفيفة
- تجنب `sleep()` في tests
- استخدام `Carbon::setTestNow()` بدلاً من `now()`

**⚠️ يمكن تأجيله إلى Phase 1 (أولوية منخفضة).**

---

# 🏗️ WEEK 1-2: Foundation Reinforcement

## المهمة 1: استخراج FormRequests (يوم 1-5)

**الهدف:** 193 inline validation → 80+ FormRequest

### Step 1.1: FormRequest Templates (الأساس)

**`app/Http/Requests/Concerns/TenantAware.php`:**
```php
<?php

namespace App\Http\Requests\Concerns;

use App\Support\TenancyContext;

trait TenantAware
{
    protected function tenantId(): int
    {
        return (int) (auth()->user()?->tenant_id ?? 0);
    }

    protected function centerId(): ?int
    {
        return auth()->user()?->current_center_id;
    }

    /**
     * Rule: Field must reference a row scoped to current tenant.
     */
    protected function tenantExistsRule(string $table, string $column = 'id'): string
    {
        return "exists:{$table},{$column},tenant_id,{$this->tenantId()}";
    }

    /**
     * Rule: Field must reference a row in current tenant + center.
     */
    protected function centerExistsRule(string $table, string $column = 'id'): string
    {
        $centerId = $this->centerId() ?? 0;
        return "exists:{$table},{$column},tenant_id,{$this->tenantId()},center_id,{$centerId}";
    }
}
```

**`app/Http/Requests/Concerns/PaginatesRequest.php`:**
```php
<?php

namespace App\Http\Requests\Concerns;

trait PaginatesRequest
{
    public function perPage(): int
    {
        $perPage = (int) $this->input('per_page', 15);
        return min(max($perPage, 1), 100);
    }

    public function page(): int
    {
        return max((int) $this->input('page', 1), 1);
    }
}
```

**`app/Http/Requests/Concerns/SortableRequest.php`:**
```php
<?php

namespace App\Http\Requests\Concerns;

trait SortableRequest
{
    public function sortBy(): string
    {
        return $this->input('sort_by', 'id');
    }

    public function sortDir(): string
    {
        $dir = strtolower($this->input('sort_dir', 'desc'));
        return in_array($dir, ['asc', 'desc']) ? $dir : 'desc';
    }
}
```

**`app/Http/Requests/Concerns/FilterableRequest.php`:**
```php
<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait FilterableRequest
{
    public function applySearch(Builder $query, array $fields): Builder
    {
        if ($search = $this->input('search')) {
            $query->where(function ($q) use ($search, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
        return $query;
    }

    public function applyDateRange(Builder $query, string $column = 'created_at'): Builder
    {
        if ($from = $this->input('date_from')) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to = $this->input('date_to')) {
            $query->whereDate($column, '<=', $to);
        }
        return $query;
    }
}
```

### Step 1.2: Customer FormRequests

**`app/Http/Requests/Customer/CustomerStoreRequest.php`:**
```php
<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('customers.create');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['individual', 'company', 'government'])],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'phone')
                    ->where('tenant_id', $this->tenantId())
                    ->whereNull('deleted_at'),
            ],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address_line' => ['nullable', 'string', 'max:500'],
            'building_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => __('validation.customer_phone_exists'),
            'type.in' => __('validation.customer_type_invalid'),
        ];
    }
}
```

**`app/Http/Requests/Customer/CustomerUpdateRequest.php`:** (similar but with `Rule::unique` ignore current ID)

```php
<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerUpdateRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('customers.update');
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'type' => ['sometimes', Rule::in(['individual', 'company', 'government'])],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('customers', 'phone')
                    ->where('tenant_id', $this->tenantId())
                    ->whereNull('deleted_at')
                    ->ignore($customerId),
            ],
            // ... other fields
        ];
    }
}
```

**`app/Http/Requests/Customer/CustomerMergeRequest.php`:**
```php
<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class CustomerMergeRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('customers.merge');
    }

    public function rules(): array
    {
        return [
            'source_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('customers'),
            ],
            'target_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('customers'),
                'different:source_id',
            ],
        ];
    }
}
```

### Step 1.3: Work Order FormRequests (الأكبر)

**`app/Http/Requests/WorkOrder/UpdateConditionRequest.php`:**
```php
<?php

namespace App\Http\Requests\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('work_order');
        return $workOrder && $this->user()->can('update', $workOrder);
    }

    public function rules(): array
    {
        return [
            'fuel_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'damage_marks' => ['nullable', 'array'],
            'damage_marks.*.x' => ['required_with:damage_marks', 'numeric', 'between:0,100'],
            'damage_marks.*.y' => ['required_with:damage_marks', 'numeric', 'between:0,100'],
            'damage_marks.*.color' => ['required_with:damage_marks', 'string', 'in:red,blue,gray'],
            'damage_marks.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

**`app/Http/Requests/WorkOrder/ChangeStatusRequest.php`:**
```php
<?php

namespace App\Http\Requests\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('work_order');
        return $workOrder && $this->user()->can('update', $workOrder);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(WorkOrder::STATUSES)],
            'reason' => ['required_if:status,on_hold', 'nullable', 'string', 'max:500'],
        ];
    }
}
```

**`app/Http/Requests/WorkOrder/AddItemRequest.php`:**
```php
<?php

namespace App\Http\Requests\WorkOrder;

use App\Http\Requests\Concerns\TenantAware;
use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;

class AddItemRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        $workOrder = $this->route('work_order');
        return $workOrder
            && $workOrder->canBeEdited()
            && $this->user()->can('update', $workOrder);
    }

    public function rules(): array
    {
        return [
            'service_id' => [
                'nullable',
                'integer',
                $this->centerExistsRule('services'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.01', 'max:999'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:none,percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'department_id' => [
                'nullable',
                'integer',
                $this->centerExistsRule('departments'),
            ],
            'is_warranty' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

**`app/Http/Requests/WorkOrder/CompleteRequest.php`:**
```php
<?php

namespace App\Http\Requests\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Foundation\Http\FormRequest;

class CompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workOrder = $this->route('work_order');
        return $workOrder
            && $workOrder->allItemsCompleted()
            && $this->user()->can('update', $workOrder);
    }

    public function rules(): array
    {
        return [
            'exit_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_deferred' => ['nullable', 'boolean'],
            'due_date' => ['required_if:is_deferred,true', 'nullable', 'date', 'after_or_equal:exit_date'],
        ];
    }
}
```

### Step 1.4: Payment FormRequests (الأكثف استخداماً)

**`app/Http/Requests/Payment/StorePaymentRequest.php`:**
```php
<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\Concerns\TenantAware;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('payments.create');
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(Payment::METHODS)],
            'type' => ['required', Rule::in(array_column(\App\Enums\PaymentType::cases(), 'value'))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'work_order_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('work_orders'),
            ],
            'invoice_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('invoices'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => __('payments.invalid_type'),
            'payment_method.in' => __('payments.invalid_method'),
        ];
    }
}
```

**`app/Http/Requests/Payment/UpdatePaymentRequest.php`:** (similar with `id` ignore)

**`app/Http/Requests/Payment/RefundPaymentRequest.php`:**
```php
<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundPaymentRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('payments.refund');
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

### Step 1.5: Inventory FormRequests

**`app/Http/Requests/Inventory/PartStoreRequest.php`:**
```php
<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('parts.create');
    }

    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parts', 'sku')
                    ->where('tenant_id', $this->tenantId()),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'category_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('inventory_categories'),
            ],
            'unit_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('inventory_units'),
            ],
            'default_sale_price' => ['required', 'numeric', 'min:0'],
            'default_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'barcode' => ['nullable', 'string', 'max:50'],
        ];
    }
}
```

**`app/Http/Requests/Inventory/StockAdjustmentRequest.php`:**
```php
<?php

namespace App\Http\Requests\Inventory;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('inventory.adjust');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => [
                'required',
                'integer',
                $this->centerExistsRule('warehouses'),
            ],
            'part_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('parts'),
            ],
            'qty' => ['required', 'numeric', 'not_in:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
            'allow_negative' => ['nullable', 'boolean'],
        ];
    }
}
```

### Step 1.6: HR FormRequests

**`app/Http/Requests/HR/EmployeeStoreRequest.php`:**
```php
<?php

namespace App\Http\Requests\HR;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('hr.employees.create');
    }

    public function rules(): array
    {
        return [
            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_employees', 'employee_number')
                    ->where('tenant_id', $this->tenantId()),
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'national_id' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('hr_employees', 'national_id')
                    ->where('tenant_id', $this->tenantId()),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'hire_date' => ['required', 'date'],
            'job_title_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('hr_job_titles'),
            ],
            'department_id' => [
                'required',
                'integer',
                $this->centerExistsRule('departments'),
            ],
            'employee_type_id' => [
                'nullable',
                'integer',
                $this->tenantExistsRule('hr_employee_types'),
            ],
            'nationality_id' => [
                'nullable',
                'integer',
                $this->existsRule('nationalities'),
            ],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'gender' => ['nullable', 'in:male,female'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
        ];
    }
}
```

**`app/Http/Requests/HR/LeaveStoreRequest.php`:**
```php
<?php

namespace App\Http\Requests\HR;

use App\Http\Requests\Concerns\TenantAware;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveStoreRequest extends FormRequest
{
    use TenantAware;

    public function authorize(): bool
    {
        return $this->user()->can('leaves.create');
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                $this->tenantExistsRule('hr_employees'),
            ],
            'leave_type' => ['required', Rule::in(['annual', 'sick', 'unpaid', 'maternity', 'pilgrimage', 'emergency'])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
```

**`app/Http/Requests/HR/LeaveApproveRequest.php`:**
```php
<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class LeaveApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leaves.approve');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

### Step 1.7: Generic FormRequests

**`app/Http/Requests/ReportFilterRequest.php`:**
```php
<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\FilterableRequest;
use App\Http\Requests\Concerns\PaginatesRequest;
use App\Http\Requests\Concerns\SortableRequest;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    use PaginatesRequest, SortableRequest, FilterableRequest;

    public function authorize(): bool
    {
        return true; // Reports accessible to all authenticated
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'max:50'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
```

**`app/Http/Requests/Auth/TwoFactorVerifyRequest.php`:**
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ];
    }
}
```

**`app/Http/Requests/Auth/PhoneOtpRequest.php`:**
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PhoneOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+9665\d{8}$/'],
        ];
    }
}
```

### Step 1.8: Update Controllers (الربط)

**`app/Http/Controllers/App/CustomerController.php` (refactored):**
```php
<?php

namespace App\Http\Controllers\App;

use App\Http\Requests\Customer\CustomerMergeRequest;
use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->withCount(['vehicles', 'quotes', 'workOrders'])
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => request()->only(['search', 'type', 'date_from', 'date_to']),
        ]);
    }

    public function store(CustomerStoreRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());
        return redirect()->back()->with('customer', $customer);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());
        return redirect()->back()->with('success', __('messages.customer_updated'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);
        $customer->forceDelete();
        return redirect()->route('customers.index')->with('success', __('messages.customer_deleted'));
    }

    public function merge(CustomerMergeRequest $request, Customer $source, Customer $target): RedirectResponse
    {
        app(\App\Actions\Customer\MergeCustomerAction::class)
            ->execute($source, $target, $request->user());

        return redirect()->route('customers.show', $target)
            ->with('success', __('messages.customers_merged'));
    }
}
```

**`app/Http/Controllers/App/WorkOrders/WorkOrderStatusController.php` (refactored):**
```php
<?php

namespace App\Http\Controllers\App\WorkOrders;

use App\Http\Requests\WorkOrder\ChangeStatusRequest;
use App\Http\Requests\WorkOrder\CompleteRequest;
use App\Http\Requests\WorkOrder\UpdateConditionRequest;
use App\Models\WorkOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class WorkOrderStatusController
{
    use AuthorizesRequests;

    public function updateCondition(UpdateConditionRequest $request, WorkOrder $work_order): mixed
    {
        $work_order->update($request->validated());
        return back()->with('success', __('messages.condition_updated'));
    }

    public function startWork(WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);

        if (!in_array($work_order->status, [WorkOrder::STATUS_OPEN, WorkOrder::STATUS_IN_PROGRESS])) {
            return back()->with('error', __('messages.cannot_start_work'));
        }

        if ($work_order->items()->count() === 0) {
            return back()->with('error', __('messages.cannot_start_work_no_services'));
        }

        \DB::transaction(function () use ($work_order) {
            $work_order->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);
            $work_order->items()
                ->where('status', \App\Models\WorkOrderItem::STATUS_PENDING)
                ->update(['status' => \App\Models\WorkOrderItem::STATUS_IN_PROGRESS]);
        });

        $work_order->logActivity('status_changed', /* ... */);

        return back()->with('success', __('messages.work_order_started'));
    }

    public function putOnHold(ChangeStatusRequest $request, WorkOrder $work_order): RedirectResponse
    {
        $validated = $request->validated();

        if (!$work_order->canBeOnHold()) {
            return back()->with('error', __('messages.cannot_put_on_hold'));
        }

        $work_order->putOnHold($validated['reason']);
        $work_order->logActivity('status_changed', /* ... */);

        return back()->with('success', __('messages.work_order_on_hold'));
    }

    public function complete(CompleteRequest $request, WorkOrder $work_order): RedirectResponse
    {
        $validated = $request->validated();

        if ($work_order->balance < -0.01) {
            return back()->with('error', __('messages.cannot_complete_excess_payments'));
        }

        $exitDate = \Carbon\Carbon::parse($validated['exit_date']);
        // ... completion logic
    }
}
```

### Step 1.9: Update Routes (الربط)

في `routes/web.php` و `routes/auth.php` — أبق الـ routes كما هي، الـ FormRequest الجديد يتم استدعاؤه تلقائياً.

---

## ✅ معايير النجاح - Week 1-2

```bash
# 1. Critical Fixes
./scripts/check-tenant-scope.sh
# Expected: 0 failed

# 2. FormRequests created
find app/Http/Requests -name "*.php" | wc -l
# Expected: 50+ (was 15)

# 3. Tests pass
php artisan test
# Expected: 290+ tests passing

# 4. No inline validate() in controllers (in audited areas)
grep -rn "validate(" app/Http/Controllers/App/CustomerController.php | wc -l
# Expected: 0
```

**Commit strategy:**
```bash
git checkout -b feature/phase-1-foundation

git commit -m "phase-1(d1): fix SQLite migration compatibility (cross-driver)"
git commit -m "phase-1(d2): improve tenant-scope check script (no broad exemptions)"
git commit -m "phase-1(d3): add FormRequest traits (TenantAware, PaginatesRequest, etc.)"
git commit -m "phase-1(d4): extract Customer FormRequests (3 files)"
git commit -m "phase-1(d5): extract WorkOrder FormRequests (5 files)"
git commit -m "phase-1(d6): extract Payment FormRequests (3 files)"
git commit -m "phase-1(d7): extract Inventory FormRequests (4 files)"
git commit -m "phase-1(d8): extract HR FormRequests (6 files)"
git commit -m "phase-1(d9): refactor CustomerController to use FormRequests"
git commit -m "phase-1(d10): refactor WorkOrderStatusController to use FormRequests"
```

---

# 🏗️ WEEK 3-4: Frontend Testing Infrastructure

## المهمة 2: Frontend Tests (يوم 6-10)

### Step 2.1: Setup (install dependencies if not present)

```bash
# Check if installed
cat package.json | grep -E "vitest|@vue/test-utils|@vitest/coverage"

# If not:
npm install -D @vitest/coverage-v8 happy-dom @vitest/ui
```

### Step 2.2: Vitest Configuration

**`vitest.config.ts` (verify/update):**
```typescript
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [vue()],
    test: {
        globals: true,
        environment: 'happy-dom',
        include: ['resources/js/**/*.test.{js,ts}', 'resources/js/**/*.spec.{js,ts}'],
        coverage: {
            reporter: ['text', 'html', 'lcov'],
            exclude: [
                '**/node_modules/**',
                '**/dist/**',
                '**/*.stories.*',
                '**/*.story.*',
            ],
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
});
```

### Step 2.3: Composables Tests (الأساس)

**`resources/js/Composables/useTheme.test.js`:**
```javascript
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useTheme } from './useTheme';

describe('useTheme', () => {
    beforeEach(() => {
        // Reset DOM class
        document.documentElement.className = '';
        // Reset localStorage
        localStorage.clear();
    });

    it('initializes from document class', () => {
        document.documentElement.classList.add('dark');
        const { isDark } = useTheme();
        expect(isDark.value).toBe(true);
    });

    it('initializes from localStorage if no class', () => {
        localStorage.setItem('theme', 'dark');
        const { isDark } = useTheme();
        expect(isDark.value).toBe(true);
    });

    it('toggles theme', () => {
        const { isDark, toggle } = useTheme();
        expect(isDark.value).toBe(false);

        toggle();
        expect(isDark.value).toBe(true);
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        toggle();
        expect(isDark.value).toBe(false);
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('saves to localStorage on toggle', () => {
        const { toggle } = useTheme();
        toggle();
        expect(localStorage.getItem('theme')).toBe('dark');
    });
});
```

**`resources/js/Composables/useFormatters.test.js`:**
```javascript
import { describe, it, expect } from 'vitest';
import { useFormatters } from './useFormatters';

describe('useFormatters', () => {
    it('formats currency', () => {
        const { formatCurrency } = useFormatters();
        expect(formatCurrency(1000)).toContain('1,000');
        expect(formatCurrency(1000.5)).toContain('1,000.50');
    });

    it('formats dates', () => {
        const { formatDate } = useFormatters();
        const date = new Date('2026-01-15');
        expect(formatDate(date)).toMatch(/2026|15/);
    });

    it('formats numbers with Arabic locale', () => {
        const { formatNumber } = useFormatters();
        expect(formatNumber(1234.56)).toContain('1,234');
    });
});
```

**`resources/js/Composables/usePermission.test.js`:**
```javascript
import { describe, it, expect } from 'vitest';
import { usePermission } from './usePermission';

describe('usePermission', () => {
    it('returns can() function', () => {
        const { can } = usePermission();
        expect(typeof can).toBe('function');
    });

    it('can() returns true for granted permission', () => {
        // Setup auth state
        // (this depends on your Pinia/store implementation)
    });

    it('can() returns false for denied permission', () => {
        // ...
    });

    it('canAny() returns true if any granted', () => {
        // ...
    });
});
```

**`resources/js/Composables/useToast.test.js`:**
```javascript
import { describe, it, expect, vi } from 'vitest';
import { useToast } from './useToast';

describe('useToast', () => {
    it('shows success toast', () => {
        const { show, toasts } = useToast();
        show('Success!', 'success');
        expect(toasts.value.length).toBe(1);
        expect(toasts.value[0].message).toBe('Success!');
        expect(toasts.value[0].type).toBe('success');
    });

    it('shows error toast', () => {
        const { show, toasts } = useToast();
        show('Error!', 'error');
        expect(toasts.value[0].type).toBe('error');
    });

    it('auto-dismisses after timeout', async () => {
        vi.useFakeTimers();
        const { show, toasts } = useToast();
        show('Test', 'info', 1000);
        expect(toasts.value.length).toBe(1);
        vi.advanceTimersByTime(1100);
        expect(toasts.value.length).toBe(0);
        vi.useRealTimers();
    });
});
```

**`resources/js/Composables/useConfirm.test.js`:**
```javascript
import { describe, it, expect, vi } from 'vitest';
import { useConfirm } from './useConfirm';

describe('useConfirm', () => {
    it('opens confirm dialog', () => {
        const { isOpen, open } = useConfirm();
        expect(isOpen.value).toBe(false);
        open({ title: 'Delete?', message: 'Are you sure?' });
        expect(isOpen.value).toBe(true);
    });

    it('resolves with true on confirm', async () => {
        const { open, confirm, cancel } = useConfirm();
        const promise = open({ title: 'Test' });
        confirm();
        await expect(promise).resolves.toBe(true);
    });

    it('resolves with false on cancel', async () => {
        const { open, cancel } = useConfirm();
        const promise = open({ title: 'Test' });
        cancel();
        await expect(promise).resolves.toBe(false);
    });
});
```

### Step 2.4: Plugins Tests

**`resources/js/Plugins/safeHtml.test.js`:**
```javascript
import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import SafeHtmlPlugin from './safeHtml';

describe('SafeHtmlPlugin', () => {
    let app;

    beforeEach(() => {
        app = {
            directive: vi.fn(),
        };
    });

    it('registers v-safe-html directive', () => {
        SafeHtmlPlugin.install(app);
        expect(app.directive).toHaveBeenCalledWith('safe-html', expect.any(Object));
    });
});

describe('v-safe-html directive', () => {
    it('sanitizes XSS attempts', async () => {
        // Mount with DOMPurify
        const Component = {
            template: '<div v-safe-html="html"></div>',
            props: ['html'],
        };
        const wrapper = mount(Component, {
            props: { html: '<script>alert("xss")</script>Hello' },
            global: { plugins: [SafeHtmlPlugin] },
        });
        expect(wrapper.html()).not.toContain('<script>');
        expect(wrapper.html()).toContain('Hello');
    });

    it('allows safe HTML', () => {
        const Component = {
            template: '<div v-safe-html="html"></div>',
            props: ['html'],
        };
        const wrapper = mount(Component, {
            props: { html: '<p>Hello <strong>World</strong></p>' },
            global: { plugins: [SafeHtmlPlugin] },
        });
        expect(wrapper.html()).toContain('<p>');
        expect(wrapper.html()).toContain('<strong>');
    });
});
```

**`resources/js/Plugins/arabicNumerals.test.js`:**
```javascript
import { describe, it, expect } from 'vitest';
import { ArabicNumeralsPlugin } from './arabicNumerals';

describe('ArabicNumeralsPlugin', () => {
    it('converts English numerals to Arabic', () => {
        const app = { directive: vi.fn() };
        ArabicNumeralsPlugin.install(app);
        // Trigger directive manually
        const directive = app.directive.mock.calls[0][1];
        const el = document.createElement('div');
        el.textContent = '123';
        directive.mounted(el);
        expect(el.textContent).toBe('١٢٣');
    });
});
```

### Step 2.5: App Component Tests

**`resources/js/Components/App/AppButton.test.ts`:**
```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppButton from './AppButton.vue';

describe('AppButton', () => {
    it('renders with default props', () => {
        const wrapper = mount(AppButton, {
            slots: { default: 'Click me' },
        });
        expect(wrapper.text()).toBe('Click me');
        expect(wrapper.classes()).toContain('app-button');
    });

    it('renders primary variant', () => {
        const wrapper = mount(AppButton, {
            props: { variant: 'primary' },
            slots: { default: 'Primary' },
        });
        expect(wrapper.classes()).toContain('app-button--primary');
    });

    it('emits click event', async () => {
        const wrapper = mount(AppButton, {
            slots: { default: 'Click' },
        });
        await wrapper.trigger('click');
        expect(wrapper.emitted('click')).toBeTruthy();
    });

    it('shows loading spinner when loading=true', () => {
        const wrapper = mount(AppButton, {
            props: { loading: true },
            slots: { default: 'Loading' },
        });
        expect(wrapper.find('.app-button__spinner').exists()).toBe(true);
        expect(wrapper.attributes('disabled')).toBeDefined();
    });

    it('respects size prop', () => {
        const wrapper = mount(AppButton, {
            props: { size: 'lg' },
            slots: { default: 'Large' },
        });
        expect(wrapper.classes()).toContain('app-button--lg');
    });
});
```

**`resources/js/Components/App/AppInput.test.ts`:**
```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppInput from './AppInput.vue';

describe('AppInput', () => {
    it('renders input with label', () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '', label: 'Name' },
        });
        expect(wrapper.find('label').text()).toBe('Name');
    });

    it('emits update:modelValue on input', async () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '' },
        });
        const input = wrapper.find('input');
        await input.setValue('test');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['test']);
    });

    it('shows error message', () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '', error: 'Required' },
        });
        expect(wrapper.text()).toContain('Required');
        expect(wrapper.find('input').attributes('aria-invalid')).toBe('true');
    });

    it('shows hint message', () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '', hint: 'Enter your name' },
        });
        expect(wrapper.text()).toContain('Enter your name');
    });

    it('supports suffix for currency', () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: 0, type: 'number', suffix: 'SAR' },
        });
        expect(wrapper.text()).toContain('SAR');
    });
});
```

### Step 2.6: Common Component Tests

**`resources/js/Components/Common/StatusBadge.test.ts`:**
```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import StatusBadge from './StatusBadge.vue';

describe('StatusBadge', () => {
    it.each([
        ['active', 'badge-success'],
        ['inactive', 'badge-gray'],
        ['pending', 'badge-warning'],
        ['error', 'badge-danger'],
    ])('applies %s variant', (status, expectedClass) => {
        const wrapper = mount(StatusBadge, {
            props: { status },
            slots: { default: status },
        });
        expect(wrapper.classes()).toContain(expectedClass);
    });
});
```

**`resources/js/Components/Common/ConfirmModal.test.ts`:**
```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ConfirmModal from './ConfirmModal.vue';

describe('ConfirmModal', () => {
    it('renders when open', () => {
        const wrapper = mount(ConfirmModal, {
            props: {
                open: true,
                title: 'Delete?',
                message: 'Are you sure?',
            },
        });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
    });

    it('emits confirm on confirm button', async () => {
        const wrapper = mount(ConfirmModal, {
            props: { open: true, title: 'Test' },
        });
        await wrapper.find('[data-test="confirm"]').trigger('click');
        expect(wrapper.emitted('confirm')).toBeTruthy();
    });

    it('emits cancel on cancel button', async () => {
        const wrapper = mount(ConfirmModal, {
            props: { open: true, title: 'Test' },
        });
        await wrapper.find('[data-test="cancel"]').trigger('click');
        expect(wrapper.emitted('cancel')).toBeTruthy();
    });

    it('hides when open=false', () => {
        const wrapper = mount(ConfirmModal, {
            props: { open: false, title: 'Test' },
        });
        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });
});
```

### Step 2.7: Update package.json

**`package.json` scripts:**
```json
{
  "scripts": {
    "build": "vite build",
    "dev": "vite",
    "type-check": "vue-tsc --noEmit",
    "test": "vitest run",
    "test:watch": "vitest",
    "test:coverage": "vitest run --coverage",
    "test:ui": "vitest --ui",
    "lint": "eslint . --ext .vue,.js,.ts",
    "format": "prettier --write .",
    "prepare": "husky"
  }
}
```

**⚠️ ملاحظة:** شيل `--passWithNoTests` — يجب أن يفشل CI لو ما في tests.

### Step 2.8: CI Integration

**`.github/workflows/ci.yml` — أضف frontend tests:**
```yaml
  test-frontend:
    name: Test Frontend (Vitest)
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      - name: Install npm dependencies
        run: npm ci

      - name: Run Vitest
        run: npm run test -- --reporter=verbose

      - name: Coverage report
        if: always()
        run: npm run test:coverage
```

---

## ✅ معايير النجاح - Week 3-4

```bash
# 1. Frontend tests count
find resources/js -name "*.test.*" -o -name "*.spec.*" | wc -l
# Expected: 30+ (was 0)

# 2. Frontend tests pass
npm run test
# Expected: 30+ passing, 0 failing

# 3. Coverage
npm run test:coverage
# Expected: 50%+ statements covered

# 4. No --passWithNoTests
grep "passWithNoTests" package.json
# Expected: 0 results
```

**Commit strategy:**
```bash
git commit -m "phase-1(d11): add vitest config (happy-dom, coverage)"
git commit -m "phase-1(d12): add 5 composables tests (useTheme, useFormatters, usePermission, useToast, useConfirm)"
git commit -m "phase-1(d13): add 2 plugins tests (safeHtml, arabicNumerals)"
git commit -m "phase-1(d14): add 5 App component tests (AppButton, AppInput, AppSelect, AppTextarea, AppCheckbox)"
git commit -m "phase-1(d15): add 3 common component tests (StatusBadge, ConfirmModal, EmptyState)"
git commit -m "phase-1(d16): update package.json scripts (no passWithNoTests)"
git commit -m "phase-1(d17): add frontend tests to CI workflow"
```

---

# 🏗️ WEEK 5-6: Critical Unit Tests + Events Foundation

## المهمة 3: Unit Tests للـ Critical Services (يوم 11-15)

### Step 3.1: PricingHelperTest

**`tests/Unit/Support/PricingHelperTest.php`:**
```php
<?php

namespace Tests\Unit\Support;

use App\Support\PricingHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PricingHelperTest extends TestCase
{
    public function test_no_discount_returns_zero(): void
    {
        $this->assertEquals(0, PricingHelper::computeDiscountAmount(100, 'none', null));
        $this->assertEquals(0, PricingHelper::computeDiscountAmount(100, 'none', 0));
        $this->assertEquals(0, PricingHelper::computeDiscountAmount(100, 'none', -5));
    }

    public function test_percentage_discount(): void
    {
        // 10% of 100 = 10
        $this->assertEquals(10, PricingHelper::computeDiscountAmount(100, 'percentage', 10));

        // 50% of 200 = 100
        $this->assertEquals(100, PricingHelper::computeDiscountAmount(200, 'percentage', 50));

        // Cap at 100%
        $this->assertEquals(100, PricingHelper::computeDiscountAmount(100, 'percentage', 150));
    }

    public function test_fixed_discount_capped_at_price(): void
    {
        $this->assertEquals(30, PricingHelper::computeDiscountAmount(100, 'fixed', 30));
        $this->assertEquals(50, PricingHelper::computeDiscountAmount(50, 'fixed', 100)); // Capped
    }

    public function test_final_unit_price_never_negative(): void
    {
        $this->assertEquals(0, PricingHelper::computeFinalUnitPrice(100, 'fixed', 150));
    }

    public function test_line_total_with_quantity(): void
    {
        $result = PricingHelper::computeLineTotal(100, 'percentage', 10, 3, 0);
        $this->assertEquals(27, $result['discount_amount']); // 10% of 100 = 10
        $this->assertEquals(90, $result['final_unit_price']);
        $this->assertEquals(270, $result['line_total']); // 90 * 3
    }

    public function test_min_price_validation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PricingHelper::computeLineTotal(100, 'fixed', 50, 1, 60); // Final = 50 < min 60
    }

    public function test_rounding_to_2_decimals(): void
    {
        $result = PricingHelper::computeLineTotal(99.99, 'percentage', 33.33, 2, 0);
        $this->assertEquals(2, count(array_filter($result, fn($v) => abs($v - round($v, 2)) < 0.001)));
    }
}
```

### Step 3.2: InventoryServiceTest

**`tests/Unit/Services/Inventory/InventoryServiceTest.php`:**
```php
<?php

namespace Tests\Unit\Services\Inventory;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;
    protected Tenant $tenant;
    protected Center $center;
    protected Warehouse $warehouse;
    protected Part $part;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->forTenant($this->tenant)->create();
        $this->warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $this->part = Part::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
    }

    public function test_receipt_creates_balance_and_move(): void
    {
        $move = $this->service->receipt(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 50,
            userId: $this->user->id,
        );

        $this->assertInstanceOf(InventoryMove::class, $move);
        $this->assertEquals(10, $move->qty);
        $this->assertEquals('receipt', $move->move_type);

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        $this->assertEquals(10, $balance->qty_on_hand);
        $this->assertEquals(50, $balance->wac_cost);
    }

    public function test_receipt_rejects_negative_qty(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->receipt(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: -5,
            unitCost: 50,
        );
    }

    public function test_issue_reduces_stock(): void
    {
        // Setup: add 10 units
        $this->service->receipt($this->warehouse->id, $this->part->id, 10, 50);
        // Issue 3
        $this->service->issue($this->warehouse->id, $this->part->id, 3);

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        $this->assertEquals(7, $balance->qty_on_hand);
    }

    public function test_issue_blocks_insufficient_stock(): void
    {
        $this->service->receipt($this->warehouse->id, $this->part->id, 5, 50);

        $this->expectException(ValidationException::class);
        $this->service->issue($this->warehouse->id, $this->part->id, 10);
    }

    public function test_issue_allows_negative_when_flagged(): void
    {
        $this->service->receipt($this->warehouse->id, $this->part->id, 2, 50);
        $this->service->issue($this->warehouse->id, $this->part->id, 5, allowNegative: true);

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        $this->assertEquals(-3, $balance->qty_on_hand);
    }

    public function test_wac_calculation_with_multiple_receipts(): void
    {
        // First receipt: 10 @ 50 = WAC 50
        $this->service->receipt($this->warehouse->id, $this->part->id, 10, 50);
        // Second receipt: 10 @ 70 = WAC (10*50 + 10*70) / 20 = 60
        $this->service->receipt($this->warehouse->id, $this->part->id, 10, 70);

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        $this->assertEquals(20, $balance->qty_on_hand);
        $this->assertEquals(60, $balance->wac_cost);
    }

    public function test_wac_unchanged_on_issue(): void
    {
        $this->service->receipt($this->warehouse->id, $this->part->id, 10, 50);
        $this->service->issue($this->warehouse->id, $this->part->id, 3);

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        $this->assertEquals(7, $balance->qty_on_hand);
        $this->assertEquals(50, $balance->wac_cost); // WAC unchanged
    }

    public function test_transfer_moves_stock_between_warehouses(): void
    {
        $warehouse2 = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $this->service->receipt($this->warehouse->id, $this->part->id, 10, 50);
        $this->service->transfer(
            fromWarehouseId: $this->warehouse->id,
            toWarehouseId: $warehouse2->id,
            partId: $this->part->id,
            qty: 4,
        );

        $b1 = InventoryBalance::where('warehouse_id', $this->warehouse->id)->first();
        $b2 = InventoryBalance::where('warehouse_id', $warehouse2->id)->first();
        $this->assertEquals(6, $b1->qty_on_hand);
        $this->assertEquals(4, $b2->qty_on_hand);
    }
}
```

### Step 3.3: WorkOrderSuggestionServiceTest

**`tests/Unit/Services/AI/WorkOrderSuggestionServiceTest.php`:**
```php
<?php

namespace Tests\Unit\Services\AI;

use App\Models\Center;
use App\Models\Part;
use App\Models\Prompt;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AI\Providers\MockProvider;
use App\Services\AI\ProviderRegistry;
use App\Services\AI\WorkOrderSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderSuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_local_mock_when_no_api_key(): void
    {
        config(['services.openai.key' => '']);

        $service = app(WorkOrderSuggestionService::class);
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->forTenant($tenant)->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id]);
        $wo = WorkOrder::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);

        $request = \Illuminate\Http\Request::create('/', 'POST', ['complaint' => 'test complaint']);
        $request->setUserResolver(fn() => $user);

        $result = $service->suggest($wo, $request, $user, $tenant->id, $center->id);

        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('total_candidates', $result['meta']);
        $this->assertArrayHasKey('returned', $result['meta']);
    }

    public function test_drops_hallucinated_item_ids(): void
    {
        // Mock provider that returns invalid IDs
        $mockProvider = $this->createMock(MockProvider::class);
        $mockProvider->method('name')->willReturn('mock');
        $mockProvider->method('complete')->willReturn(new \App\Services\AI\CompletionResponse(
            content: json_encode(['suggestions' => [
                ['item_type' => 'service', 'item_id' => 99999, 'name' => 'Fake', 'confidence' => 0.9],
            ]]),
            inputTokens: 0,
            outputTokens: 0,
            cost: 0,
            provider: 'mock',
            model: 'mock',
        ));

        app(ProviderRegistry::class)->register('test', fn() => $mockProvider);

        $service = app(WorkOrderSuggestionService::class);
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->forTenant($tenant)->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id]);
        $wo = WorkOrder::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);

        $request = \Illuminate\Http\Request::create('/', 'POST', ['complaint' => 'test']);
        $request->setUserResolver(fn() => $user);

        $result = $service->suggest($wo, $request, $user, $tenant->id, $center->id);

        // total_candidates is PRE-defense-in-depth (per contract)
        $this->assertEquals(1, $result['meta']['total_candidates']);
        $this->assertEquals(0, $result['meta']['returned']); // Hallucinated ID dropped
    }

    public function test_returns_502_on_invalid_json(): void
    {
        // ... mock provider returning non-JSON ...
    }
}
```

### Step 3.4: PaymentServiceTest

**`tests/Unit/Services/PaymentServiceTest.php`:**
```php
<?php

namespace Tests\Unit\Services;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_payment_updates_invoice_status(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->forTenant($tenant)->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $wo = WorkOrder::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'work_order_id' => $wo->id,
            'total_incl_tax' => 1000,
            'payment_status' => 'unpaid',
        ]);

        $service = app(PaymentService::class);
        $payment = $service->recordPayment($invoice, [
            'amount' => 1000,
            'type' => 'payment',
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(1000, $payment->amount);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->payment_status);
        $this->assertEquals(1000, $invoice->total_paid);
    }

    public function test_partial_payment(): void
    {
        // ... test that 500/1000 sets to 'partial' ...
    }

    public function test_refund_creates_refund_payment(): void
    {
        // ... test refund flow ...
    }

    public function test_payment_uses_enum_type(): void
    {
        // ... test that invalid type throws ...
    }
}
```

---

## المهمة 4: Events Foundation (يوم 16-20)

### Step 4.1: First 10 Events (الأكثف استخداماً)

**`app/Events/WorkOrder/WorkOrderCreated.php`:**
```php
<?php

namespace App\Events\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WorkOrder $workOrder) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->workOrder->tenant_id}"),
            new PrivateChannel("center.{$this->workOrder->center_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'work-order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->workOrder->id,
            'code' => $this->workOrder->code,
            'customer' => $this->workOrder->customer?->only(['id', 'name']),
            'vehicle' => $this->workOrder->vehicle?->only(['id', 'plate_number']),
        ];
    }
}
```

**`app/Events/WorkOrder/WorkOrderStatusChanged.php`:**
```php
<?php

namespace App\Events\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkOrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WorkOrder $workOrder,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("work-order.{$this->workOrder->id}"),
            new PrivateChannel("tenant.{$this->workOrder->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'work-order.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_at' => now()->toIso8601String(),
        ];
    }
}
```

**`app/Events/Payment/PaymentRecorded.php`:**
```php
<?php

namespace App\Events\Payment;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->payment->tenant_id}"),
            new PrivateChannel("invoice.{$this->payment->invoice_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->payment->id,
            'amount' => (float) $this->payment->amount,
            'type' => $this->payment->type->value,
            'method' => $this->payment->payment_method,
            'invoice_id' => $this->payment->invoice_id,
        ];
    }
}
```

**`app/Events/Invoice/InvoiceIssued.php`:**
```php
<?php

namespace App\Events\Invoice;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceIssued implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->invoice->tenant_id}"),
            new PrivateChannel("customer.{$this->invoice->customer_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'invoice.issued';
    }
}
```

**`app/Events/Customer/CustomerCreated.php`:**
```php
<?php

namespace App\Events\Customer;

use App\Models\Customer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Customer $customer) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->customer->tenant_id}")];
    }

    public function broadcastAs(): string
    {
        return 'customer.created';
    }
}
```

**`app/Events/Part/StockLow.php`:**
```php
<?php

namespace App\Events\Part;

use App\Models\Part;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockLow implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Part $part,
        public int $warehouseId,
        public float $currentQty,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->part->tenant_id}"),
            new PrivateChannel("warehouse.{$this->warehouseId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.low';
    }
}
```

**`app/Events/Auth/LoginSuccessful.php`:**
```php
<?php

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginSuccessful
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public string $ipAddress,
    ) {}

    public function logContext(): array
    {
        return [
            'event' => 'login.successful',
            'user_id' => $this->user->id,
            'tenant_id' => $this->user->tenant_id,
            'ip' => $this->ipAddress,
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

**`app/Events/Auth/LoginFailed.php`:**
```php
<?php

namespace App\Events\Auth;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $email,
        public string $ipAddress,
        public string $reason,
    ) {}
}
```

**`app/Events/HR/LeaveRequested.php`:**
```php
<?php

namespace App\Events\HR;

use App\Models\HR\Leave;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Leave $leave) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->leave->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'leave.requested';
    }
}
```

**`app/Events/HR/LeaveApproved.php`:**
```php
<?php

namespace App\Events\HR;

use App\Models\HR\Leave;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Leave $leave,
        public int $approverId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("leave.{$this->leave->id}"),
            new PrivateChannel("tenant.{$this->leave->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'leave.approved';
    }
}
```

### Step 4.2: First 5 Listeners

**`app/Listeners/WorkOrder/LogActivityOnStatusChange.php`:**
```php
<?php

namespace App\Listeners\WorkOrder;

use App\Events\WorkOrder\WorkOrderStatusChanged;
use App\Services\NotificationService;

class LogActivityOnStatusChange
{
    public function __construct(public NotificationService $notifications) {}

    public function handle(WorkOrderStatusChanged $event): void
    {
        $event->workOrder->logActivity('status_changed', __(
            'work_orders.activities.actions.status_changed',
            ['from' => $event->oldStatus, 'to' => $event->newStatus]
        ));
    }
}
```

**`app/Listeners/WorkOrder/NotifyOwnerOnCreation.php`:**
```php
<?php

namespace App\Listeners\WorkOrder;

use App\Events\WorkOrder\WorkOrderCreated;
use App\Services\NotificationService;

class NotifyOwnerOnCreation
{
    public function handle(WorkOrderCreated $event): void
    {
        NotificationService::notifyOwner(
            tenantId: $event->workOrder->tenant_id,
            type: 'work_order.created',
            title: 'أمر عمل جديد #' . ($event->workOrder->code ?? $event->workOrder->id),
            body: 'تم إنشاء أمر عمل جديد',
            actionUrl: '/app/work-orders/' . $event->workOrder->id,
        );
    }
}
```

**`app/Listeners/Payment/UpdateInvoiceStatusOnPayment.php`:**
```php
<?php

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentRecorded;
use App\Jobs\AutoCreateInvoiceForDoneWorkOrderJob;

class UpdateInvoiceStatusOnPayment
{
    public function handle(PaymentRecorded $event): void
    {
        // Update invoice status
        if ($event->payment->invoice) {
            $event->payment->invoice->updatePaymentStatus();
        }

        // Dispatch job for auto invoice creation if needed
        if ($event->payment->work_order_id
            && $event->payment->workOrder?->status === 'done'
            && !$event->payment->workOrder?->invoice) {
            AutoCreateInvoiceForDoneWorkOrderJob::dispatch(
                $event->payment->work_order_id,
                $event->payment->received_by,
            );
        }
    }
}
```

**`app/Listeners/Auth/LogSuccessfulLogin.php`:**
```php
<?php

namespace App\Listeners\Auth;

use App\Events\Auth\LoginSuccessful;

class LogSuccessfulLogin
{
    public function handle(LoginSuccessful $event): void
    {
        \Log::channel('ai')->info('auth.login.successful', $event->logContext());

        \App\Models\AdminActivityLog::create([
            'tenant_id' => $event->user->tenant_id,
            'user_id' => $event->user->id,
            'action' => 'login.successful',
            'description' => 'User logged in successfully',
            'ip_address' => $event->ipAddress,
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([]),
        ]);
    }
}
```

**`app/Listeners/Auth/LogFailedLogin.php`:**
```php
<?php

namespace App\Listeners\Auth;

use App\Events\Auth\LoginFailed;

class LogFailedLogin
{
    public function handle(LoginFailed $event): void
    {
        \Log::warning('auth.login.failed', [
            'email' => $event->email,
            'ip' => $event->ipAddress,
            'reason' => $event->reason,
        ]);
    }
}
```

### Step 4.3: EventServiceProvider Registration

**`app/Providers/EventServiceProvider.php`:**
```php
<?php

namespace App\Providers;

use App\Events\Auth\LoginFailed;
use App\Events\Auth\LoginSuccessful;
use App\Events\Customer\CustomerCreated;
use App\Events\HR\LeaveApproved;
use App\Events\HR\LeaveRequested;
use App\Events\Invoice\InvoiceIssued;
use App\Events\Part\StockLow;
use App\Events\Payment\PaymentRecorded;
use App\Events\WorkOrder\WorkOrderCreated;
use App\Events\WorkOrder\WorkOrderStatusChanged;
use App\Listeners\Auth\LogFailedLogin;
use App\Listeners\Auth\LogSuccessfulLogin;
use App\Listeners\HR\NotifyManagerOnLeaveRequest;
use App\Listeners\Invoice\SendInvoiceIssuedNotification;
use App\Listeners\Part\NotifyOnLowStock;
use App\Listeners\Payment\UpdateInvoiceStatusOnPayment;
use App\Listeners\WorkOrder\LogActivityOnStatusChange;
use App\Listeners\WorkOrder\NotifyOwnerOnCreation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        WorkOrderCreated::class => [
            NotifyOwnerOnCreation::class,
        ],
        WorkOrderStatusChanged::class => [
            LogActivityOnStatusChange::class,
        ],
        PaymentRecorded::class => [
            UpdateInvoiceStatusOnPayment::class,
        ],
        InvoiceIssued::class => [
            SendInvoiceIssuedNotification::class,
        ],
        CustomerCreated::class => [
            // (no listeners yet)
        ],
        StockLow::class => [
            NotifyOnLowStock::class,
        ],
        LeaveRequested::class => [
            NotifyManagerOnLeaveRequest::class,
        ],
        LeaveApproved::class => [
            // (no listeners yet)
        ],
        LoginSuccessful::class => [
            LogSuccessfulLogin::class,
        ],
        LoginFailed::class => [
            LogFailedLogin::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
```

**`bootstrap/app.php` — Register EventServiceProvider:**
```php
->withProviders([
    App\Providers\RouteServiceProvider::class,
    App\Providers\EventServiceProvider::class,
])
```

### Step 4.4: Trigger Events in Observers (Decoupling)

**`app/Observers/WorkOrderObserver.php` (جديد):**
```php
<?php

namespace App\Observers;

use App\Events\WorkOrder\WorkOrderCreated;
use App\Events\WorkOrder\WorkOrderStatusChanged;
use App\Models\WorkOrder;

class WorkOrderObserver
{
    public function created(WorkOrder $workOrder): void
    {
        event(new WorkOrderCreated($workOrder));
    }

    public function updated(WorkOrder $workOrder): void
    {
        if ($workOrder->isDirty('status')) {
            event(new WorkOrderStatusChanged(
                $workOrder,
                $workOrder->getOriginal('status'),
                $workOrder->status,
            ));
        }
    }
}
```

**`app/Observers/PaymentObserver.php` (جديد):**
```php
<?php

namespace App\Observers;

use App\Events\Payment\PaymentRecorded;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        event(new PaymentRecorded($payment));
    }
}
```

**`app/Providers/AppServiceProvider.php` — Register Observers:**
```php
public function boot(): void
{
    // ... existing code ...
    
    \App\Models\WorkOrder::observe(\App\Observers\WorkOrderObserver::class);
    \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);
}
```

### Step 4.5: Refactor Payment::boot() (Decouple)

**`app/Models/Payment.php` — إزالة business logic من observer:**
```php
// BEFORE: 60+ lines of business logic in boot()
// AFTER: Just dispatch event

protected static function booted(): void
{
    static::created(function (Payment $payment) {
        // Link to invoice if WO has one
        if ($payment->work_order_id && !$payment->invoice_id) {
            $invoice = $payment->workOrder?->invoice;
            if ($invoice) {
                $payment->invoice_id = $invoice->id;
                $payment->saveQuietly();
            }
        }
        // ... other side effects removed ...
    });
}
```

**`app/Listeners/Payment/LinkPaymentToInvoice.php` (new):**
```php
<?php

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentRecorded;

class LinkPaymentToInvoice
{
    public function handle(PaymentRecorded $event): void
    {
        $payment = $event->payment;
        if ($payment->work_order_id && !$payment->invoice_id) {
            $invoice = $payment->workOrder?->invoice;
            if ($invoice) {
                $payment->invoice_id = $invoice->id;
                $payment->saveQuietly();
            }
        }
    }
}
```

---

## ✅ معايير النجاح - Week 5-6

```bash
# 1. Events created
find app/Events -name "*.php" | wc -l
# Expected: 10+

# 2. Listeners created
find app/Listeners -name "*.php" | wc -l
# Expected: 10+

# 3. EventServiceProvider registered
grep "EventServiceProvider" bootstrap/app.php
# Expected: 1 match

# 4. Observers trigger events
grep "event(new WorkOrderCreated" app/Observers/
# Expected: found

# 5. Tests pass
php artisan test
# Expected: 320+ passing

# 6. Frontend tests
npm run test
# Expected: 30+ passing
```

**Commit strategy:**
```bash
git commit -m "phase-1(d18): add 5 critical unit tests (PricingHelper, InventoryService, WorkOrderSuggestion, PaymentService)"
git commit -m "phase-1(d19): add WorkOrder events (Created, StatusChanged)"
git commit -m "phase-1(d20): add Payment + Invoice events (PaymentRecorded, InvoiceIssued)"
git commit -m "phase-1(d21): add Customer + Part + HR + Auth events (8 events)"
git commit -m "phase-1(d22): add 5 critical listeners (Log, Notify, UpdateStatus)"
git commit -m "phase-1(d23): create EventServiceProvider + register events"
git commit -m "phase-1(d24): create WorkOrderObserver + PaymentObserver (trigger events)"
git commit -m "phase-1(d25): refactor Payment::boot() - decouple via events"
```

---

# 🎯 معايير النجاح النهائية (Phase 1)

```bash
# 1. Critical Fixes
./scripts/check-tenant-scope.sh
php artisan migrate  # على SQLite

# 2. FormRequests count
find app/Http/Requests -name "*.php" | wc -l
# Expected: 50+ (was 15)

# 3. Backend tests
php artisan test
# Expected: 320+ passing, 0 failing

# 4. Frontend tests
npm run test
# Expected: 30+ passing, 0 failing

# 5. Events + Listeners
echo "Events: $(find app/Events -name '*.php' | wc -l)"
echo "Listeners: $(find app/Listeners -name '*.php' | wc -l)"
# Expected: Events 10+, Listeners 10+

# 6. No inline validate() in major controllers
for f in app/Http/Controllers/App/CustomerController.php \
         app/Http/Controllers/App/WorkOrders/WorkOrderStatusController.php; do
    echo "$f: $(grep -c 'validate(' $f) inline validations"
done
# Expected: 0

# 7. Score estimate
# 68% (Phase 0) + 5% (Critical Fixes) + 2% (FormRequests) = 75%
```

**Final Score Target: 75/100** ⬆️

---

# 📤 التقرير النهائي

عند الانتهاء، أنشئ تقرير في `docs/audit/phase-1-completion.md`:

```markdown
# Phase 1 Completion Report

## Status: ✅ COMPLETE / ⚠️ PARTIAL / ❌ INCOMPLETE

## Summary
- [x] Fix #1 (SQLite migration): ✅
- [x] Fix #2 (tenant-scope script): ✅
- [x] Fix #3 (flaky tests): ✅/⚠️
- [x] FormRequests extracted: X files
- [x] Controllers refactored: X files
- [x] Frontend tests: X files
- [x] Unit tests: X files
- [x] Events created: X
- [x] Listeners created: X
- [x] EventServiceProvider registered: ✅
- [x] Observers triggering events: ✅

## Score: 75/100 (from 68)

## Commits
- abc123 phase-1(d1): ...
- def456 phase-1(d2): ...
- ...

## Remaining Issues
- (if any)

## Phase 2 Recommendations
- Wire up Payment Gateways
- ZATCA full integration
- ...
```

---

# 🛑 Stop Conditions

توقف فقط عند:
- ✅ Critical Fix #1, #2, #3 مكتملة
- ✅ 50+ FormRequest files
- ✅ 30+ frontend tests
- ✅ 5+ unit tests for critical services
- ✅ 10+ events + 10+ listeners
- ✅ EventServiceProvider registered
- ✅ Observers trigger events (no side effects in boot())
- ✅ كل الـ tests تمر (320+ backend, 30+ frontend)
- ✅ Score: 75/100
- ✅ التقرير النهائي مكتوب

لا تنتقل لـ Phase 2. أنا (Mavis) سأراجع عملك.

---

# 🤝 ملاحظات

- **لا تعدل الـ existing `PrintSettings` work** (يوجد تعديل سابق لم يلتزم)
- **لا تنشئ assets في `public/build/`** 
- استخدم `feature/audit-master-plan-zxz80` كـ base branch
- الـ branch الجديد يكون `feature/phase-1-foundation`
- يومياً commit، مع رسائل واضحة
- أنا (Mavis) أراجع بدقة — لا تتوقع أن أثق بدون أدلة
```

---

<deliver-assets>
<media type="file" src="/Users/ahmad/Desktop/Carag-V2-Phase-1-Implementation-Plan.md" caption="Phase 1 Implementation Plan — Critical Fixes + FormRequests + Frontend Tests + Events Foundation. من 68% إلى 75%." />
</deliver-assets>

## 📊 ملخص البرومنت

| العنصر | التفصيل |
|---|---|
| **الهدف** | 68% → **75%** |
| **المدة** | شهر 1 (4 أسابيع) |
| **المهام الرئيسية** | 4 |
| **الملفات المتوقعة** | 50+ |
| **الاختبارات المتوقعة** | 50+ جديدة (20 backend + 30 frontend) |
| **Commits المتوقعة** | 20+ |

## 🎯 المهام الـ 4

1. **Critical Fixes** (يوم 1-2) — SQLite migration + tenant-scope script + flaky tests
2. **FormRequests** (يوم 3-12) — 50+ FormRequest + 4 refactored Controllers
3. **Frontend Tests** (يوم 13-17) — 30+ tests (composables, plugins, components)
4. **Unit Tests + Events** (يوم 18-25) — 5+ unit tests + 10 events + 10 listeners

## 🛑 Stop عند

- ✅ كل المهام الـ 4 مكتملة
- ✅ كل الاختبارات تمر (320+ backend, 30+ frontend)
- ✅ Score 75/100
- ✅ تقرير Phase 1 مكتوب

جاهز يا أحمد! الصق البرومنت في agent منفصل، أو ابدأ بنفسك، وأنا جاهز أراجع بدقة 🎯