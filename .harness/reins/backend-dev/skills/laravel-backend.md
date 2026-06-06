# Skill: Laravel Backend - Carag V2

دليل سريع للقواعد الصارمة في backend Carag V2. كل تغيير في الـ backend **لازم** يلتزم بها.

## المشروع

- **Framework:** Laravel 12 (PHP 8.2+)
- **Database:** MySQL 8.0+ (multi-tenant)
- **Queue:** Redis
- **Auth:** Laravel Sanctum + Google 2FA
- **Frontend bridge:** Inertia.js (Backend يقدّم Props فقط، الـ markup من frontend)

## القاعدة رقم 1: Multi-tenant Scoping

**كل** query على tenant-scoped model **لازم** يحتوي `tenant_id` و `center_id`.

```php
// ❌ غلط
$workOrders = WorkOrder::all();

// ✅ صح
$workOrders = WorkOrder::where('tenant_id', $user->tenant_id)
    ->where('center_id', $user->center_id)
    ->get();

// ✅✅ أفضل: scope على الـ Model
// في WorkOrder model
public function newQuery()
{
    return parent::newQuery()
        ->where('tenant_id', auth()->user()?->tenant_id)
        ->where('center_id', auth()->user()?->center_id);
}
```

## القاعدة رقم 2: Authorization

كل controller method لازم عنده authorization check.

```php
public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder)
{
    $this->authorize('update', $workOrder); // Policy
    // ...
}
```

## القاعدة رقم 3: Action Pattern للكتابة المعقدة

**لا** تكتب logic كبير داخل controller. استخرجها:

```php
// app/Actions/WorkOrder/CloseWorkOrderAction.php
namespace App\Actions\WorkOrder;

class CloseWorkOrderAction
{
    public function execute(WorkOrder $workOrder, User $actor): WorkOrder
    {
        $workOrder->update(['status' => 'closed', 'closed_at' => now()]);
        $workOrder->logActivity('closed', "By user {$actor->id}");
        return $workOrder;
    }
}
```

## القاعدة رقم 4: Service Pattern للـ Orchestration

Actions = خطوة واحدة. Services = orchestrate multiple actions.

```php
// app/Services/WorkOrderService.php
class WorkOrderService
{
    public function __construct(
        private CreateWorkOrderAction $create,
        private CloseWorkOrderAction $close,
    ) {}

    public function createWithItems(User $user, array $data): WorkOrder
    {
        $workOrder = $this->create->execute($user, $data);
        // ... orchestrate more
        return $workOrder;
    }
}
```

## القاعدة رقم 5: Form Request لكل Validation

```php
class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
        ];
    }
}
```

## القاعدة رقم 6: Eager Loading (لا N+1)

```php
// ❌ N+1
$workOrders = WorkOrder::all();
foreach ($workOrders as $wo) {
    echo $wo->customer->name; // query per iteration
}

// ✅ Eager load
$workOrders = WorkOrder::with(['customer', 'items'])->get();
```

## القاعدة رقم 7: Migrations آمنة

```php
// ❌ قد تفقد بيانات
Schema::table('work_orders', function (Blueprint $t) {
    $t->dropColumn('status');
});

// ✅ آمنة — أضف قبل ما تحذف
Schema::table('work_orders', function (Blueprint $t) {
    $t->string('status_v2')->nullable()->after('status');
});
// في migration ثانية بعد ما ينشر الكود
```

## القاعدة رقم 8: الـ Print Engine موحّد

```php
// الـ invoice print يستخدم template موحد
return Inertia::render('Invoices/Print/Standard', [
    'invoice' => $invoice->load('lines'),
    'company' => $company,
]);
```

## القاعدة رقم 9: ZATCA Compliance

- QR code على كل فاتورة (TLV format)
- Hash + Counter + Signature مخزّنة في `invoices.zatca_*`
- لا تعدّل schema ZATCA بدون migration مرافقة

## الوحدات المعروفة (للمرجع السريع)

| Module | Models | Controllers |
|---|---|---|
| Customers | `Customer`, `Vehicle` | `CustomerController`, `VehicleController` |
| Work Orders | `WorkOrder`, `WorkOrderItem`, `WorkOrderPhoto` | `WorkOrderController` (1200+ سطر — يحتاج refactor) |
| Quotes | `Quote`, `QuoteLine` | `QuoteController`, `QuoteApprovalController` |
| Invoices | `Invoice`, `InvoiceLine`, `Payment` | `InvoicesController`, `PaymentsController` |
| Purchasing | `PurchaseOrder`, `Supplier` | `PurchaseOrdersController`, `GoodsReceivedNotesController` |
| Inventory | `Part`, `Warehouse`, `InventoryBalance` | `InventoryBalanceController`, `InventoryMoveController` |
| HR | `HR/Employee`, `HR/Attendance`, `HR/PayrollRun` | `HRController`, `PayrollController`, `AttendanceController` |

## Handoff

بعد ما تخلص backend، اكتب في `deliverable.md`:

```markdown
## Handoff to tester
- Files changed: `path:line`
- Routes affected: `POST /app/work-orders`
- Test scenarios:
  - Auth: admin can, employee cannot
  - Tenant: data from another tenant is hidden
  - Validation: missing items returns 422
- Manual test:
  1. Login as admin
  2. POST /app/work-orders with sample data
  3. Verify response
```

---

*Built for the full-stack team rollout — 2026-06-06*
