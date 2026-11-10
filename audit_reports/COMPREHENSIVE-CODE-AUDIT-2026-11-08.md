# Code Audit Report — Carag V2 / Khidmh Pro
**Date:** 2026-11-08 | **Scope:** 564 PHP + 477 Vue/JS + 295 Database files | **Read-only**

---

## ملخص تنفيذي

| المحور | التقييم | الملاحظة |
|--------|---------|----------|
| الأمان (Security) | ⚠️ متوسط | 4 ثغرات حرجة، 4 عالية الخطورة، 6 متوسطة. عزل المستأجرين قوي لكن التفويض الداخلي ضعيف. |
| الكود الميت (Dead Code) | ⚠️ متوسط | 1 Service غير مستخدم، 13 Policy غير مسجّلة، 14 Component Vue غير مستخدمة. |
| الجودة البرمجية (Code Quality) | ❌ ضعيف | N+1 queries واسع الانتشار، عدم استخدام DB transactions في عمليات متعددة التحديث، عدم وجود `$this->authorize()` في معظم controllers الـ HR و WorkOrders. |
| الاستقرار (Stability) | ⚠️ متوسط | Backup OOM، race condition في PurchaseService، subscription auto-renew بدون انتظار دفع. |

---

## 1. الثغرات الأمنية (Security Vulnerabilities)

### [C-1] Stored XSS في LandingPreview.vue (أخطر ثغرة)
**الملف:** `resources/js/Pages/Public/LandingPreview.vue:127-153`
**المشكلة:** دالة `injectScripts()` تحقن `<script>` tags من قاعدة البيانات مباشرةً في DOM دون sanitization.
**مسار الاستغلال:** أي admin بدور `admin` (ليس بالضرورة `super_admin`) يمكنه حفظ `<script>alert(document.cookie)</script>` في `settings.website_chatbot_script`، وسينفَّذ تلقائياُ لدى كل زائر للصفحة العامة.
**الإصلاح المقترح:** حظر `<script>` tags في `WebsiteSettingsUpdateRequest.php` باستخدام validation rule أو regex blocklist على مستوى الـ Backend قبل التخزين. هذا يحمي الصفحة دون تعديل كود `LandingPreview.vue` الجاري تطويره.

### [C-2] GeneralSettingsController يقبل مفاتيح عشوائية
**الملف:** `app/Http/Controllers/System/GeneralSettingsController.php:23-43`
**الحالة:** تم إصلاح Allowlist جزئياً (`ALLOWED_KEYS = ['security.2fa_sms_enabled']`). لكن المسار لا يتطلب `super_admin`.
**الإصلاح:** إضافة `middleware('super_admin')` على مسار `PUT /system/settings/general`.

### [C-3] User::$fillable يتضمن is_system_admin
**الملف:** `app/Models/User.php:46`
**الحالة:** ✅ تم الإصلاح. تم إزالة `'is_system_admin'` من `$fillable` وتحديث 3 seeders لاستخدام `forceFill()`.

### [C-4] BackupController بدون ability check صريح
**الملف:** `app/Http/Controllers/System/BackupController.php:107-140`
**المشكلة:** `download()` و `destroy()` يتحققان فقط من وجود جلسة `auth:admin` ولا يفحصان الإمكانية الفعلية. أي `support` يمكنه تحميل نسخة احتياطية كاملة.
**ملاحظة:** الـ middleware موجود على الـ routes (`system.permission:system.activity_log.view`) لكن الدفاع الدفاعي مطلوب داخل الـ controller.
**الإصلاح:** إضافة `$this->authorize()` صريحة أو الاعتماد على `EnsureSystemPermission` middleware الموجود على المستوى الأعلى.

### [H-1] Impersonation لا يفرض super_admin داخليااً
**الملف:** `app/Http/Controllers/System/ImpersonationController.php:44-49`
**الإجراء المطلوب:** إضافة internal guard: `if (! $actingUser instanceof AdminUser || ! $actingUser->isSuperAdmin()) abort(403);`

### [H-2] BiometricAttendanceController بدون IP allowlist
**الملف:** `app/Http/Controllers/Api/HR/BiometricAttendanceController.php:34-52`
**الإجراءات المطلوبة:** تشفير الـ token، إضافة IP allowlist، إضافة nonce/timestamp validity window.

### [H-3] CreditNote refund amount uncapped
**الملف:** `app/Services/CreditNoteService.php:460-484` (وكرر 921-941)
**الإجراء المطلوب:** إضافة `$amount <= $netRefundDue + 0.01` check قبل إنشاء payment record.
**ملاحظة:** وكيل التدقيق اكتشف أن هناك فحص `refundable_amount` موجود في سطور 246-247 لكنه يطبق فقط على service items، وليس على `$refundPayment['amount']` الممرر من الـ request.

### [H-4] Gateway refunds بدون cumulative tracking
**الملف:** `app/Services/Payment/Gateways/*.php`
**الإجراء المطلوب:** إضافة عمود `refunded_total` على جدول `subscription_payments`.

### [M-1] Login rate limiter per email|ip
**الملف:** `app/Http/Requests/Auth/LoginRequest.php:97-121`
**الإجراء:** إضافة global per-account rate limit بعد N attempts مع CAPTCHA.

### [M-2/M-3] Session config weak for production
**الملف:** `config/session.php`
**الإجراء:** `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_SAME_SITE=strict`, `SESSION_LIFETIME=30`.

### [M-4] Missing CORS config
**الإجراء:** إنشاء `config/cors.php` مع allowed_origins محدّدة.

### [M-5] Race condition في PurchasingService
**الملف:** `app/Services/Purchasing/PurchasingService.php:533,560`
**الإجراء:** wrapping في transaction + `lockForUpdate()`.

### [M-6] v-html في Communication Templates Editor
**الملف:** `resources/js/Pages/System/Communication/Templates/Edit.vue:245`
**الحالة:** محمي حالياُ بـ SafeHtmlPlugin override. لكن الحل الأمثل هو استخدام `v-safe-html` صراحةً.

---

## 2. الكود الميت وغير المستخدم (Dead Code)

### 2.1 Services غير مستخدمة (PHP)
| الملف | الملاحظات |
|-------|-----------|
| `app/Services/HR/AttendanceCalculationService.php` | 0 references repo-wide — يمكن حذفه |

### 2.2 Policies غير مسجّلة/غير مستخدمة (13 ملف)
هذه Policies موجودة لكن غير مُسجّلة في `AppServiceProvider::boot()` ولا يٌستدعى أي `$this->authorize()` ضدها:

| Policy | الحالة |
|--------|--------|
| `CenterPolicy.php` | يحتوي فقط على TODO comments — لا يوجد تسجيل |
| `FeedbackPolicy.php` | غير مسجّل |
| `GoodsReceivedNotePolicy.php` | مذكور في test skip-list فقط |
| `HR/PayrollRunPolicy.php` | غير مسجّل |
| `InventoryBalancePolicy.php` | غير مسجّل |
| `InventoryMovePolicy.php` | غير مسجّل |
| `InventoryTransferPolicy.php` | غير مسجّل |
| `InvoicePolicy.php` | يستخدم في tests فقط |
| `PartPolicy.php` | يستخدم في tests فقط |
| `PurchaseOrderPolicy.php` | غير مسجّل |
| `TaxPeriodPolicy.php` | غير مسجّل |
| `UserPolicy.php` | مذكور في test skip-list فقط |

**ملاحظة هامة:** هناك أيضاُ 3 policies مُسجّلة في `AppServiceProvider` لكنها **لا توجد**: `AppointmentPolicy`, `ServiceBayPolicy`, `AppointmentMessageDeliveryPolicy`. هذه ستسبب `Class not found` errors عند تشغيل التطبيق.

### 2.3 Vue Components غير مستخدمة (14 ملف)
**Story files (5):**
- `AppButton.story.vue`, `AppCheckbox.story.vue`, `AppInput.story.vue`, `AppSelect.story.vue`, `AppTextarea.story.vue`
- لا يوجد Storybook configured في المشروع.

**Breeze leftovers (5):**
- `ApplicationLogo.vue`, `Dropdown.vue`, `DropdownLink.vue`, `NavLink.vue`, `ResponsiveNavLink.vue`
- مخلفات قالب Laravel Breeze لم تعد مستخدمة.

**Print engine leftovers (2):**
- `PrintHeader.vue` — never imported
- `TemplateReportTable.vue` — self-referencing tag غير صحيح، لا يوجد external import

**Suspicious pages (2):**
- `Pages/Errors/403.vue` — لا route يعرضه (403s تٌدار عبر middleware)
- `Pages/System/Analytics/Reports.vue` — قد يكون محمولاُ من `AnalyticsHub` بدلاُ منه

### 2.4 Duplicate Filenames (متوقعة وصحيحة)
جميع الملفات المكررة بالاسم هي classes مختلفة بـ namespaces مختلفة — لا مشكلة:
- `ProfileController` × 3 (App, System, different dirs)
- `SettingsController` × 2
- `WorkOrderController` × 2 (Api vs App\WorkOrders)
- `Index.vue` × 56 (standard per-module convention)
- `Show.vue` × 21 (standard pattern)

---

## 3. أخطاء برمجية محتملة (Error-Prone Patterns)

### 3.1 N+1 Queries (عالي الخطورة)
| الملف | السطر | المشكلة |
|-------|--------|---------|
| `EmployeePortalController.php` | ~line 483-511 | foreach loop without eager loading |
| `WorkOrders/WorkOrderStatusController.php` | ~line 107 | `$work_order->items()->where(...)->get()` inside loop |
| `ReportsController.php` | multiple | Complex joins without proper `with()` |

### 3.2 عدم استخدام DB Transactions (مرتفع)
| الملف | المشكلة |
|-------|---------|
| `EmployeePortalController.php` | Multiple DB updates without transaction wrapping |
| `PurchasingService.php:533,560` | Payment creation + balance update without lockForUpdate |

### 3.3 Unauthorized mutations في HR Controllers (عالي جدااً)
بناءً على تحليل الوكيل الثالث:
- `LeaveController.php` — `update()` بدون `$this->authorize()`
- `ShiftController.php` — نفس المشكلة
- معظم controllers الـ HR تعتمد على الـ route binding فقط بدون explicit authorization check

### 3.4 Missing Exception Handling (متوسط)
- بعض عمليات `file_get_contents()` في `PrintService.php` و `SubscriptionInvoiceService.php` بحاجة لـ try-catch.
- IntegrationsController calls without proper error boundaries.

### 3.5 TODO Markers موجودة في الكود
| الملف | السطر | المحتوى |
|-------|--------|---------|
| `CenterPolicy.php` | 45 | `// TODO: Add role check (only admin/manager can update)` |
| `CenterPolicy.php` | 55 | `// TODO: Add role check (only admin can delete)` |
| `WorkOrderController.php` | 370 | `// TODO(refactor): Move tab-specific relations to partial-reload endpoints.` |
| `InvoiceService.php` | 283 | `// TODO: ZATCA Chaining logic (Prev Hash) would happen here.` |

---

## 4. نقاط القوة الموجودة

1. ✅ `TenantScoped`/`CenterScoped` traits مطبقة على غالبية النماذج.
2. ✅ `TenancyContext::tenantId()` يعيد `null` صراحةً تحت `/system/*`.
3. ✅ `EnsureSystemPermission` middleware + `SystemPermissions::resolveFor()` registry.
4. ✅ `Gate::before` يتجاهل دور tenant-level `super_admin` ويتركز على `is_system_admin`.
5. ✅ `PaymentSettingsController.maskGatewayConfig` يخفي مفاتيح البوابات.
6. ✅ Customer portal session middleware يفحص `tenant_id` صراحةً.
7. ✅ `GeneralSettingsController.ALLOWED_KEYS` tight allowlist.
8. ✅ Security Scanner في Developer Center يتحقق من `TenantScoped`.

---

## 5. خطة العمل المقترحة (Priority Order)

### Phase 1 — Immediate (قبل النشر Prod)
1. ~~إزالة `is_system_admin` من `User::$fillable`~~ ✅ مكتمل
2. 🔴 منع `<script>` tags في `WebsiteSettingsUpdateRequest.php` (Y-XSS)
3. 🔴 إضافة `super_admin` middleware على `PUT /system/settings/general`
4. 🔴 إضافة authorization checks في `BackupController::download()` و `destroy()`
5. 🟡 إنشاء `config/cors.php`
6. 🟡 تحديث `config/session.php` للإعدادات الآمنة

### Phase 2 — Sprint (within 2 sprints)
7. 🔴 سقف مبلغ الـ Refund في `CreditNoteService`
8. 🔴 إضافة internal `super_admin` check في `ImpersonationController`
9. 🔴 إضافة `$this->authorize()` في HR controllers (Leave, Shift, etc.)
10. 🟡 إصلاح N+1 queries في `EmployeePortalController` و `WorkOrderStatusController`
11. 🟡 wrapping operations في DB transactions حيث ينقصها

### Phase 3 — Cleanup (technical debt)
12. 🟢 حذف 5 `.story.vue` files
13. 🟢 حذف 5 Breeze leftover components
14. 🟢 حذف `AttendanceCalculationService.php` غير المستخدمة
15. 🟢 مراجعة وإصلاح الـ 13 unregistered policies
16. 🟢 إصلاح الـ 3 TODO markers المتبقية
17. 🟢 التحقق من `403.vue` و `Analytics/Reports.vue`

### Phase 4 — Hardening
18. 🟡 IP allowlist + nonce على Biometric API
19. 🟡 `refunded_total` cumulative tracking للـ SubscriptionPayments
20. 🟡 streaming backup بدلاُ من accumulation في الذاكرة

---

*تم إعداد التقرير بواسطة Agnes (Sapiens AI) — قراءة فقط، لم يتم تعديل أي ملفات مشروع.*
