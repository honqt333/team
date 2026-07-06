# 📋 Carag V2 — Code Quality & Security Audit Report

**نطاق الفحص:** PHP/Backend + Vue/Frontend + Security + Code Quality
**التاريخ:** 2026-07-06
**المراجع:** Mavis
**Commit baseline:** main (ahead of origin by 9 commits)
**المشروع:** Carag V2 — نظام ERP لورش السيارات (Laravel 12 + Vue 3 + Inertia.js)

---

## 📊 ملخص الأرقام

| المقياس | القيمة |
|---|---|
| PHP files | 308 (39,033 سطر) |
| Vue files | 282 (85,928 سطر) |
| JS files | 16 (1,367 سطر) |
| Controllers | 98 |
| Models | 107 (68 root + 39 sub) |
| Policies | 26 |
| Services | 24 |
| Actions | 3 |
| Form Requests | 13 |
| Middleware | 8 |
| Tests | 35 files (6,439 سطر في Feature) |
| Controllers using `authorize` | 33/98 (34%) ⚠️ |
| Models with `TenantScoped` | 10/65 (15%) ⚠️ |
| `v-html` usage | 27 ملف |
| DB::transaction usage | 6 (في Services) |
| Cache::remember usage | 6 |
| Queue jobs | 0 |

### أكبر 10 ملفات PHP

| الملف | الأسطر |
|---|---|
| `app/Http/Controllers/App/WorkOrderController.php` | **1862** |
| `app/Http/Controllers/App/QuoteController.php` | 979 |
| `app/Models/WorkOrder.php` | 742 |
| `app/Services/Purchasing/PurchasingService.php` | 551 |
| `app/Services/Inventory/InventoryService.php` | 550 |
| `app/Http/Controllers/App/PurchaseInvoicesController.php` | 523 |
| `app/Http/Controllers/App/HR/EmployeeController.php` | 500 |
| `app/Http/Controllers/App/CompanyProfileController.php` | 406 |
| `app/Support/Permissions.php` | 405 |
| `app/Http/Controllers/App/CustomerController.php` | 402 |

### أكبر 10 ملفات Vue

| الملف | الأسطر |
|---|---|
| `Pages/Public/LandingPreview.vue` | 2407 |
| `Pages/Settings/Company/Index.vue` | 2042 |
| `Pages/Services/Index.vue` | 1575 |
| `Pages/Purchasing/Invoices/Show.vue` | 1503 |
| `Pages/WorkOrders/Index.vue` | 1321 |
| `Pages/Customers/Show.vue` | 1246 |
| `Pages/WorkOrders/Show.vue` | 1076 |
| `Pages/Quotes/Index.vue` | 1016 |
| `Pages/Quotes/Show.vue` | 950 |

---

## 🔴 ثغرات أمنية حرجة (Priority: FIX NOW)

### 1. SQL Injection في `orderBy` — Inventory Modules

**الملفات:**
- `app/Http/Controllers/App/InventoryBalanceController.php` (سطر 55-84)
- `app/Http/Controllers/App/InventoryMoveController.php` (سطر 31-57)

```php
$sort = $request->input('sort', 'qty_on_hand');   // ❌ لا validation
$order = $request->input('order', 'desc');        // ❌ لا validation
// ...
$query->orderBy($sort, $order);                    // 🚨 حقن أي اسم عمود
```

**الخطورة:** مهاجم يمكنه استخراج بيانات (UNION attacks) أو كسر الـ query. الـ `$sort` يُمرّر مباشرة إلى SQL بدون escape أو whitelist.

**الإصلاح المقترح:**

```php
$allowedSorts = ['qty_on_hand', 'min_stock', 'sku', 'name', 'created_at'];
$sort = in_array($request->input('sort'), $allowedSorts) 
    ? $request->input('sort') 
    : 'qty_on_hand';

$order = strtolower($request->input('order')) === 'asc' ? 'asc' : 'desc';
```

---

### 2. `.env` مكشوف ويحتوي بيانات حساسة

**الملف:** `/Users/ahmad/Herd/carag-v2/.env`

| المتغير | القيمة | المشكلة |
|---|---|---|
| `APP_DEBUG` | `true` | 🔴 يكشف stack trace + DB queries في الأخطاء |
| `APP_KEY` | `base64:DpxLKA3YtEMy/SHJEW8pTagUAt31rlMCXRE7s7Y3zsk=` | 🔴 مفتاح التشفير الحقيقي في المستودع |
| `DB_PASSWORD` | (فاضي) | كلمة سر DB فارغة (development only) |
| `REDIS_PASSWORD` | `null` | Redis بدون auth |
| `LOG_LEVEL` | `debug` | Logging verbose (معلومات حساسة في الـ logs) |

**الإصلاح العاجل:**

```bash
# 1. فوراً: تحقق هل الـ .env في git history
git log --all --full-history -- .env

# 2. فوراً: ولّد APP_KEY جديد
php artisan key:generate

# 3. تأكد .env في .gitignore
grep "^\.env$" .gitignore

# 4. في production: APP_DEBUG=false, LOG_LEVEL=info
```

> إذا كان `.env` في git history، يجب استخدام `git filter-repo` لتنظيفه + rotate جميع الـ secrets فوراً.

---

### 3. XSS via `v-html` في Pagination Labels

**الملفات المتأثرة (11 ملف):**
- `resources/js/Pages/Settings/System/Index.vue:608`
- `resources/js/Pages/Settings/System/Sections/ColorsSection.vue:148`
- `resources/js/Pages/Settings/System/Sections/MakesSection.vue:140`
- `resources/js/Pages/Settings/Users/Index.vue:284`
- `resources/js/Pages/Invoices/Sales/Index.vue:141, 349`
- `resources/js/Pages/Invoices/Purchasing/Index.vue:340, 494`
- `resources/js/Pages/Purchasing/Sales/Index.vue:127, 378`
- `resources/js/Pages/Purchasing/Invoices/Index.vue:340, 496`

```vue
<!-- ❌ الكل يستخدم نفس النمط -->
<span v-html="link.label" />
```

**الخطورة:** Laravel pagination labels آمنة عادة، لكن أي custom pagination أو data-driven label يحقن HTML → XSS.

**الإصلاح:**

```vue
<!-- الخيار 1: استخدام Mustache interpolation (إذا label نص فقط) -->
<span>{{ link.label }}</span>

<!-- الخيار 2: sanitize مع DOMPurify (متوفر في المشروع) -->
<span v-html="DOMPurify.sanitize(link.label)" />
```

---

## 🟠 مشاكل أمنية متوسطة

### 4. `v-html` على QR Code بدون Sanitization

**الملف:** `resources/js/Pages/System/Security/TwoFactorSetup.vue:28`

```vue
<div v-html="qrCode" class="bg-white p-2 rounded-lg border"></div>
```

**التقييم:** `qrCode` يأتي من SVG من server (مكتبة `bacon/bacon-qr-code` موثوقة). **آمن حالياً** لكن إذا تم تعديله مستقبلاً لاحتواء user input → XSS.

**التوصية:** أضف sanitization دفاعية.

---

### 5. Tenant Isolation ضعيف

| الفئة | العدد | النسبة |
|---|---|---|
| Models تستخدم `TenantScoped` trait | 10/65 | 15% ⚠️ |
| Controllers تتعامل مع `tenant_id` | 52 | - |
| Controllers بدون `authorize()` منهم | 26 | 50% ⚠️ |

**المشكلة:** الـ isolation يعتمد على manual filters في queries. يكفي نسيان `where('tenant_id', ...)` في query واحد لتسريب بيانات بين المستأجرين.

**الـ Global Scope الحالي** (`app/Models/Concerns/TenantScoped.php`):

```php
static::addGlobalScope('tenant_scoped', function (Builder $builder) {
    $tenantId = TenancyContext::tenantId();
    if ($tenantId !== null) {
        $builder->where($table . '.tenant_id', $tenantId);
    }
});
```

**الإصلاح:** أضف `TenantScoped` trait لكل الـ models التي تحتوي `tenant_id`:
- `WorkOrder`, `WorkOrderItem`, `WorkOrderItemPart`
- `Customer`, `Vehicle`
- `Quote`, `QuoteLine`, `QuotePart`
- `Invoice`, `InvoiceLine`, `Payment`
- `PurchaseOrder`, `PurchaseInvoice`
- `InventoryBalance`, `InventoryMove`, `Part`
- إلخ.

---

### 6. `WorkOrder` Model — SQL مكوّن في String

**الملف:** `app/Models/WorkOrder.php:689`

```php
$query->whereRaw('(COALESCE((SELECT SUM((unit_price * qty) - discount_amount) 
                    FROM work_order_items WHERE work_order_id = work_orders.id), 0) 
                  + COALESCE((SELECT SUM((unit_price * qty) - discount) 
                    FROM work_order_item_parts WHERE work_order_id = work_orders.id), 0)) 
                > (COALESCE((SELECT SUM(CASE WHEN type IN ("payment", "Payment") 
                    THEN amount WHEN type IN ("refund", "Refund") 
                    THEN -amount ELSE 0 END) 
                    FROM payments WHERE work_order_id = work_orders.id), 0))');
```

**المشكلة:** use of `IN ("payment", "Payment")` مع string interpolation للثوابت. **آمن حالياً** (ثوابت hardcoded) لكنه قابل للكسر عند refactoring. صعب الصيانة.

**الإصلاح:** استخرج scope method:
```php
public function scopeHasOutstandingBalance(Builder $query): Builder
{
    return $query->whereRaw(/* ... استخدم placeholders ? ... */);
}
```

---

## 🟡 مشاكل جودة عالية (Code Quality)

### 7. Fat Controllers (God Objects)

| الملف | الأسطر | عدد الـ methods | الحالة |
|---|---|---|---|
| `WorkOrderController.php` | **1862** | 40 | 🚨 ضخم |
| `QuoteController.php` | 979 | ~20 | 🟠 كبير |
| `WorkOrder.php` (Model) | 742 | 50+ | 🟠 كبير |
| `PurchaseInvoicesController.php` | 523 | ~12 | 🟡 متوسط |
| `EmployeeController.php` | 500 | ~15 | 🟡 متوسط |
| `CustomerController.php` | 402 | ~10 | 🟢 مقبول |

**الإصلاح المقترح لـ `WorkOrderController`:**

```
app/Http/Controllers/App/WorkOrder/
├── WorkOrderController.php          # show, index, create, store, update, destroy
├── WorkOrderItemController.php      # addItem, updateItem, removeItem, updateItemStatus
├── WorkOrderPaymentController.php   # addPayment, refundPayment
├── WorkOrderStatusController.php    # startWork, putOnHold, resume, cancel, complete
├── WorkOrderAttachmentController.php # uploadPhotos, uploadAttachments, deletePhoto, deleteAttachment
└── WorkOrderPrintController.php     # printInvoice, printQuote
```

> ملاحظة: الـ `Show.vue` (893 سطر) **مقسم بالفعل** لـ 10 components + 3 composables (موثق في `AGENTS.md`). الـ Controller يحتاج نفس الشي.

---

### 8. N+1 Queries — Stock لا يزال موجوداً

**الملف:** `app/Http/Controllers/App/WorkOrderController.php:204-211`

```php
$customers = Customer::select("id", "name", "phone")->get();   // Query 1
$makes = VehicleMake::ordered()->get();                         // Query 2
$colors = \App\Models\VehicleColor::active()->ordered()->get(); // Query 3
$departments = \App\Models\Department::active()->ordered()->get(); // Query 4
$modelsByMake = \App\Models\VehicleModel::query()
    ->select('id', 'make_id', 'name_ar', 'name_en')
    ->get()                                                     // Query 5
    ->groupBy('make_id');
```

ليست N+1 فعلية (queries ثابتة بـ 5 فقط) لكن **تفتقد caching** للأشياء اللي نادراً تتغير.

**الإصلاح:**

```php
$formData = Cache::remember('work_order_form_data', 3600, function () {
    return [
        'customers' => Customer::select("id", "name", "phone")->get(),
        'makes' => VehicleMake::ordered()->get(),
        // ...
    ];
});
```

---

### 9. Magic Strings في Status (متفاوت)

**✅ إيجابي:** `WorkOrder::STATUS_*` و `WorkOrderItem::STATUS_*` ثوابت مستخدمة.

**❌ سلبي:** 24 موقع آخر ما زال يستخدم نصوص خام:

```php
// WorkOrderController.php:27-29
protected const OPEN_STATUSES = ['open', 'in_progress', 'draft', 'on_hold', 'ready_for_qc'];
protected const CLOSED_STATUSES = ['done', 'cancelled'];
protected const ACTIVE_STATUSES = ['open', 'in_progress', 'on_hold', 'ready_for_qc'];
```

يجب أن يكون:
```php
protected const OPEN_STATUSES = [
    self::STATUS_DRAFT, self::STATUS_OPEN, self::STATUS_IN_PROGRESS,
    self::STATUS_ON_HOLD, self::STATUS_READY_FOR_QC,
];
```

---

### 10. Models غير منظمة (Flat Structure)

| البنية | العدد |
|---|---|
| `app/Models/` (root) | 68 |
| `app/Models/HR/` | 17 |
| `app/Models/Billing/` | 9 |
| `app/Models/Integration/` | - |
| `app/Models/Concerns/` | 3 |
| **بدون تقسيم لـ WorkOrder/Inventory/Customer** | - |

**الإصلاح المقترح:**

```
app/Models/
├── Core/         # User, Tenant, Center, Customer, Vehicle
├── WorkOrder/    # WorkOrder, WorkOrderItem, WorkOrderItemPart, WorkOrderPhoto, WorkOrderAttachment
├── Inventory/    # Part, Warehouse, InventoryBalance, InventoryMove, InventoryCategory
├── Purchasing/   # PurchaseOrder, PurchaseInvoice, Supplier
├── Billing/      # Invoice, InvoiceLine, Payment, Quote, QuoteLine, QuotePart
├── HR/           # ✓ موجود
├── Concerns/     # ✓ موجود
└── System/       # Settings, Logs, Notifications
```

---

### 11. Frontend: 141 `console.log` في Production Code

**الملف المتأثر الأكبر:** `resources/js/Components/Customers/CustomerFormModal.vue` (20+ console statements)

```javascript
console.log('[CustomerFormModal] Falling back to IP-based geolocation...');
console.log('[CustomerFormModal] IP Geolocation success:', lat, lng);
// ...
```

**الإصلاح:** استخدم logger abstraction أو `if (import.meta.env.DEV)` guard:
```javascript
if (import.meta.env.DEV) console.log(...);
```

---

### 12. قلة Rate Limiting على Web Routes

| Route | Rate Limit |
|---|---|
| `auth.php` (login) | `throttle:6,1` ✅ |
| `api.php` (attendance) | `throttle:30,1` ✅ |
| `api.php` (biometric batch) | `throttle:10,1` ✅ |
| **Web routes (login, password reset)** | ❌ لا rate limit |
| **API endpoints العامة** | ❌ غير مغطى |

**الإصلاح:** أضف rate limiting للـ login web route و password reset.

---

## 🟢 ملاحظات إيجابية (Good Patterns)

### ✅ أنماط ممتازة

| الميزة | الحالة |
|---|---|
| `DB::transaction` في Services الحرجة | ✅ Payment, Purchasing, QuoteConversion |
| `bcmath` للـ tax calculations | ✅ يتجنب floating point errors |
| `BCRYPT_ROUNDS=12` | ✅ إعدادات أمان قوية |
| Sanctum + 2FA | ✅ multi-layer authentication |
| `authorizesRequests` trait | ✅ موزع على الـ controllers |
| Policies (26) | ✅ authorization methods معرّفة |
| Spatie Permissions | ✅ role-based access |
| Status constants في Models | ✅ `STATUS_*` patterns |
| `convertArabicNumerals` middleware | ✅ معالجة أرقام عربية |
| CenterScoped trait | ✅ عزل حسب المركز |
| Form Requests (13) | ✅ validation centralized |
| `bcadd`, `bcdiv` | ✅ دقة حسابية عالية |

### ✅ ما لم أجده (الأخبار الجيدة)

- ✅ لا `dd()`, `dump()`, `var_dump` في production code
- ✅ لا `$request->all()` يُمرّر مباشرة لـ `create()`/`update()` (ماعدا logging)
- ✅ لا `Model::unguard()` — mass assignment محمي
- ✅ لا CSRF bypasses (`WithoutMiddleware` / `except` على CSRF)
- ✅ لا `file_get_contents` مع user input

---

## 📝 Tests Coverage

| المقياس | القيمة |
|---|---|
| Test files | 35 |
| Feature tests | 6439 سطر (أكبر test: 1332 سطر) |
| Unit tests | 3 (CreateWorkOrderAction, UpdateWorkOrderAction) |
| WorkOrder tests | 4 files (CRUD, Inventory, Code Generation) |
| Payment tests | 2 files (CrossSync, AutoInvoiceOnFullPayment) |
| **Security tests** | **0** ⚠️ |
| **SQL injection regression tests** | **0** ⚠️ |

**الإصلاح:** أضف security regression tests:
- اختبارات لـ SQL injection في `?sort=` و `?order=`
- اختبارات لـ Cross-tenant data access
- اختبارات لـ authorization (Role-based)

---

## 🎯 أولويات الإصلاح (Top 8)

| # | المهمة | الجهد | الأثر | الأولوية |
|---|---|---|---|---|
| 1 | Fix SQL injection في Inventory (2 controllers) | 30 دقيقة | 🔴 حرج | P0 |
| 2 | Rotate `APP_KEY` + تأكد `.env` غير tracked | 10 دقائق | 🔴 حرج | P0 |
| 3 | استبدل `v-html` بـ `{{ }}` أو `DOMPurify` في pagination | ساعة | 🟠 عالي | P1 |
| 4 | أضف `authorize` لـ 26 controller مفقود | 4 ساعات | 🟠 عالي | P1 |
| 5 | وسّع `TenantScoped` ليشمل كل الـ tenant models | 6 ساعات | 🟠 عالي | P1 |
| 6 | قسّم `WorkOrderController` لـ sub-controllers | 6 ساعات | 🟡 متوسط | P2 |
| 7 | استبدل magic strings بـ `STATUS_*` constants | 2 ساعة | 🟡 متوسط | P2 |
| 8 | أضف caching لـ dropdowns في index pages | 2 ساعة | 🟢 تحسن أداء | P3 |

---

## 📋 مقارنة مع التقرير السابق (2026-05-31)

| الملاحظة | الحالة السابقة | الحالة الحالية |
|---|---|---|
| N+1 queries | 🟡 يحتاج عمل | 🟡 لم يُحل (queries ثابتة، بقيت بدون cache) |
| Fat WorkOrderController | 🟡 يحتاج عمل | 🟡 لم يُقسّم (1862 سطر) |
| Missing database indexes | 🟡 يحتاج عمل | ⚠️ لم يتم التحقق |
| Business logic duplication | 🟡 يحتاج عمل | 🟡 مازال موجود |
| Test coverage ~15% | 🟡 يحتاج عمل | 🟢 تحسن (35 test files) |
| API documentation | 🟢 مستقبلاً | 🟢 لم يُنشأ |
| Cache layer | 🟢 مستقبلاً | 🟢 لم يُضف |

**الخلاصة:** **3 ملاحظات حرجة جديدة** (SQL injection, APP_KEY exposure, XSS) ظهرت في هذا الفحص لم تكن في التقرير السابق. الـ backlog القديم لا يزال valid. التحسن الوحيد الملحوظ هو زيادة عدد الـ tests.

---

## 🔍 منهجية الفحص

1. **Static analysis:** قراءة 308 ملف PHP + 282 ملف Vue بدون تنفيذ
2. **Pattern detection:** البحث عن anti-patterns معروفة (SQL injection, XSS, mass assignment)
3. **Security checks:** التحقق من `.env`, CSRF, authorization, file uploads
4. **Code quality:** تحليل حجم الـ controllers, magic strings, N+1 queries
5. **Cross-referencing:** مقارنة مع تقرير `REVIEW-REPORT.md` (2026-05-31)

---

## 📎 الملاحق

### الملحق A: تفاصيل SQL Injection

```php
// InventoryBalanceController.php:55-85
$sort = $request->input('sort', 'qty_on_hand');    // user-controlled
$order = $request->input('order', 'desc');         // user-controlled

if ($sort === 'sku') {
    $query->join('parts', 'inventory_balances.part_id', '=', 'parts.id')
          ->orderBy('parts.sku', $order)            // ⚠️ $order not validated
          ->select('inventory_balances.*');
} elseif ($sort === 'name') {
    $query->join('parts', 'inventory_balances.part_id', '=', 'parts.id')
          ->orderBy(/* locale-based */, $order)     // ⚠️ $order not validated
          ->select('inventory_balances.*');
} else {
    $query->orderBy($sort, $order);                // 🚨 $sort not validated
}
```

### الملحق B: Controllers بدون `authorize`

Controllers مع `tenant_id` references لكن بدون `$this->authorize()`:
- (مكتشف 26 ملف — يحتاج فحص يدوي للقائمة الكاملة)

### الملحق C: Models بدون `TenantScoped`

Models مع `tenant_id` عمود لكن بدون `TenantScoped` trait:
- `WorkOrder`, `WorkOrderItem`, `WorkOrderItemPart`, `WorkOrderPhoto`
- `WorkOrderAttachment`, `WorkOrderInspection`, `WorkOrderDamageMark`
- `Customer`, `Vehicle`
- `Quote`, `QuoteLine`, `QuotePart`
- `Invoice`, `InvoiceLine`, `Payment`
- `PurchaseOrder`, `PurchaseInvoice`, `PurchaseInvoiceLine`
- `InventoryBalance`, `InventoryMove`, `Part`
- `GoodsReceivedNote`, `GrnItem`
- `InventoryTransfer`, `InventoryTransferItem`
- `Payment`, `CompanyTransaction`
- إلخ (~50 model)

---

**تقرير كامل — جاهز للمراجعة. للتطبيق، ابدأ بـ P0 (البنود 1-2) فوراً.**

*تم إنشاء هذا التقرير بواسطة Mavis*
*التاريخ: 2026-07-06*
