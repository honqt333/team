# 09 — تدقيق Backend وقاعدة البيانات (Backend & Database Audit)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط — **لم يُشغَّل أي migration ولم يُتصل بأي قاعدة بيانات**

> ⚠️ **تصحيحات — اقرأ `16_AUDIT_CORRECTIONS.md` أولاً.**
> - **§6 (`InvoiceObserver`) — مُغلق الآن.** المراقب مُسجَّل في `AppServiceProvider`، والاختبار لم يعد يُسجّله بنفسه.
> - **§7 (`floatDiffInDays`) — التصنيف مُصحَّح.** ليست Fatal error؛ هي **alias مدعوم في Carbon** عبر `__call`، تُرجع القيمة الصحيحة وتُصدر `E_USER_DEPRECATED` فقط. الشدة الصحيحة: **منخفض**، لا **عالٍ**.
> - **تكرار الضريبة — أُعيد تحديد نطاقه.** «9 نسخ» ليست 9 تنفيذات متكافئة؛ المساران المتبقيان هما المدخل والإشعار الدائن. التفاصيل في `15_TAX_REFERENCE_AND_PATHS.md`.
>
> الباقي من هذا التقرير كما هو.

---

## 1. الحكم العام

> **البنية الخلفية ناضجة ومهنية. المشكلات مركَّزة في: تكرار منطق الأعمال، وفجوات صغيرة في التسجيل.**

| المحور | التقييم | التفصيل |
|---|---|---|
| فصل المسؤوليات | ✅ جيد | 158 controller / 79 service / 55 FormRequest / 38 policy |
| التفويض | ✅ **ممتاز** | 419 مسار تعديل ⇒ كلها تفحص |
| عزل المستأجرين | ✅ **قوي** | 60 + 45 موديل + 5 ملفات اختبار عزل |
| سلامة البيانات | ⚠️ | 50 controller + 5 service بلا transaction |
| تكرار المنطق | 🔴 | 9 نسخ لحساب الضريبة · 6 تنفيذات للترقيم · 280 سطر مكرر |
| الأخطاء الساكنة | 🔴 | 20 خطأ PHPStan، منها **دالة غير موجودة** |
| المخطط | ✅ good | 140 جدول · قيود FK سليمة · فهارس على كل أعمدة FK |

---

## 2. طبقة الـ Controllers

### 2.1 الحجم

| المقياس | القيمة |
|---|---|
| إجمالي الـ controllers | 158 |
| الـ controllers التي تتجاوز 5,000 سطر | 24 |
| `ReportsController` | 🔴 **الأضخم** — 24 استدعاء `DB::raw` وحده |
| `AppLayout.vue` (واجهة) | 3,600+ سطر |

### 2.2 🔴 منطق أعمال داخل الـ controllers

**50 استدعاء `DB::table` / `DB::raw` في `app/Http/Controllers/`** — منها **12 مسار كتابة** (violations مباشرة)، و**24 في `ReportsController` وحده**.

| الملف | السطر | المخالفة |
|---|---|---|
| `PurchaseReturnsController.php` | 49 | إنشاء فاتورة مرتجع **خارج `InvoiceService`** |
| `PurchaseReturnsController.php` | 61-100 | **حلقة ضريبة مكتوبة يدوياً** في controller |
| `PurchasingInvoicesController.php` | 198 | إنشاء فاتورة شراء **خارج `InvoiceService`** |
| `PurchasingInvoicesController.php` | 215-282 | **حلقة ضريبة** في controller |
| `ReportsController.php` | 2978 | **حساب ضريبة** في controller |

### 2.3 ✅ ما يُنفَّذ بشكل صحيح

- **المخزون 100% داخل `InventoryService`** — 8 مواقع transaction. لا استدعاء `InventoryService` من controllers مباشرة للكتابة.
- **سياسة تجاوز المخزون السالب** تُفحص **داخل** الخدمة (`InventoryService.php:497`) لا في الـ controller — ✅ دفاع صحيح.
- `QuoteConversionService.php:106` يستخدم نفس الخدمة.

---

## 3. 🔴 أكبر مشكلة: تسعة تنفيذات لحساب الضريبة

> ⚠️ **تصحيح مُقاس (2026-11-11).** تعداد «تسعة تنفيذات» يخلط بين ثلاثة أشياء مختلفة، وقد ضخّم الأثر المالي. بعد تثبيت مرجع مستقل وقياس كل مسار فعلياً (`15_TAX_REFERENCE_AND_PATHS.md`):
>
> | المجموعة | العدد | الأثر المالي |
> |---|---|---|
> | **مسارات تُنتج مستنداً مالياً** (فاتورة · إشعار دائن) | **2** | **حقيقي** — تقريب لكل سطر بدل مرة واحدة، فروق موثّقة بالهللة |
> | **مسار أمر العمل** (`WorkOrder::recalculateTotals()`) | 1 | **صحيح حسابياً** — يجمع الصافي ثم يطبّق الضريبة مرة واحدة. عليه **فجوة نموذجية**: لقطة ضريبة واحدة للأمر كله، فلا يمكن خلط سطر معفى بسطر خاضع، ولا سطر شامل بسطر غير شامل |
> | **حسابات عرض في الواجهة** (≈19 ملف Vue/JS) | ~19 | **لا أثر مالي** — تحسب ما يراه المستخدم في نموذج قبل وجود المستند. القيمة المحفوظة هي التي يحسبها الخادم |
>
> ⇒ الخلل الحقيقي القابل للقياس = **مساران** يقربان لكل سطر. مسار أمر العمل **يجب حفظه** لا إعادة كتابته، وفجوة المعفى لكل سطر قرار منفصل. تفصيل القياس: `15_TAX_REFERENCE_AND_PATHS.md` §3 · التصحيح الكامل: `16_AUDIT_CORRECTIONS.md` §5.
>
> التعداد أدناه يبقى كما هو توثيقاً توصّفياً، لكن **القراءة الصحيحة** هي ما سبق.

### 3.1 التفصيل

| # | الموقع | ملاحظات |
|---|---|---|
| 1 | **`app/Services/Optimization/TaxCalculator.php:24-62`** | 🟢 **المعتمد** — `calculateLine()` يُعيد قيماً **بدون تقريب** للتسوية على مستوى المستند (`:50-52`: *"We do NOT round here anymore… purely for display preview"*) |
| 2 | `app/Models/WorkOrder.php:115` | ✅ **الاستدعاء الفعلي الوحيد** — `app(TaxCalculator::class)->calculateLine(...)` |
| 3 | `app/Services/InvoiceService.php:145-152` | 🔴 **إعادة تنفيذ مع تقريب لكل سطر** — رغم أن `TaxCalculator` **مُحقَّنة** في `:20-22` |
| 4 | `app/Services/InvoiceService.php:197-201` | 🔴 نسخة ثانية |
| 5 | `app/Services/CreditNoteService.php:254-258` | 🔴 |
| 6 | `app/Services/CreditNoteService.php:312-316` | 🔴 |
| 7 | `app/Services/CreditNoteService.php:733` | 🔴 |
| 8 | `app/Models/QuoteLine.php:89-95` | 🔴 |
| 9 | `app/Models/WorkOrder.php:263` | 🔴 |
| 10 | `app/Http/Controllers/App/PurchaseReturnsController.php:69-73` | 🔴 **في controller** |
| 11 | `app/Http/Controllers/App/ReportsController.php:2978` | 🔴 **في controller** |

**⚠️ مُتحقَّق:** `grep -n "taxCalculator->" app/Services/InvoiceService.php` → **فارغ**. الخدمة تحقن `TaxCalculator` عبر الـ constructor (`:22`) **ولا تستدعيها أبداً** — ثم تعيد كتابة الحساب يدوياً.

### 3.2 لماذا يهم — فرق التقريب

```php
// TaxCalculator::calculateLine() — لا تقريب (للتجميع على مستوى المستند)
'tax_amount' => round($taxAmount, 2),
'raw_tax_amount' => $taxAmount,        // ← القيمة الحقيقية

// InvoiceService.php:145-152 — تقريب لكل سطر
$lineIncl = round($net, 2);
$lineExcl = $taxRate > 0 ? round($net / (1 + ($taxRate / 100)), 2) : $net;
$taxAmt   = round($lineIncl - $lineExcl, 2);      // ← تقريب مستقل لكل سطر
```

**النتيجة:** لو كان `TaxCalculator` يجمع غير المقرَّب ثم يقرّب مرة واحدة، بينما `InvoiceService` يقرّب كل سطر — **فرق تراكمي** يظهر في:
- فاتورة بـ 30 سطراً ⇒ فرق يصل ربع ريال أو أكثر
- **مرتجع** ⇒ فرق مضاعف
- `credit_note` ⇒ فرق ثالث

**⇒ الفاتورة المعروضة قد لا تطابق الإجمالي المحفوظ، ولا المرتجع.**

### 3.3 🔴 `CreditNoteService` — 280 سطر مكرر

`app/Services/CreditNoteService.php` (980 سطر):

| الدالة | الأسطر | المحتوى |
|---|---|---|
| `getReturnableSummary()` | 33-203 | — |
| `createCreditNote()` | 205-519 | المنطق |
| `getReturnableSummaryForInvoice()` | 521-685 | — |
| `createCreditNoteForInvoice()` | 687-979 | **نسخة** |

**القياس:** `diff <(sed -n '236,515p' …) <(sed -n '716,995p' …)` ⇒ **183 سطر اختلاف من 560 ⇒ ≈ 67% متطابق**.

**الفروق الفعلية:** اسم المتغيّر (`itemId` ↔ `lineId`)، مصدر الجلب (`work_order_item_id` ↔ `id`)، رسالة الخطأ، **وسطر مفقود**: النسخة الأولى فيها `$pricingMode === 'inclusive'` والضريبة على `lineIncl`، والثانية **تفترض exclusive دائماً** ⇒ **اختلاف سلوكي حقيقي** بين مرتجع كرت عمل ومرتجع فاتورة.

**ونصوص عربية مثبتة داخل الخدمة:** `'مرتجع خدمة: '.$item->title` و`'مرتجع: '.$line->description`.

---

## 4. 🔴 توليد أرقام المستندات — 6 تنفيذات

| التنفيذ | القفل | قابل للتهيئة | الحالة |
|---|---|---|---|
| `app/Models/WorkOrder.php:176-222` | `lockForUpdate` على صفوف موجودة | ✅ | ⚠️ **القفل غير فعّال** |
| `app/Models/Quote.php:130-168` | نفس العيب | ✅ | ⚠️ **القفل غير فعّال** |
| `app/Services/InvoiceService.php:267` | ✅ `CenterSequence` | ✅ | ✅ |
| `app/Services/CreditNoteService.php:373, 839` | ✅ `CenterSequence` | ✅ | ✅ |
| `app/Models/PurchaseOrder.php:150-166` | ❌ | ❌ | 🔴 |
| `app/Models/PurchaseInvoice.php:68-85` | ❌ | ❌ | 🔴 |
| `app/Models/GoodsReceivedNote.php:119-136` | ❌ | ❌ | 🔴 |
| `app/Models/PurchaseReturnInvoice.php:55-71` | ❌ | ❌ | 🔴 |
| `app/Models/Billing/SubscriptionInvoice.php:112-117` | ❌ | ❌ | 🔴 |

### 4.1 لماذا `lockForUpdate` على صفوف موجودة غير كافٍ

```php
// النمط في WorkOrder/Quote:
$max = Model::where('tenant_id', ...)->max('code');   // ← يقرأ
$code = $max + 1;                                      // ← يحسب
Model::create(['code' => $code]);                      // ← يكتب
```

`lockForUpdate` على **صفوف موجودة** يمنع تعديلها، لكنه **لا يمنع عمليتين من قراءة نفس `max` واحتساب نفس `max+1`**. القفل على صف غير موجود لا ينشئ شيئاً.

**الحل الصحيح:** صف تسلسل واحد مع `upsert` + `lockForUpdate` — وهو **نمط `CenterSequence` الموجود فعلاً** ✅.

**⇒ الحل مُتاح داخل المشروع؛ المشكلة أن 5 تنفيذات لا تستخدمه.**

### 4.2 ✅ القيد الفريد موجود (تصحيح)

- `quotes.code` ⇒ `unique(['tenant_id','center_id','code'])` في `database/migrations/2026_01_15_154700_fix_quotes_code_unique_constraint.php:20` — **موجود**.
- `center_sequences` ⇒ `unique(['tenant_id','center_id','type','year'], 'center_seq_unique')` في `2025_12_29_142537_create_center_sequences_table.php:22` — **موجود**.
- `invoices` ⇒ `unique(['tenant_id','center_id','invoice_number'])` في `2025_12_29_141556…:72`.
- `purchase_invoices.code` ⇒ كان `unique()` عالمياً (تعارض بين المستأجرين!) ⇒ **أُصلح** في `2026_07_09_133855_fix_purchase_invoices_unique_code.php:16` إلى `unique(['tenant_id','code'])`.

**⇒ القواعد الفريدة تحمي من التكرار لكن لا تمنع فجوة الأرقام (gaps) — والفشل عند Constraint violation يظهر كخطأ 500 لا كـ retry.**

---

## 5. 🔴 ثلاث قواعد محاسبية متعارضة لحالة الفاتورة

| # | التنفيذ | القاعدة |
|---|---|---|
| 1 | `app/Models/Invoice.php:175-224` (`updatePaymentStatus()`) | 🔴 **يعدّ `bad_debt` كمدفوع** |
| 2 | `app/Services/Payment/PaymentService.php:119-152` (`recalculatePurchaseInvoice()`) | 🔴 **لا يعدّ `bad_debt`** |
| 3 | `app/Services/Purchasing/PurchasingService.php:549` | 🔴 **نسخة ثالثة** |

**⇒ نفس الجدول، قاعدتان مختلفتان.** فاتورة شراء لها `bad_debt` ستُعرض «مدفوعة» في مكان و«غير مدفوعة» في آخر.

> **مُتحقَّق**: الاختبار `FinancialSafetyAuditAcceptanceTest` يغطي «payments report handles bad debt kpi» — أي أن السلوك **مقصود** في تقرير المدفوعات، لكن **غير متّسق** مع الفاتورة.

---

## 6. 🔴 `InvoiceObserver` غير مُسجَّل (EV-01)

**الأدلة:**
```
app/Providers/AppServiceProvider.php:248-252
    Employee::observe(EmployeeObserver::class);
    Center::observe(CenterObserver::class);
    WorkOrder::observe(WorkOrderObserver::class);
    WorkOrderItem::observe(WorkOrderItemObserver::class);
    Payment::observe(PaymentObserver::class);
    ← InvoiceObserver غائب
```

```
grep -rn "InvoiceObserver" app/Providers/ bootstrap/   →  (فارغ)
grep -rn "InvoiceObserver" app/ tests/
    app/Observers/InvoiceObserver.php:10:  class InvoiceObserver
    tests/Feature/InvoicingSecurityTest.php:27:  Invoice::observe(InvoiceObserver::class);
```

**الأثر:** حارس ثبات فاتورة ZATCA (رفض تعديل/حذف فاتورة غير مسودة) **معطّل في الإنتاج**.

**المصيدة:** الاختبار يُسجّل المراقب بنفسه ⇒ يحمي نفسه لا يحمي الإنتاج.

---

## 7. 🔴 `floatDiffInDays()` — دالة غير موجودة (EV-02)

```php
// app/Models/Billing/Subscription.php:125
return $end ? max(0, (int) ceil($end->floatDiffInDays(now(), true) > 0 && $end->isFuture()
        ? now()->floatDiffInDays($end) : 0)) : 0;

// app/Services/Billing/SubscriptionLifecycleService.php:331
return (int) ceil($from->floatDiffInDays($to));
```

**الأمر:** `php -r "method_exists('Carbon\Carbon','floatDiffInDays')"` → **`DOES NOT EXIST`**

**التصنيف الدقيق — `deprecation notice` وليس `critical` (مُصحَّح):**
- مسار `middleware` **ملفوف بـ `rescue()`** (`HandleInertiaRequests.php:151-163`) — لكن بما أن الاستدعاء **لا يرمي**، فلا فشل صامت ولا اختفاء للحقل.
- accesssor `getDaysRemainingAttribute()` **غير مستدعى من أي كود إنتاجي** (تحققت: `grep -rn "->days_remaining" app/ resources/views/` → فارغ).
- ~~أول استدعاء|accessor = **Fatal error** في طلب غير ملفوف.~~ **مُصحَّح**: هذا التصنيف **خاطئ**. `floatDiffInDays` هو **alias مدعوم في Carbon** يمرّ عبر `__call` (`vendor/nesbot/carbon/src/Carbon/Traits/Date.php:2731` → `callDiffAlias()` في `:2878-2908`)، تُرجع **القيمة الصحيحة** وتُصدر `E_USER_DEPRECATED` (16384) فقط. لا انهيار ولا فقدان بيانات. الشدة الصحيحة: **منخفض**. التفاصيل والأدلة في `16_AUDIT_CORRECTIONS.md` §1.

**الإصلاح:** `floatDiffInDays` ⇒ `$from->diffInDays($to, true)` أو `diffInDays` مع `float` صريح.

---

## 8. 🔴 معاملات DB غير مُغلَّفة

| المستوى | العدد | المخاطرة |
|---|---|---|
| Controllers بـ ≥3 عمليات كتابة بلا transaction | **50** | عالية |
| Services بـ ≥3 عمليات كتابة بلا transaction | **5** | عالية |

### الأخطر

| الملف | الأسطر | المخاطرة |
|---|---|---|
| `app/Services/ZatcaOnboardingService.php` | 494-592 | 🔴 **6 عمليات حفظ متتابعة بلا transaction** — تفعيل CSID في ZATCA. فشل midway ⇒ حالة CSUID نصف مُفعَّلة |
| `app/Http/Controllers/App/EmployeePortalController.php` | 438-511 | 🔴 كتابة على 3 جداول بلا transaction |
| `app/Http/Controllers/App/HR/PayrollController.php` | 180-216 | 🔴 **`lockForUpdate` بلا `DB::transaction`** ⇒ القفل يُحرَّر بعد كل عبارة ⇒ **لا يحمي** |

> **نمط `lockForUpdate` بلا `transaction` هو خطأ دلالي**: القفل في MySQL حالة للجلسة/المعاملة، وبلا transaction يُحرَّر فوراً بعد `SELECT`.

### 8.1 ✅ ما يُنفَّذ بشكل صحيح

**الخدمات الـ14 الأساسية كلها transactional**، ومخزون `InventoryService` محمي بـ **8 مواقع** (`113, 196, 275, 361, 581, 641`).

---

## 9. الفهارس والقيود

### 9.1 ✅ كل أعمدة FK مفهرسة

**مُتحقَّق:** كل عمود مفتاح أجنبي إمّا `->constrained()` (يفهرسه MySQL تلقائياً) أو فهرس صريح. **لا يوجد عمود FK بلا فهرس.**

### 9.2 الفجوات الحقيقية

| الفجوة | الدليل | الأثر |
|---|---|---|
| `invoices.invoice_number` **غير مفهرس** | 5 تقارير تستخدم `LIKE 'prefix%'` | `ReportsController.php:480, 958, 1855, 1953, 2877` — مسح جدول كامل |
| `center_sequences.center_id` قابل لـNULL | `2026_07_09_165048_make_center_id_nullable_in_financial_tables.php:30,56` | ⚠️ القيد `unique(['tenant_id','center_id','type','year'])` مع NULL ⇒ **MySQL يعامل NULL كقيمة مميزة** ⇒ **تكرار ممكن!** |

> 🔴 **هذه نقطة دقيقة مهمة**: في MySQL، الـ `UNIQUE` لا يمنع تكرار الصفوف التي تحتوي NULL. لو `center_id` صار NULL، يمكن تسجيل **سلسلتين لنفس (tenant, type, year)** ⇒ نفس الرقم مرتين.

### 9.3 ⚠️ ملاحظة على `database/migrations/backups/`

يوجد مجلد `backups/` فيه نسخ من ملفات migration. `audit_dup3.py` كشف تطابقاً تاماً (159 سطر) بين `2026_01_02_184931_create_hr_tables.php` ونسخته في `backups/`.

**⇒ هذه الملفات قد تُنفَّذ مرتين** إن لم تكن مستثناة. **لم أتحقق** من mechanism الاستثناء — يستحق فحصاً.

---

## 10. عزل المستأجرين

### 10.1 ✅ التقييم

| الآلية | التغطية |
|---|---|
| `TenantScoped` | 60 موديل |
| `CenterScoped` | 45 موديل |
| `TenancyContext::tenantId()` | يرجع `null` صراحةً على `system/*` (`TenancyContext.php:23-25`) |
| اختبارات العزل | 5 ملفات في `tests/Feature/TenantIsolation/` |
| اختبار تلقائي | `TenantContextAutoFillTest` |

### 10.2 🔴 الفتح عند غياب السياق

```php
// app/Models/Concerns/TenantScoped.php:21-28
$tenantId = TenancyContext::tenantId();
if ($tenantId !== null) {                    // ← إن كان null: لا فلتر إطلاقاً
    $builder->where($table.'.tenant_id', $tenantId);
}
```

```php
// app/Models/Concerns/CenterScoped.php:23-26
if ($tenantId !== null && $centerId !== null) {
    // ... only here
}
// ← خارج الشرط: لا فيلاتر ولا شيء
```

**⇒ fail-open**: في worker الطابور، أو أمر console، أو مسار `system/*` — **كل استعلام على موديل مُبعَّد بلا ح**.

### 10.3 🟡 النقطة الوحيدة التي وجدتها

`app/Jobs/SendAppointmentMessage.php:147`:
```php
$delivery = AppointmentMessageDelivery::query()->find($this->deliveryId);
```

**الموديل نفسه يوثّق العقد:**
```php
// app/Models/AppointmentMessageDelivery.php:52-58
// NOTE: deliberately NOT using TenantScoped here.
// ... Every query in this codebase MUST filter on
// tenant_id explicitly.
```

**و`scopeForTenant` موجود** (`:156`) لكن **غير مستخدم في الـ job**.

**تقييم المخاطرة — الدقيق:**
- `deliveryId` **لا يُحقن من المستخدم** — يُمرَّر من: controller (بعد جلب مُقيَّد بالمستأجر) · dispatcher · أمر console للاستعادة.
- ⇒ **ليس استغلالاً عبر المستأجرين**، بل **فجوة دفاع عميق (defense-in-depth)**.
- 🔴 لكن: `AppointmentMessageDelivery` **لا يحمل `TenantScoped`** ⇒ أي استعلام غير مُقيَّد عليه يُعيد سجلات كل المستأجرين.

**التوصية:** `AppointmentMessageDelivery::query()->where('tenant_id', $delivery->tenant_id)->findOrFail(...)` — لكن يلزم معرفة المستأجر قبل الجلب ⇒ الحل: تمرير `tenantId` في الـ job، أو استخدام `scopeForTenant` بعد جلب `delivery_id` من جدول غير مبعَّد.

**لم يتم التحقق**: هل `SendAppointmentMessage` يُنفَّذ قبل ضبط `TenancyContext` في الـ worker — يحتاج فحص `app/Jobs` كاملاً (ملف واحد فقط) — **لم يُغلق**.

---

## 11. الأخطاء الساكنة — PHPStan (20 خطأ)

| الملف | العدد | الطبيعة | الخطورة |
|---|---|---|---|
| `app/Models/Billing/Subscription.php` | 2 | 🔴 **`floatDiffInDays()` غير موجودة** | عالية |
| `app/Services/Billing/SubscriptionLifecycleService.php` | 1 | 🔴 **`floatDiffInDays()` غير موجودة** | عالية |
| `app/Http/Controllers/App/HR/AttendanceController.php` | 6 | تعارض نوع `Collection` في `when()` | متوسطة |
| `app/Services/Media/ImageOptimizer.php` | 6 | شروط دائماً خاطئة · `is_object(GdImage)` دائماً صحيح | متوسطة |
| `app/Http/Controllers/App/ZatcaOnboardingController.php` | 1 | ignore pattern لم يُطابق ⇒ baseline قديم | منخفضة |
| `app/Http/Middleware/HandleInertiaRequests.php` | 1 | `?->` على يسار `??` | منخفضة |
| `app/Services/TenantStorageService.php` | 1 | `?->` على يسار `??` | منخفضة |

**ملاحظة:** `phpstan-baseline.neon` (63KB) يحتوي استثناءات كثيرة — بعضها لم يعد يُطابق (نمط ignore غير مُطابق = **خطأ جديد مُغطّى**). الـ baseline **يحتاج إعادة توليد** بعد الإصلاح.

---

## 12. 🔴 الجولة 1 — إصلاحات Backend (الأولوية)

| # | الملف | التغيير | المخاطرة | الجهد |
|---|---|---|---|---|
| 1 | `app/Providers/AppServiceProvider.php` | `Invoice::observe(InvoiceObserver::class);` | **صفر** | 1 سطر |
| 2 | `tests/Feature/InvoicingSecurityTest.php` | **إزالة** `Invoice::observe(...)` من `:27` | **صفر** | 1 سطر |
| 3 | `app/Models/Billing/Subscription.php` | `floatDiffInDays()` ⇒ `diffInDays(..., true)` | **صفر** | 5 دقائق |
| 4 | `app/Services/Billing/SubscriptionLifecycleService.php` | نفس الإصلاح | **صفر** | 5 دقائق |
| 5 | `app/Services/InvoiceService.php` | `:145-152` و`:197-201` ⇒ `taxCalculator->calculateLine(...)` | متوسط | 2-3 ساعات + اختبار |
| 6 | `app/Services/CreditNoteService.php` | استخراج `RefundCalculator` مشترك | متوسط | 3-4 أيام |
| 7 | `app/Services/ZatcaOnboardingService.php` | تغليف `:494-592` بـ `DB::transaction` | متوسط | ساعة |
| 8 | `app/Http/Controllers/App/HR/PayrollController.php` | تغليف `:180-216` بـ `DB::transaction` | متوسط | ساعة |
| 9 | 5 موديلات ترقيم | التحويل إلى `CenterSequence` | متوسط | يومان |
| 10 | `app/Models/Invoice.php` + `PaymentService.php` + `PurchasingService.php` | توحيد قاعدة `bad_debt` | 🔴 **عالٍ** | يوم + اختبار لكل transition |

---

## 13. التحقق من الأخطاء عبر الاختبارات

| القاعدة | الحالة |
|---|---|
| **كل route تعديل يفحص `authorize()`** | ✅ 419/419 |
| **حارس ثبات الفاتورة** | 🔴 **معطّل في الإنتاج** (الاختبار يقنّع نفسه) |
| **عزل المستأجرين** | ✅ 5 ملفات اختبار |
| **حساب الضريبة** | ⚠️ لا يوجد اختبار يقارن ناتج `TaxCalculator` بنسخ الـ inline |
| **ترقيم المستندات** | ✅ `WorkOrderCodeGenerationTest` يغطي التصادم — لكن `PurchaseOrder`/`GoodsReceivedNote` بلا اختبار مماثل |
| **حالة الفاتورة** | ⚠️ `FinancialSafetyAuditAcceptanceTest` يغطي `bad_debt` في تقرير المدفوعات فقط |

---

## 14. حد أدنى مقترح للاختبارات

| الاختبار | الهدف |
|---|---|
| `InvoiceObserverRegistrationTest` | **أن** `InvoiceObserver` مُسجَّل في الإنتاج — يمنع تكرار EV-01 |
| `TaxCalculatorParityTest` | **أن** كل مسار حساب الضريبة يطابق `TaxCalculator` |
| `DocumentNumberingUniquenessTest` | **أن** كل نوع مستند ينتج أرقاماً فريدة عبر المستأجرين |
| `InvoiceStatusRuleConsistencyTest` | **أن** الفاتورة والتقرير والمشتريات تحسب `bad_debt` بنفس القاعدة |
| `TransactionBoundaryTest` | **أن** العمليات المتعددة الكتابة ملفوفة بـ transaction |
| `SnapshotImmutabilityTest` | **أن** تعديل بيانات المستأجر لا يغيّر فاتورة سابقة |

---

## 15. ملخص

| المحور | التقييم | الجملة القابلة للتنفيذ |
|---|---|---|
| فصل المسؤوليات | ✅ | 79 خدمة + 55 FormRequest |
| التفويض | ✅ ممتاز | 419 مسار — كلها تفحص |
| عزل المستأجرين | ✅ | fail-open في الطابور — انتبه |
| منطق الضريبة | 🔴 | 9 نسخ — `TaxCalculator` مُحقَّنة وغير مستدعاة |
| استنساخ الفواتير | ✅ ممتاز | `??=` + تجميد الفراغ + توريث |
| ترقيم المستندات | 🔴 | 6 تنفيذات — الحل موجود (`CenterSequence`) |
| المعاملات | 🔴 | 50 controller بلا transaction |
| أخطاء PHPStan | 🔴 | 20 — منها دالة غير موجودة |
| قيد `center_sequences` مع NULL | 🔴 | تكرار ممكن (MySQL + NULL) |
| `InvoiceObserver` | 🔴 | غير مُسجَّل — حارس ZATCA معطّل |
| المخطط | ✅ | 140 جدول — فهارس FK سليمة |
| الازدواج في التسجيل | ✅ مُصلَح | `withEvents(discover: false)` |
