# 02 — الخريطة المعمارية (Architecture Map)

**المشروع:** `/Users/ahmad/Herd/carag-v2` — خدمة برو / Khidmh Pro
**التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

---

## 1. المكدّس التقني المُتحقَّق منه

| الطبقة | التقنية | الإصدار | الدليل |
|---|---|---|---|
| Backend | Laravel Framework | **12.70.1** | `php artisan --version` |
| Runtime | PHP | **8.5.0** | `php -v` (المشروع يشترط `^8.2`) |
| Frontend | Vue 3 | `^3.4.0` | `package.json:55` |
| Bridge | Inertia.js | `^2.0.0` | `package.json:30` |
| Build | Vite | `^7.0.7` | `package.json:53` |
| CSS | Tailwind CSS | `^3.2.1` | `package.json:53` |
| i18n | vue-i18n | `^10.0.8` | `package.json:60` |
| State | — | **لا يوجد Pinia/Vuex** | `package.json` لا يحتويهما |
| Auth | Laravel Sanctum + Spatie Permission | `^4.0` / `^6.24` | `composer.json:15,20` |
| 2FA | pragmarx/google2fa-laravel | `^2.3` | `composer.json:18` |
| PDF/Print | barryvdh/laravel-dompdf + `maatwebsite/excel` | `^3.1` | `composer.json:11,16` |
| ZATCA | `app/Services/Zatca` | — | هيكل مجلدات |
| Analysis | larastan/phpstan | `^3.0` / `^2.0` | `composer.json:26,30` |
| Frontend test | Vitest | `^2.1.5` | `package.json:54` |

### النتيجة المعمارية الحاسمة

**لا توجد مكتبة إدارة حالة (State Management).** لا Pinia ولا Vuex ولا composable store مركزي.

**الأثر:** حالة الخادم تأتي من Inertia props، والحالة المحلية عبر `ref`/`reactive` داخل كل مكوّن. هذا **يفسّر** الشكوى المتكررة من «تكرار منطق الجداول»: كل صفحة تبني حالتها بنفسها.

---

## 2. بنية الطبقات كما هي فعلاً

```
routes/web.php (1,515 سطر) + api.php + auth.php  →  741 مسار
        │
        ▼
app/Http/Middleware/  (EnsureTenantActive, EnsureSystemPermission, EnsureTwoFactorEnabled,
                       center.context, EnsureSystemAdmin, HandleInertiaRequests)
        │
        ▼
app/Http/Controllers/  (158 controller)
        │   ├─ 5,097 سطر في 24 controller
        │   └─ 76 استدعاء DB::table/DB::raw  ← منطق في الطبقة الخطأ
        ▼
app/Http/Requests/  (55 FormRequest — طبقة التحقق)
        │
        ▼
app/Services/  (79 service)  ──────► app/Actions/ (6)
        │                              app/Observers/ (6)
        │                              app/Events/ (12) → app/Listeners/ (9)
        │                              app/Jobs/ (1)
        ▼
app/Models/  (127 model)
        │   ├─ TenantScoped  → 60 موديل
        │   └─ CenterScoped  → 45 موديل
        ▼
140 جدول في MySQL / SQLite
```

### أرقام الطبقات

| الطبقة | العدد | ملاحظة |
|---|---|---|
| Controllers | 158 | منها 24 تتجاوز 5,000 سطر |
| Services | 79 | 14 خدمة أساسية، جميعها transaction |
| Form Requests | 55 | طبقة تحقق منفصلة — ✅ ممارسة جيدة |
| Policies | 38 | 27 مسجّلة صراحة + discovery |
| Models | 127 | |
| Exports / Imports | 9 / 2 | Excel |
| Enums | 2 | تغطية ضعيفة لـ enum |
| Exceptions | `app/Exceptions` + `app/Exceptions/AI` | |

---

## 3. خريطة المسارات → الـ Controllers

### المجموعات الرئيسية (من `routes/web.php`)

| السطر | المجموعة | الحماية | النطاق |
|---|---|---|---|
| `:222` | `/{tenant}/portal/*` | `auth` | بوابة العميل |
| `:370` | `app/my/*` | `role:employee` | بوابة الموظف |
| `:395` | `app/*` | `auth, verified, tenant.active, center.context, EnsureTwoFactorEnabled` | تطبيق المستأجر |
| `:1186` | `system/*` | `auth:web,admin, system.admin` | لوحة المنصة |

### توزيع المسارات حسب المجال

| المجال | عدد المسارات | Controllers | طبقة الخدمة |
|---|---|---|---|
| settings | 89 | 18 | `UpdateCompanyProfileSectionAction`, `ZatcaOnboardingService` |
| system-admin | 138 | 30 | `SubscriptionLifecycleService`, `TenantSuspensionService`, `PaymentManager`, `DunningService` |
| hr | 74 | 16 | **لا شيء — على مستوى الموديل فقط** |
| work-orders | 60 | 17 | `WorkOrderLaborService`, `Inventory/WorkOrderPartsService`, `CreateWorkOrderAction` |
| reports | 42+10 | 3 | `TaxPeriodService`, `FinancialSummaryReportService`, `CashRegisterReportService` |
| inventory | 32 | 5 | `Inventory/InventoryService` ✅ |
| purchasing | 30 | 5 | `Purchasing/PurchasingService` |
| subscriptions | 26 | 2 | `SubscriptionLifecycleService`, `PaymentManager` |
| public-portal | 23 | 7 | `CustomerPortalOtpService`, `CustomerPortalAvailabilityService` |
| permissions | 25 | 3 | Spatie teams |
| tenants | 21 | 5 | `TenantSuspensionService` |
| invoices | 21 | 4 | `InvoiceService`, `SubscriptionInvoiceService` |
| quotes | 20 | 5 | `QuoteConversionService` |
| technicians | 18 | 1 | — |
| customers | 18 | 4 | — |
| vehicles | 15 | 5 | — |
| services | 11 | 2 | — |
| appointments | 7 | 1 | — |
| notifications | 5 | 1 | `NotificationService` |

---

## 4. مسارات البيانات الفعلية (Data Paths)

### مسار A — إنشاء أمر عمل

```
UI: WorkOrderFormModal.vue (1,257 سطر)
  → Inertia POST /app/work-orders
  → WorkOrderController::store
      → $this->authorize('create', WorkOrder::class)     ← Server-side
      → CreateWorkOrderAction
          → DB::transaction
              → WorkOrder::create          (TenantScoped يملأ tenant_id تلقائياً)
              → WorkOrderItem::createMany
              → InventoryService::issueParts   ← الاستعلام + الخصم + السجل
              → WorkOrderStatusChanged event
                  → LogActivityOnStatusChange listener
      → InvoiceService (إن كان مدفوعاً)
```

### مسار B — الفاتورة والدفع

```
UI: PaymentFormModal.vue
  → POST /app/work-orders/{wo}/payments
  → PaymentController
      → authorize
      → DB::transaction
          → Payment::create
          → PaymentRecorded event
              → LinkPaymentToInvoice
              → UpdateInvoiceStatusOnPayment   ← قاعدة الحالة (نسخة من 3)
              → HandleAutoInvoiceOnDoneWorkOrder
          → Invoice::updatePaymentStatus()     ← قاعدة الحالة (نسخة من 3) ⚠️
```

### مسار C — تحويل عرض سعر إلى أمر عمل

```
QuoteConversionService
  → InventoryService (بالمستوى المنخفض)
  → InvoiceService (إن كان ضريبياً)
  → bypass محتمل لـ inventory.override_negative_stock عند :106
```

### مسار D — لوحة المنصة (system)

```
UI: Pages/System/*
  → /system/* (auth:web,admin, system.admin)
  → System Controllers (30)
  → Services: SubscriptionLifecycleService, TenantSuspensionService, PaymentManager, DunningService
  → AdminUser model (ليس User) ← فصل ذكي: مستأجر vs منصة
```

---

## 5. نظام الأذونات (Permission System)

### ثلاث طبقات مستقلة

| الطبقة | المصدر | الغرض |
|---|---|---|
| **المنصة** | `app/Support/SystemPermissions.php` | صلاحيات `admin` (AdminUser) |
| **المستأجر** | `app/Support/Permissions.php` | صلاحيات `tenant.*` |
| **الأدوار** | Spatie `permission` + teams | ربط الدور بالمستأجر/الفرع |

### آليات الفرض

| الآلية | العدد | الحالة |
|---|---|---|
| `$this->authorize()` | 325 استدعاء عبر 68 controller | ✅ |
| `Gate::` / `->can()` | ضمن الـ325 | ✅ |
| `Gate::policy()` صريحة | 27 | ✅ |
| `Gate::before` | `AppServiceProvider.php:269-279` | ⚠️ **لا يتجاوز أبداً** لـ `super_admin` داخل المستأجر — قرار صحيح |
| `system.permission:` middleware | على مسارات `/system` | ✅ |

### نتيجة الفحص الشامل

**مسح 419 مسار تعديل** ⇒ كل واحد يmaps إلى طريقة تحتوي `authorize()`/`Gate::`/`->can()`. فحص ثانٍ (model-bound بدون authorize وبلا فحص مستأجر) ⇒ **0 نتيجة**.

> هذه أقوى نقطة في النظام. لم أجد مسار تعديل بلا فحص.

### الاستثناءات (مُتحقَّق منها)

| الحالة | التفصيل |
|---|---|
| 11 policy غير مسجّلة صراحة | **تُحل تلقائياً** عبر discovery convention في Laravel — ليست عيباً |
| `QuoteApprovalController` | ✅ **يحتوي `authorize()`** — `:31` و`:102` (تصحيح ادعاء سابق) |

---

## 6. نظام الأحداث (Events / Listeners / Observers)

### تسجيل الـ listeners

```
bootstrap/app.php:71  →  ->withEvents(discover: false)
```
✅ **هذا يمنع الازدواج** الذي كان مُسجَّلاً في `EventServiceProvider`.

### الأحداث المُسجَّلة (12)

| الحدث | المستمع | الحالة |
|---|---|---|
| `WorkOrderCreated` | `NotifyOwnerOnCreation` | ✅ يعمل |
| `WorkOrderStatusChanged` | `LogActivityOnStatusChange` | ✅ يعمل |
| `PaymentRecorded` | `LinkPaymentToInvoice`، `UpdateInvoiceStatusOnPayment`، `HandleAutoInvoiceOnDoneWorkOrder` | ✅ يعمل |
| `ReceiptSubmitted` | `NotifyAdminsOfReceipt` | ✅ يعمل |
| `ReceiptReviewed` | `NotifyTenantOfReceiptDecision` | ✅ يعمل |
| `LoginSuccessful` | `LogSuccessfulLogin` | ⚠️ **لا يُطلق أبداً** |
| `LoginFailed` | `LogFailedLogin` | ⚠️ **لا يُطلق أبداً** |
| `InvoiceIssued` | (فارغ) | ❌ **لا يُطلق** — مستبدل باستدعاء ثابت في `InvoicesController.php:91` |
| `CustomerCreated` | (فارغ) | ❌ **لا يُطلق** |
| `StockLow` | (فارغ) | ❌ **لا يُطلق — تنبيه نفاد المخزون معطّل** |
| `LeaveRequested` | (فارغ) | ❌ **لا يُطلق — إشعارات الإجازات معطّلة** |
| `LeaveApproved` | (فارغ) | ❌ **لا يُطلق** |

### المراقبون (Observers)

| المراقب | مُسجَّل | الحالة |
|---|---|---|
| `EmployeeObserver` | ✅ `AppServiceProvider.php:248` | يعمل |
| `CenterObserver` | ✅ `:249` | يعمل |
| `WorkOrderObserver` | ✅ `:250` | يعمل |
| `WorkOrderItemObserver` | ✅ `:251` | يعمل |
| `PaymentObserver` | ✅ `:252` | يعمل |
| **`InvoiceObserver`** | ❌ **غير مُسجَّل** | 🔴 **حارس ZATCA معطّل — EV-01** |

---

## 7.map الوحدات التشغيلية

```
                    ┌──────────────┐
                    │  المنصة      │
                    │  /system     │
                    └──────┬───────┘
                           │ TenantSubscriptionService
                           │ TenantSuspensionService
                           ▼
┌─────────────┐      ┌──────────────┐      ┌─────────────┐
│  العملاء    │─────►│  كروت العمل  │◄─────│  المركبات   │
│  Customer   │      │  WorkOrder   │      │  Vehicle    │
└─────────────┘      └───┬──────┬───┘      └─────────────┘
                         │      │
        ┌────────────────┘      └────────────────┐
        ▼                                        ▼
┌─────────────┐   ┌──────────────┐   ┌──────────────────┐
│ المخزون    │◄──┤  الفواتير     │   │  عروض الأسعار    │
│ Inventory  │   │  Invoice     │◄──┤  Quote           │
│ (Service)  │   └──────┬───────┘   │ (Conversion)     │
└──────┬──────┘          │           └──────────────────┘
       │                 ▼
       │        ┌──────────────┐
       │        │  المدفوعات   │
       │        │  Payment     │
       │        └──────────────┘
       ▼
┌─────────────┐   ┌──────────────┐   ┌──────────────────┐
│ المشتريات  │   │  الفنيين     │   │  الموارد البشرية │
│ Purchasing  │   │  Technician  │   │  HR (74 مسار)    │
│ (Service)   │   └──────────────┘   │  ⚠️ بلا Service  │
└─────────────┘                      └──────────────────┘
       ▲
       │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
       └──┤ الفروع/المراكز│  │  الاشتراكات  │  │  التقارير    │
          │  Centers     │  │  Billing     │  │  Reports     │
          └──────────────┘  └──────────────┘  └──────────────┘
```

---

## 8. الفجواتEight

| # | الفجوة | الدليل | الأثر |
|---|---|---|---|
| 1 | 7 أحداث لا تُطلق | `EventServiceProvider.php:32-72` | تنبيهات المخزون + الإجازات معطّلة |
| 2 | `InvoiceObserver` غير مُسجَّل | `AppServiceProvider.php:248-252` | حارس ZATCA معطّل |
| 3 | HR بلا طبقة خدمة | 74 مسار، 16 controller، صفر Service | منطق HR في الـ controllers |
| 4 | الضمانات ليست وحدة | لا موديل/خدمة/policy | إعادة حساب لكل طلب |
| 5 | `ReportsController` ضخم | 24 استدعاء `DB::raw` وحده | استعلامات هائلة |
| 6 | `CreditNoteService` منطق مكرر | `app/Services/CreditNoteService.php:236-515` ≡ `:716-995` | انحراف محاسبي محتمل |
| 7 | 3 قواعد لحالة الفاتورة | `Invoice.php:175-224` / `PaymentService.php:119-152` / `PurchasingService.php:549` | نفس الجدول، قاعدتان |
| 8 | `InvoiceIssued` غير مستخدم | `InvoicesController.php:91` | مسار الفوترة التلقائية يُشعر بطريقة مختلفة |

---

## 9. ملاحظات معمارية إيجابية

| الملاحظة | الدليل |
|---|---|
| فصل `AdminUser` عن `User` | `app/Models/AdminUser.php` منفصل — يمنع خلط صلاحيات المنصة بالمستأجر |
| `TenantScoped` + `CenterScoped` traits | 60 + 45 موديل — بدل `where('tenant_id')` يدوي في كل مكان |
| `CenterScoped` يدعم `source='system'` | `CenterScoped.php:29-36` — بيانات نظام مرئية لكل الفروع |
| `useConfirm` composable مركزي للواجهة | 40 مستورد — بدل `window.confirm` |
| `usePrint` + `PrintEngine` مركزيان | 41 + 29 مستورد |
| `useServerTable` composable للجداول | 18 صفحة — نمط موحّد قائم |
| `useToast` مركزي | 67 مستورد |
| نموذج `page-manifest` بدل `import.meta.glob` | `scripts/generate-page-manifest.mjs` — حدّث الأداء بشكل حقيقي |

---

## 10. خريطة الفلاتر (Middleware Chain)

```
┌─ general ────────────────────────────────────────┐
│  TrustHosts · TrustProxies · HandleCors          │
│  PreventRequestsDuringMaintenance · EncryptCookies│
│  ValidatePostSize · TrimStrings · ConvertEmpty   │
│  StringsToNull                                  │
├─ auth ───────────────────────────────────────────┤
│  StartSession · ShareErrorsFromSession           │
│  ValidateCsrfToken · SubstituteBindings          │
├─ web ────────────────────────────────────────────┤
│  EncryptCookies · AddQueuedCookiesToResponse     │
│  StartSession · ShareErrorsFromSession           │
│  ValidateCsrfToken · SubstituteBindings          │
└──────────────────────────────────────────────────┘
```

**ملاحظات:**
- `EnsureSystemAdmin` — للوحة المنصة (فصل عن مستأجر `super_admin`)
- `EnsureSystemPermission` — صلاحيات `/system`
- `EnsureTenantActive` — يمنع المستأجر المعطَّل (يستخدم `messages.subscription.account_suspended` — مفتاح مفقود، انظر I18N)
- `center.context` — يضبط المركز الحالي
- `EnsureTwoFactorEnabled` — يفرض 2FA
- `HandleInertiaRequests` — يشارك `tenant`، `center`، `auth.permissions`، `subscription_lifecycle` مع كل Inertia response

---

## 11. واجهة Inertia المشتركة — نقطة الحقيقة للواجهة

`app/Http/Middleware/HandleInertiaRequests.php` يشارك:

| المفتاح | المصدر | الملاحظات |
|---|---|---|
| `auth.user` | المستخدم الحالي | |
| `auth.permissions` | `Permissions` | — **الواجهة لا تكرّر قراءة الصلاحيات من مكان آخر** (عدا استثناءين) |
| `tenant` | `Tenant` model | `:146-163` |
| `tenant.legal_name` | `tenants.legal_name` | |
| `center` | `Center` model | `:165+` مع `display_name` محسوب |
| `center.display_name` | `name_ar` ?: `name` ?: `name_en` | ترتيب احتياطي ذكي |
| `subscription_lifecycle` | `rescue(fn () => ...)` | ⚠️ `rescue` يُخفي خطأ `floatDiffInDays` — EV-02 |
| `app.name` | ❓ **غير مُتحقَّق** | انظر `07_SETTINGS_AND_DATA_SOURCES.md` |

> **فرصة مركزية**: `HandleInertiaRequests` هو **المكان الطبيعي** لتوحيد اسم المنظومة وشعارها — بدلاً من 6 مصادر متفرقة.
