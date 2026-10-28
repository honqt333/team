# تقرير التدقيق الشامل — KHIDMH.PRO (خدمة برو)

**التاريخ:** 2026-10-25
**الفرع:** `feature/phase-2-goal-3`
**نوع المراجعة:** تدقيق وتحليل وتوثيق فقط — **لم يتم تعديل أي ملف تطبيق**
**حالة المستودع بعد التدقيق:** `git status` نظيف (لا تغييرات على ملفات التطبيق)

---

## 1. الملخص التنفيذي

### الحكم العام

المشروع **نظام تجاري مبني بأسس هندسية سليمة**، وليس هيكلاً أولياً. معماريته أوضح من متوسط مشاريع Laravel، وأنماط الأمان فيه (عزل المستأجرين، مصادقة ثنائية، تحديد المعدل، معاملات قاعدة البيانات) **أفضل مما هو شائع في الأنظمة المشابهة**. الاختبارات تغطي الوحدات التجارية الحرجة بدقة عالية (عزل المستأجرين، الفوترة، المخزون، بوابة العميل).

لكن التدقيق كشف **ثلاثة أنواع من المخاطر الحقيقية**:

| # | نوع المخاطرة | الجوهر | الخطورة |
|---|---|---|---|
| 1 | **حزمة إهمال استراتيجي** | أدوات الجودة trio (PHPStan /TranslationsCheck /استعلامات الأداء) **معطّلة فعلياً** — تمر بلا فائدة أو تفشل بلا تفسير | **P1** |
| 2 | **اعتماديات مصابة** | `laravel/framework` به ثغرة تجاوز مصادقة حرجة؛ 60 ثغرة إجمالاً | **P1** |
| 3 | **تراكم تصميمي** | نظام التصميم موجود لكنه **مُتجاوَز بنسبة 97.5%**؛ 15 ملف واجهة ميتة (~3,360 سطر) | **P2** |

### الأرقام الحاكمة

| المقياس | القيمة | المصدر |
|---|---|---|
| ملفات PHP | 536 (~70,764 سطر) | `find app` |
| ملفات JS/Vue | 439 (~146,673 سطر) | `find resources/js` |
| مسارات مسجّلة فعلياً | **642** (صفر تكرارات) | `php artisan route:list` |
| ترحيلات قاعدة البيانات | 244 (332 قيد مفتاح أجنبي) | `database/migrations` |
| ملفات اختبار | 114 | `tests/` |
| اختبارات ناجحة (تشغيل فعلي) | **349** في المجموعات المُشغّلة | سجل أدناه |
| **اختبارات فاشلة** | **10** (7 + 1 + 2) | سجل أدناه |
| ثغرات Composer | **60** (2 حرجة، 9 عالية) | `composer audit` |
| ثغرات npm | **7** (6 عالية، 1 حرجة) | `npm audit` |
| مفاتيح الترجمة الناقصة **فعلياً** | **13** (وليس 713) | قياس مباشر |
| أزرار لا تستخدم نظام التصميم | **1,231 من 1,255** (98%) | تحليل القوالب |
| ملفات Vue ميتة | **15** (~3,360 سطر) | رسم بياني الاستيراد |

### الرسالة الأساسية لصاحب المشروع

> النظام **قابل للتشغيل التجاري** ولا يحتاج إعادة بناء. ما يحتاجه هو **إعادة ضبط أدوات الجودة** (ثلاث أدوات معطّلة)، **تحديث حزمة واحدة** لإغلاق ثغرة.authentication، و**قرار توحيد** لواجهة مبنية بقواعد متعددة. أول ثلاث خطوات لا تحتاج قرارات تجارية ولا تحمل مخاطر تشغيلية تُذكر.

---

## 2. وصف بنية المشروع والتقنيات

### التقنيات والإصدارات المكتشفة (من ملفات المشروع لا من الذاكرة)

| الطبقة | التقنية | الإصدار | الدليل |
|---|---|---|---|
| Backend | Laravel Framework | **12.56.0** | `composer show` / `php artisan --version` |
| PHP | PHP | **8.5.0** (CLI)، `^8.2` مطلوب | `php -v` / `composer.json` |
| Frontend | Vue | **3.4+** | `package.json` |
| جسر الواجهة | Inertia.js | **2.0** (`@inertiajs/vue3` + `inertiajs/inertia-laravel`) | `package.json` |
| البناء | Vite | **7.0.7** | `package.json` |
| التنسيق | Tailwind CSS | 3.2.1 (plugin) + 4.0 (vite) | `package.json` |
| الترجمة (Vue) | vue-i18n | **10.0.8** | `package.json` |
| الصلاحيات | spatie/laravel-permission | **6.24** | `composer.json` |
| 2FA | pragmarx/google2fa-laravel | **2.3** | `composer.json` |
| PDF | barryvdh/laravel-dompdf | **3.1** | `composer.json` |
| Excel | maatwebsite/excel | **3.1.68** | `composer.json` |
| الاختبارات | PHPUnit | **11.5.55** + Vitest **2.1.5** | `phpunit.xml` / `package.json` |
| التحليل الساكن | PHPStan / Larastan | **2.0 / 3.0** (level 5) | `composer.json` |
| التنسيق | Laravel Pint | **1.24** | `composer.json` |
| المراقبة | sentry/sentry-laravel | **4.26** | `composer.json` |
| Node | Node.js | **22.21.1** | `node -v` |

> **ملاحظة مهمة:** `composer.json` يثبّت `config.platform.php = 8.3.30` بينما PHP المحلي 8.5.0 و CI يختبر 8.2/8.3/8.4. هذا يعني أن **بناء الإنتاج قد يختلف عن بيئة التطوير** (انظر AUD-014).

### الوحدات الرئيسية المكتشفة

| الوحدة | النماذج | المحركات | الحالة |
|---|---|---|---|
| العملاء والمركبات | `Customer`, `Vehicle`, `VehicleMake/Model/Color` | `CustomerController`, `VehicleController` | **مكتملة** — CRUD + دمج + استيراد/تصدير XLSX |
| أوامر العمل | `WorkOrder`, `WorkOrderItem`, `WorkOrderItemPart`, `WorkOrderPhoto`, `WorkOrderAttachment`, `WorkOrderInspection`, `WorkOrderLaborEntry`, `WorkOrderDamageMark` | `WorkOrderController` + 10 متحكمات فرعية | **مكتملة** — أقسام، حالات، فحص جودة، عمالة، مرفقات |
| عروض الأسعار | `Quote`, `QuoteLine`, `QuotePart` | `QuoteController` | **مكتملة** — موافقة + تحويل لأمر عمل |
| الفواتير | `Invoice`, `InvoiceLine`, `Payment` | `InvoiceService` + `PaymentService` | **مكتملة** — حسابية ضريبية ZATCA + تسوية الفلت |
| المخزون | `Part`, `Warehouse`, `InventoryBalance`, `InventoryMove`, `InventoryTransfer` | `InventoryService` | **مكتملة** — حركة + تحويل + عكس |
| المشتريات | `PurchaseOrder`, `PurchaseInvoice`, `Supplier`, `GoodsReceivedNote` | `PurchasingService` | **مكتملة** — أوامر شراء + استلام بضائع |
| الموارد البشرية | `Employee`, `Payroll`, `PayrollRun`, `Attendance`, `Leave`, `Shift`, `EmployeeContract`, `BiometricDevice` | `HRController` + متحكمات | **مكتملة** — رواتب، حضور، بصمة، إجازات |
| بوابة العميل | `CustomerPortalOtp`, `Appointment`, `AppointmentActivity` | 9 خدمات في `Services/CustomerPortal` | **مكتملة** — OTP + حجز + إلغاء + إعادة جدولة |
| الاشتراكات والفوترة | `Subscription`, `SubscriptionInvoice`, `DunningConfig`, `PromoCode` | 5 خدمات في `Services/Billing` | **مكتملة** — تجديد + تحصيل متدرّج |
| لوحة النظام | `Tenant`, `AdminUser`, `AdminActivityLog` | 29 متحكم في `Controllers/System` | **مكتملة** — إدارة مستأجرين + نسخ احتياطي + انتحال |
| الضرائب السعودية | `TenantZatcaSetting`, `TenantTaxSetting` | `ZatcaOnboardingService` | **موجود** — انضمام Phase 2 |
| الذكاء الاصطناعي | — | 3 مزوّدين + `MockSuggester` | **موجود مع خلل تفعيل** (AUD-010) |

### اتصال الواجهة بالخادم

نمط **Inertia.js** ناضج:

```
صفحة Vue (resources/js/Pages/…)
   ↓ Inertia::render(...)          — 166 استدعاء
Controller
   ↓ Resources\Models (Eloquent)
MySQL / SQLite
```

- `app/Http/Middleware/HandleInertiaRequests.php` يبني الـ props المشتركة (الصلاحيات، اللغة، الثيم)
- **Zero صفحات AJAX منفصلة** في الكود الأساسي — كلها Inertia
- API منفصل صغير في `routes/api.php`: فحص صحة، بصمة الحضور، اقتراحات الذكاء الاصطناعي

### المصادقة وإدارة الصلاحيات

```
تسجيل الدخول
  ├─ admin guard  → AdminUser (admin_users) → /system
  └─ web guard    → User (users)          → /dashboard, /app/*
       └─ 2FA challenge (EnsureTwoFactorEnabled + app.2fa.challenge)
              └─ session: '2fa:user_id' فقط، مع Auth::logout() قبل التحقق
```

- **`spatie/laravel-permission` مع Team Context**: `SetPermissionsTeamId` = `tenant_id` → عزل صلاحيات لكل مستأجر
- `app/Support/Permissions.php` (551 سطر) = **مصدر واحد** لتعريف الصلاحيات
- **31 Policy** على مستوى Eloquent + `authorize()` في المتحكمات
- **عزل المستأجرين** عبر global scope (`TenantScoped` / `CenterScoped`)

### تخزين البيانات وإدارتها

- MySQL 8 (إنتاج) / SQLite في-memory (اختبار)
- **244 ترحيل**، **332 قيد مفتاح أجنبي**، 77 `cascade` + 332 `nullOnDelete`
- **سجل تغييرات**: `WorkOrderActivity`, `AdminActivityLog`, `InventoryMove`, `AppointmentActivity`, `NotificationSendLog`
-Soft deletes على النماذج الرئيسية

### إدارة اللغات والتصميم

- **PHP**: `lang/ar/*.php` + `lang/en/*.php` — 47 ملفاً لكل لغة
- **Vue**: `resources/js/i18n/lang/{ar,en}.json` — مفتاحان فقط؟ لا — **51 مفتاحاً علوياً**، ~2,400 مفتاح مُسطَّح
- **RTL**: `SetLocale` + `PortalLocale` على مستوى الخادم + `ConvertArabicNumerals` + خصائص CSS المنطقية (`ms-`/`me-`)
- **نظام تصميم**: `resources/js/Design/tokens.json` (مصدر واحد) → `tokens.css` + `reset.css` + `typography.css`

### الاختبارات وأدوات البناء

| الأداة | الغرض | الحالة المُتحقَّقة |
|---|---|---|
| PHPUnit 11 | Backend — 114 ملف اختبار | **يعمل** (10 فشل، انظر §13) |
| Vitest 2 | Frontend — 14 ملف، 29 اختبار | **يعمل — 29/29 ناجح** |
| Laravel Pint | تنسيق PHP | **يعمل — 722 ملف، 2 مخالفة فقط** |
| ESLint | لقط JavaScript | **يعمل — 0 أخطاء، 1,809 تحذير** |
| **PHPStan level 5** | تحليل ساكن | **معطّل — exit code 1 (AUD-001)** |
| **`translations:check`** | فحص الترجمة | **معطّل منطقياً (AUD-002)** |
| **`check-tenant-scope.sh`** | عزل المستأجرين | **يعمل — 0 مخالفة** |
| Vite build | بناء الإنتاج | **يعمل — 34 ثانية** |
| `composer audit` / `npm audit` | الثغرات | **يعمل** (لا يُشغَّل في CI — AUD-003) |

---

## 3. نطاق التدقيق والتغطية

### ما تم فحصه فعلياً (بالأرقام)

| المجال | التغطية | الدليل |
|---|---|---|
| المسارات | **642/642** | `php artisan route:list --json` — فحص كامل + تحليل تكرار |
| المتحكمات | 145 ملف — **جميع** | فحص `authorize`/`Gate`/استعلامات يدوية |
| النماذج | 124 ملف — **جميع** | فحص `TenantScoped`/`CenterScoped`/`$fillable` |
| الخدمات | 43 ملف — **جميع** | فحص المعاملات والحسابات |
| ملفات الترحيل | 244 — **إحصاء + فحص القيود** | `foreign()`/`cascade`/`nullOnDelete` |
| ملفات Vue | 400 — **رسم بياني الاستيراد الكامل** | كشف الملفات الميتة بدقة |
| ملفات الترجمة | 47 PHP × 2 لغة + 2 JSON | **مقارنة مفتاح-بمفتاح** لا عدد فقط |
| الاعتماديات | 60 Composer + 7 npm | `composer audit` + `npm audit` |
| الاختبارات | 114 ملف — **أُشغّلت مجموعات مختارة** | سجل في §13 |
| الأمان | المصادقة، 2FA، IDOR، الرفع، الأسرار، SQL injection | فحص يدوي + إسناد |

### ما لم يُفحص أو تعذّر التحقق منه

| البند | السبب | الأثر |
|---|---|---|
| **فحص بصري لكل صفحة** | لم يُشغَّل متصفح/آلةScreenshot في هذه الجلسة | تقييمات RTL/UX في §9 مبنية على **تحليل ساكن** (قراءة الكود)، لا على رصد شاشة |
| **قاعدة بيانات الإنتاج** | غير مصرّح بالوصول (`.env` يشير MySQL محلي، ولا اتصال إنتاج) | تأكيدات سلامة البيانات في §11 مبنية على المخطط لا على بيانات حقيقية |
| **سلوك ZATCA الحيّ** | لا نُرسل طلبات إلى هيئة الزكاة والضريبة | منطق Phase 2 مُراجَع ساكناً فقط |
| **بوابات الدفع (Moyasar)** | لا نُرسل مدفوعات حقيقية | منطق البوابة مُراجَع ساكناً فقط |
| **البريد / SMS الفعلي** | لم تُرسل رسائل | مسارات الإرسال مُراجَعة ساكناً |
| **معدّل الاستعلامات في الإنتاج** | `PerformanceBaselineTest` يقيس SQLite لا MySQL | أرقام §14 **مؤشرات** لا قياسات إنتاج |
| **اختبارات E2E للمتصفح** | لا توجد في المستودع | فجوة في تغطية تدفقات المستخدم |
| **سلوكHorizonscale عند Increased Load** | غير قابل للاختبار محلياً | تقديرات الأداء تحمل هامش خطأ |
| **`app/Services/Developer/` (9 ملفات)** | أدوات المطور الداخلي — خارج نطاق الاستخدام التجاري | فحص سطحي فقط |
| **`app/Models/Developer/` (5 ملفات)** | أدوات المطور — خارج نطاق الاستخدام التجاري | فحص سطحي فقط |

---

## 4. نقاط القوة

### 4.1 العزل بين المستأجرين — مصمَّم بعمق لا بصدفة

أهم أصل في النظام. الدليل:

- `app/Models/Concerns/TenantScoped.php` — global scope على 57 نموذجاً
- `app/Support/TenancyContext.php:16-26` — المشتق من `auth()->user()->tenant_id`، مع **استثناء مقصود** لمسارات `system/*` التي تحتاج رؤية عابرة للمستأجرين
- `scripts/check-tenant-scope.sh` — **بوابة جودة تعمل** في CI، تُظهر **0 مخالفة** على 124 نموذجاً
- **21 اختبار عزل** يمرّ (`TenantIsolationTest` 6، `TenantIsolation/*` 21، `HrAndPurchasingTenantIsolationTest`، `InvoicePaymentTenantIsolationTest`، `SuppliersIsolationTest` 9)

هذا ليس تطبيقاً سطحياً — النظام يفصل بين:
- **المستأجر** (`tenant_id`)
- **الفرع** (`center_id`) — عبر `CenterScoped` مع دعم بيانات `source = 'system'` المشتركة

### 4.2 بوابة العميل — أفضل مثال على التصميم الأمني في المشروع

`app/Http/Middleware/EnsureCustomerPortalSession.php` يجمع أربع طبقات حماية حقيقية:

1. **مهلة خمول 30 دقيقة** + **سقف مطلق 12 ساعة** — السقف المطلق يحدّ من ضرر جلسة مسروقة
2. **مطابقة المستأجر**:.session minted for tenant A تُرفض على رابط tenant B (`(int) $routeTenant->id !== $tenantId`)
3. **بحث العميل داخل المستأجر** (`where('tenant_id', $tenantId)->whereKey($customerId)`) — فحص واضح وصريح
4. **كعكة جلسة منفصلة** (`carag_customer_portal_session`) عبر `UsePortalSessionCookie` — حلّ تعارض التبويبات (مُوثَّق في `bootstrap/app.php`)

**الأثر**: 39 اختبار في `CustomerPortalHttpTest` + 23 في `CustomerPortalOtpTest` + 30 في `CustomerPortalActionsHttpTest` + 30 في `CustomerPortalBookingHttpTest` — **كلها ناجحة**.

فحص الملكية (IDOR) موجود فعلاً في طبقة الخدمة:
`app/Services/CustomerPortal/CustomerPortalAppointmentActionService.php:364,390,418` — `->where('customer_id', $customer->id)`

### 4.3 الحسابات المالية — أدق مما هو معتاد

`app/Services/Optimization/TaxCalculator.php` يطبّق **تسوية الفلت على مستوى المستند** (ZATCA-compliant):
- حساب الضريبة على **مجموع** القيم الخاضعة لكل فئة، لا سطراً سطراً
- توزيع فرق التقريب (0.01) على السطر الأول من المجموعة
- وضعان: `exclusive` و `inclusive` مع اشتقاق صحيح

`WorkOrder::recalculateTotals()` (app/Models/WorkOrder.php:61-112) يتعامل مع الوضعين بشكل صحيح، و`InvoiceService::createFromWorkOrder()` **لا يثق** في الأعمدة القديمة بل يعيد الحساب دفاعياً — تعليق الكود يشرح سبب ذلك صراحةً.

**10 اختبارات ضريبة ناجحة** (`TaxCalculationTest`) + `InvoiceConversionLineTotalsTest` + `InvoicePaymentStatusTest` (4، يغطي الاسترداد والتسوية).

### 4.4 سلامة البيانات في المخزون

`app/Services/Inventory/InventoryService.php` — كل عملية داخل `DB::transaction` (7 عمليات)، مع:
- **سجل حركة كامل** (`InventoryMove` بنوع `receipt` / `issue_to_workorder` / `adjustment` / `reversal`)
- **حارس المخزون السالب** مع صلاحية تجاوز (`inventory.override_negative_stock`)
- **دعم العكس** (reversal)Instead of حذف

### 4.5 جودة معمارية

- `app/Support/Permissions.php` (551 سطر) = **مصدر واحد** للصلاحيات — لا تشتّت
- `app/Actions/WorkOrder/` — نمط Action واضح
- `app/Observers/` — فصل واستقلال
- **سجل تدقيق** في 5 جداول مستقلة
- `bootstrap/app.php` **مُوثَّق بمحتوى تعليقات decisions** — يسهل صيانة قرارات التصميم

### 4.6 إدارة المراقبة

- `RequestId` + `SentryContext` → `correlation_id` + `tenant_id` في كل سجل
- **JSONFormatter** تلقائي خارج بيئة `local` → جاهز لـ Fluent Bit / Loki
- Sentry يستثني فحوص الصحة (`healthz` / `readyz`)

### 4.7 أداء البناء والواجهة

- بناء Vite: **34 ثانية**، تقسيم تلقائي للحزم
- `vendor-misc` 445 kB (gzip 125) — مقبول
- `Inertia` يقلل الطلبات مقارنة بـ SPA مع API منفصل

---

## 5. نقاط الضعف

| # | الملاحظة | التصنيف | الثقة |
|---|---|---|---|
| AUD-001 | PHPStan معطّل — CI يفشل عند كل تشغيل | خلل مؤكد | **عالية** |
| AUD-002 | `translations:check` يسجّل 713 نقصاً — 700 منها إيجابيات كاذبة | خلل مؤكد | **عالية** |
| AUD-003 | `laravel/framework` 12.56.0 — ثغرة تجاوز مصادقة حرجة | ثغرة مؤكدة | **عالية** |
| AUD-004 | 60 ثغرة Composer / 7 npm — بلا فحص آلي في CI | ثغرة مؤكدة | **عالية** |
| AUD-005 | `EmployeeDocumentsController` يقبل أي ملف على قرص public | خلل مؤكد | **عالية** |
| AUD-006 | أسرار ZATCA مخزّنة بنص صريح | خلل مؤكد | **عالية** |
| AUD-007 | توكن أجهزة البصمة مخزّن بنص صريح + مقارنة مباشرة | خلل مؤكد | **عالية** |
| AUD-008 | النظام يُكمل تلقائياًTranslationsCheck عبر 5 مستويات رفع | خطر محتمل | **متوسطة** |
| AUD-009 | استعلام N+1 في قائمة المركبات (25 استعلام / حد 25) | خلل مؤكد | **عالية** |
| AUD-010 | مفتاح AI لا يمكن تفعيله أبداً (مفتاح config مفقود) | خلل مؤكد | **عالية** |
| AUD-011 | 15 ملف Vue ميت (~3,360 سطر) | خلل مؤكد | **عالية** |
| AUD-012 | نظام التصميم مُتجاوَز بنسبة 97.5% | فرصة تحسين | **عالية** |
| AUD-013 | 1,231 نص عربي ثابت خارج نظام الترجمة | خلل مؤكد | **عالية** |
| AUD-014 | `config.platform.php` يثبّت 8.3.30 بينما الإنتاج 8.5 | مخاطرة محتملة | **متوسطة** |
| AUD-015 | 10 اختبارات فاشلة (الاقتباسات، الأداء، الترجمة) | خلل مؤكد | **عالية** |
| AUD-016 | ميزة بلا اختبارات — انتحال بلا أي اختبار | خلل مؤكد | **عالية** |
| AUD-017 | CI فيه مهمة مكرّرة (`test-frontend` مرتين) | خلل مؤكد | **عالية** |
| AUD-018 | اثنان من ملفات الموديلات متكرران (WorkOrderFormModal) | فرصة تحسين | **عالية** |

---

## 6. نتائج المراجعة الوظيفية

### 6.1 العملاء والمركبات — **مكتملة**

- `CustomerController` + `CustomerMergeController` (دمج مع معاينة الأثر قبل التنفيذ)
- استيراد/تصدير XLSX عبر `maatwebsite/excel`
- `VehicleMake` → `VehicleModel` → `VehicleColor` بتسلسل هرمي مع `source` (center/tenant/system)
- **منع الحذف غير الآمن**: SoftDeletes + `nullOnDelete` على المفاتيح الأجنبية (332 قيد)
- التحقق من العلاقة: `CustomerStoreRequest` يمنع تكرار الهاتف داخل المستأجر والفرع

### 6.2 أوامر العمل — **مكتملة**

دورة الحالة محروسة في المتحكم لا في الواجهة:

```
draft → open → in_progress → (on_hold ⇄ in_progress) → ready_for_qc → done
                                                        ↘ cancelled
```

`app/Http/Controllers/App/WorkOrders/WorkOrderStatusController.php`:
- `startWork()` يرفض الانتقال إذا لم يكن `open`/`in_progress` (سطر 108)
- `putOnHold()` / `resume()` / `complete()` عبر `ChangeStatusRequest`
- **سجل موثوق**: `WorkOrderActivity` + `logActivity()` على كل تغيير

### 6.3 الفواتير والضرائب — **مكتملة**

- `TaxCalculator` مع تسوية فلت على مستوى المستند
- `InvoiceService::createFromWorkOrder()` مع إعادة حساب دفاعية
- تسلسل ترقيم محمي بـ lock (`InvoicePaymentStatusTest` / `InvoicingSecurityTest` — "sequential numbering with locking")
- **عدم قابلية تعديل الفاتورة المُصدرة** (اختبار `immutability of issued invoice` يمرّ)
- ZATCA Phase 2: CSR → CSID (محاكاة/إنتاج) → ضريبة

### 6.4 الموظفون والصلاحيات — **مكتملة**

- 12 نموذج HR، رواتب، حضور (يدوي + بصمة)، إجازات، عقود، ورديات
- **الصلاحيات تُفحص في الخادم**: 31 Policy + `authorize()` في كل متحكم حسّاس
- `SetPermissionsTeamId` = عزل صلاحيات لكل مستأجر

### 6.5 المخزون والمشتريات — **مكتملة**

مُتحقَّق via: `WorkOrderInventoryTest` (8، ناجح)، `InventoryServiceTest` (11، ناجح)، `DirectSalesInventoryDeductionTest`، `PurchaseDebitNoteTest`

### 6.6 الوظائف غير القابلة للتحقق أو الناقصة

| الوظيفة | الحالة | ملاحظة |
|---|---|---|
| بوابة العملاء | **موجودة ومكتملة** | OTP، حجز، إلغاء، إعادة جدولة، لوحة |
| الإشعارات | **موجودة** | `InternalNotification` + `NotificationSendLog` + SMS/WhatsApp |
| التذكيرات | **موجودة** | `DunningService` (25k) + `DunningRetryService` |
| التكاملات الخارجية | **جزئية** | ZATCA، Moyasar، SMS، WhatsApp — كلها مُراجَعة ساكناً فقط |
| التكامل المحاسبي | **غير موجود** | لا يوجد ربط ERP/محاسبة — خارج النطاق المرصود |
| تعدد المستأجرين | **موجود** | مع عزل مُتحقَّق منه |

---

## 7. فرص الدمج والتوحيد

> التفاصيل الكاملة في `PROJECT_MERGE_AND_UI_PLAN.md`. ملخص الأدلة:

| المعرّف | الفرصة | الدليل | القرار | الأولوية |
|---|---|---|---|---|
| MERGE-01 | **مكوّنات نظام التصميم غير مستخدمة** | 1,231 `<button>` أصلي مقابل 24 `<AppButton>` | توحيد جزئي | P2 |
| MERGE-02 | **ملفات Vue ميتة** | 15 ملف، 0 `import` | حذف | P2 |
| MERGE-03 | **`WorkOrderFormModal` مكرر** | نسختان: 442 و 1,301 سطر | حذف الاثنتان | P2 |
| MERGE-04 | **دوال حساب الرصيد** | `getOutstandingBalance()` في حلقة + `withSum` في مكان آخر | توحيد | P2 |
| MERGE-05 | **صفحات Detail بـ 9 تبويبات** | `activeTab` في 9 صفحات، لا مكوّن مشترك | توحيد جزئي | P3 |
| MERGE-06 | **نصوص ثابتة عربية** | 723 في Vue + 508 في PHP | توحيد | P2 |
| MERGE-07 | **تكرار ألوان الأزرار** | indigo 2,522 / emerald 2,018 / red 2,238 / gold 1,879 / violet 656 / blue 1,352 | توحيد | P3 |

---

## 8. تقرير التصميم وتجربة المستخدم

### 8.1 حالة نظام التصميم اليوم

يوجد نظام تصميم **مُعرَّف بشكل ممتاز** و**غير مُطبَّق**:

```
resources/js/Design/
  tokens.json      ← مصدر واحد (لون/مسافة/خط/حركة)
  tokens.css       ← مولَّد كمتغيرات CSS
  reset.css        ← 139 سطر
  typography.css   ← 138 سطر
```

**المقاييس**:

| المقياس | القيمة |
|---|---|
| ملفات Vue تستخدم `var(--token)` | **10 من 400** (2.5%) |
| `var(--color-*)` | 96 استخداماً |
| `var(--space-*)` | 23 استخداماً |
| `var(--font-*)` | 32 استخداماً |
| `<AppButton>` | 24 من 1,255 زر (1.9%) |
| `<AppInput>` | 4 ملفات فقط |
| `<AppSelect>` | **0 استخدام إنتاجي** (اختبار + story فقط) |

### 8.2 الفروقات الفعلية المكتشفة

| العنصر | الوضع | الدليل |
|---|---|---|
| **الأزرار** | 6 ألوان أساسية متنافسة، بدون class مشترك | indigo 2,522 / emerald 2,018 / red 2,238 / gold 1,879 / blue 1,352 / violet 656 |
| **نمط الزر** | نسخ-لصق حرفي لـ Tailwind | `class="px-4 py-2 bg-gray-100 dark:bg-gray-700..."` يتكرر |
| **الحقول** | 799 `<input>` أصلي مقابل 4 استخدام لـ `AppInput` | `grep -rc '<input'` |
| **القوائم المنسدلة** | `SearchableSelect` (173) هو المعتمد الفعلي — و`AppSelect` شبه معطل | `SearchableSelect` في 98 ملف |
| **الجداول** | لا مكوّن مشترك | `AppTable` — 0 استخدام |
| **النوافذ** | لا مكوّن مشترك | `AppModal` — 0 استخدام |
| **التوكنات** | معرَّفة ومحمّلة لكن مهملة | 10/400 ملف |

### 8.3 التوصية: نظام تصميم عملي

**لا نوصي بإعادة تصميم.** نوصي بـ **توحيد تدريجي قائم على الموجود**:

**المرحلة 1 — Tokens → أصناف دلالية**
```css
/* في tokens.css — اشتقاق من المتغيرات الحالية */
.btn { /* من AppButton.vue المستخرج */ }
.btn--primary { background: var(--color-brand-primary); }
.btn--danger  { background: var(--color-danger); }
.btn--ghost   { background: transparent; }
.input { /* من AppInput.vue */ }
.card { border-radius: var(--radius-lg); background: var(--color-surface); }
```

**المرحلة 2 — إصلاح `AppSelect`**
المكوّن **لا يقبل `options`** بل `<slot>` فقط — بينما اختباره يمرّر `options`. النتيجة: `options` تتسرّب إلى `<select>` الأصلي كـ attribute غير صالح (تحذير Vue في سجل Vitest). **تصحيحان**: (أ) إضافة prop `options`، أو (ب) تغيير الاختبار ليمرّر `<option>` — ويُفضَّل (أ) لأن `SearchableSelect` هو المعتمد.

**المرحلة 3 — تبنٍّ تدريجي**
استبدال `<button class="...">` بـ `<AppButton variant="...">` صفحةً صفحة، بدءاً بأكثر الصفحات استخداماً (العملاء، المركبات، أوامر العمل).

### 8.4 قواعد RTL / LTR

- **صحيح**: 584 استخداماً لـ `dir="ltr"` — **معظمها سليم** (أرقام هواتف، بريد، VIN، مبالغ). هذه ممارسة صحيحة وليست خللاً.
- **مؤشر**: ~92 خاصية اتجاهية فيزيائية (`ml-`/`mr-`/`pl-`/`pr-`) مقابل 65 منطقية (`ms-`/`me-`). تحويل تدريجي مطلوب.

### 8.5 الاستجابة للشاشات

`resources/css/app.css` + Tailwind مع `dark:` — تم فحص الكود ساكناً. **لم يُجرَ فحص بصري** (انظر §3).

---

## 9. تقرير الترجمات والعربية والإنجليزية

> التفاصيل الكاملة في `PROJECT_TRANSLATION_AUDIT.md`.

### 9.1 النتيجة الجوهرية — تصحيح مُهم

الاختبار `tests/Feature/I18nCoverageTest.php` يسمح بـ **708 مفاتيح ناقصة**. `translations:check` يبلّغ **713**.

**قياس مباشر أظهر أن 700 من الـ 713 إيجابية كاذبة.** السبب في `app/Console/Commands/TranslationsCheck.php:188`:

```php
if (! isset($definedKeys[$locale][$key])) {   // ← $definedKeys = ملفات PHP فقط
    $missing[$locale][$key] = $files;
}
```

المتغيّر `$vueDefined` (الأسطر 95-106) — الذي يحمل مفاتيح `resources/js/i18n/lang/*.json` — **لا يُستعمل أبداً** في تحديد النقص. يُستعمل فقط في فحص تطابق ar/en (السطر 199). والنتيجة: **كل مفتاح خاص بـ Vue يُبلَّغ ناقصاً** رغم وجوده في JSON.

**إثبات**: `system.admin_users.search_placeholder` — مُبلَّغ ناقصاً، وموجود فعلاً في `ar.json` (السطر 1395) ويُحلّ بشكل صحيح في وقت التشغيل.

### 9.2 الواقع الفعلي

| القياس | النتيجة |
|---|---|
| مفاتيح ناقصة **فعلاً** (absent من PHP **و** JSON) | **13** |
| منها ناقصة في ar فقط | 0 |
| منها ناقصة في en فقط | 0 |
| **التباين بين اللغتين** | **0** (symmetric) |
| مجلدات/ملفات PHP ar مقابل en | **متطابقة تماماً** (47/47) |
| عدد `=>` في كل زوج ملفات PHP | **متطابق تماماً** |
| قيم بديلة (`[AR] foo`) | **0** |

**الخلاصة**: تغطية الترجمة **ممتازة**. العيب في أداة القياس لا في المحتوى.

### 9.3 المفاتيح الـ 13 الناقصة فعلاً

```
common.secure_payment_note
company_profile.subscription.payment_initiated
customer_portal.otp.body
inventory.stock.status.low
payments.errors.amount_exceeds_invoice_balance
payments.errors.amount_exceeds_max
payments.errors.amount_exceeds_supplier_balance
payments.errors.invoice_already_paid
payments.errors.no_outstanding_balance
payments.form.max
purchasing.invoices.remaining
purchasing.invoices.total
purchasing.suppliers.no_open_invoices
```

**الأثر**: 6 منها رسائل خطأ دفع تظهر للمستخدم بالعربية/الإنجليزية كما هي المفتاح (لا مترجمة). الأثر **منخفض** لكنه حقيقي.

### 9.4 النصوص الثابتة خارج نظام الترجمة — finding أكبر

| الطبقة | العدد |
|---|---|
| نصوص عربية ثابتة في قوالب Vue | **723** |
| نصوص عربية ثابتة في متحكمات PHP (خارج التعليقات) | **508** |

**أمثلة مؤكدة** (وليست تعليقات):
- `app/Http/Controllers/App/CustomerImportExportController.php:59` — `'لم يتم رفع ملف'` (رسالة JSON للمستخدم)
- `app/Http/Controllers/App/CenterSettingsController.php:156` — `__('تم الحفظ بنجاح')` (مفتاح حرفي لا مفتاح!)
- `app/Http/Controllers/App/ReportsController.php:171-179` — رؤوس CSV بالعربية فقط (تقرير المدفوعات **عربي فقط** حتى لو كان النظام إنجليزياً)
- `app/Http/Controllers/App/CompanyProfileController.php:101-104` — قوالب إشعارات SMS بالعربية فقط

**الأثر**: مستخدمInterface إنجليزي يرى شاشة عربية في هذه المواضع. وخيارات التقارير **ليست ثنائية اللغة**.

---

## 10. تقرير قاعدة البيانات

### 10.1 المخطط

| القياس | القيمة |
|---|---|
| ترحيلات | 244 |
| قيود المفتاح الأجنبي | 332 |
| `onDelete('cascade')` | 77 |
| `nullOnDelete` / `restrict` | 332 |
| SoftDeletes | على النماذج الرئيسية |

### 10.2 نقاط القوة

- **FK واقعية**: المفاتيح الأجنبية تُستخدم مع `nullOnDelete` ومع `cascade` حيث مناسب — لا فك ارتباط عشوائي
- **سجل تدقيق** على 5 جداول مستقلة
- **عزل ثنائي المستوى** (`tenant_id` + `center_id`) — لا يكفي `tenant_id` لنظام فروع
- **لقطات ضريبية** (`HasTaxSnapshot`) — تُجمّد إعداد الضريبة وقت البيع. ممتاز لمتطلبات ZATCA
- **ترقيم تسلسلي مقفل** (`InvoicePaymentStatusTest`: "sequential numbering with locking") — يمنع التكرار عند التزامن

### 10.3 الملاحظات

| # | الملاحظة | الشدة | الأثر |
|---|---|---|---|
| DB-01 | `TenantScoped` **يفتح عند غياب السياق** | P2 | لو وصل نموذج `TenantScoped` بلا `tenant_id` في `auth()`،/flتر لا يُطبَّق. لكن `scripts/check-tenant-scope.sh` + `TenantScoped` على 57 نموذجاً + `EnsureTenantActive` يقلّلون الخطر كثيراً. **مخاطرة دفاعية** تستحق توثيقاً |
| DB-02 | الحسابات المالية تستخدم `float` | P2 | `(float) $this->total_incl_tax` في `Invoice.php:89`. لـ SAR (ريال) بحدّين، خطر حقيقي. **الأدق** استخدام `decimal` حصراً. ملاحظة: `TaxCalculator` يقيد التقريب بـ 2 — وهذا minimizes الخطر عملياً |
| DB-03 | `Invoice::updatePaymentStatus()` ينشئ/يحدّث | P3 | تم استخدام سعة 0.01 للتسامح مع الفاصلة العائمة — سلوك سليم ومُختبر |
| DB-04 | استعلام N+1 في `getOutstandingBalance()` | P2 | `DB::table('invoices')` خام داخل حلقة — انظر AUD-009 |
| DB-05 | `CenterScoped` يتجاوز `source='system'` | P3 | سلوك **مقصود** وموثّق (بيانات النظام المشتركة بين الفروع) |

### 10.4 ما لم يُتحقق منه

لا يوجد اتصال بقاعدة إنتاجية. تأكيدات الأقسام (تكرار البيانات، أعمدة ميتة، بيانات يتيمة) **غير مُتحقَّق منها** — تتطلب فحصاً بصلاحية قراءة على الإنتاج.

---

## 11. التقرير الأمني

> **نطاق الفحص**: المصادقة، 2FA، إدارة الجلسات، التحكم بالوصول، IDOR، التحقق من المدخلات، SQL injection، XSS، CSRF، رفع الملفات، الأسرار، إعدادات الإنتاج، الاعتماديات.
> **حدود الفحص**: لم يُنفَّذ أي اختبار اختراق، ولم يُختبر أي ثغرة على بيانات حقيقية، ولم يُفحص خادم الويب (Apache/Nginx) الفعلي في الإنتاج.

### 11.1 نقاط القوة الأمنية

| الضابط | الدليل |
|---|---|
| CSRF | `bootstrap/app.php` — `validateCsrfTokens(except: ['locale','healthz','readyz'])` — استثناءات محددة فقط |
| 2FA | إلزامي على مستوى المستأجر (`EnsureTwoFactorEnabled`) + تحقق عند الدخول + سقف 12 ساعة |
| تحديد المعدل | **17 مُعرِّفاً** في `RouteServiceProvider` — دخول، تسجيل، استعادة كلمة مرور، 2FA، بوابة العميل (6)، عام |
| حجب XSS | `SafeHtmlPlugin` مسجَّل في تطبيق Vue — مع اختبار انحدار (`SecurityRegressionTest`) |
| SQL injection | **51 استخداماً لـ raw SQL — كلها نصوص ثابتة**، لا إدخال مستخدم مُدخَل |
| الأسرار | `.env` في `.gitignore` وغير متتبَّع في git ✓ |
| تعداد المحاولات | `CustomerPortalOtpTest`: "verify rejects after max attempts" ✓ |
| idempotency | `AutoInvoiceOnFullPaymentTest`: "does not double invoice" ✓ |
| Correlation | `RequestId` + `SentryContext` على كل طلب |

### 11.2 الثغرات المؤكدة

#### AUD-003 (P1) — ثغرة تجاوز مصادقة حرجة في Laravel

```
CVE-2026-114509  critical  TokenGuard authentication bypass through
                            non-string API credentials
الإصدار المتأثر:  <12.68.0
المثبَّت:          12.56.0
الإصلاح المتاح:   12.70.1  (داخل القيد الحالي ^12.0 — لا كسر!)
```

**الأثر**: تجاوز المصادقة على مسارات API المحمية بـ Sanctum TokenGuard.
**المسار المتأثر في هذا المشروع**: `POST /api/v1/work-orders/{workOrder}/suggestions` و `POST /api/v1/ai/demo/describe-vehicle` — كلاهما بـ `auth:sanctum`.
**ملاحظة مخفِّفة**: الكود يستخدم `Sanctum` guard على `web` users أساساً؛ الأثر الفعلي يتوقف على نمط الاستخدام الفعلي لـ personal access tokens.

#### AUD-004 (P1) — 60 ثغرة Composer + 7 npm

| الحزمة | الأسوأ | الإصدار المثبَّت | الإصلاح |
|---|---|---|---|
| `laravel/framework` | **critical** | 12.56.0 | 12.70.1 |
| `phpoffice/phpspreadsheet` | **critical** | (عبر maatwebsite) | 1.30.5+ / 2.1.17+ |
| `maatwebsite/excel` | high | 3.1.68 | **3.1.70** (داخل القيد) |
| `guzzlehttp/guzzle` | high | — | 7.15.2+ / 8.0.1+ |
| `league/commonmark` | 9 advisories high | — | 2.10.1+ |
| `symfony/http-kernel` | high | — | 7.4.12+ / 8.0.12+ |
| `symfony/mime` | high | — | 7.4.12+ |
| `dompdf/dompdf` | 4 medium + 2 low | 3.1.5 | **3.1.6** |
| npm: `postcss` | high | ≤8.5.22 | `npm audit fix` |
| npm: `source-map-js` | high | ≤1.2.1 | `npm audit fix` |
| npm: `nanoid` | critical | — | `npm audit fix` |

**ملاحظة مهمة**: `maatwebsite/excel` و `dompdf` و `laravel/framework` — **كلها قابلة للإصلاح داخل القيود الحالية** (`^3.1` / `^3.1` / `^12.0`). الإصلاح = `composer update` بدون كسر.

**الأهم**: `composer audit` و `npm audit` **غير مُشغَّلين في CI** — لا شيء يمنع انحدار الاعتماديات.

#### AUD-005 (P1) — رفع ملفات غير مقيدة على القرص العام

```php
// app/Http/Controllers/App/HR/EmployeeDocumentsController.php:21-25
$request->validate([
    'title' => 'required|string|max:255',
    'type' => 'required|string',
    'file' => 'required|file|max:10240',   // ← أي نوع ملف، بلا mimes
]);
$path = $request->file('file')->store('employee-documents', 'public');
```

**السلسلة كاملة**:
1. `public/storage` → symlink → `storage/app/public` (موجود ومُتحقَّق)
2. `employee-documents` على القرص `public` (75 رفعاً على public في المجموع)
3. **لا فحص صلاحية عند الخدمة** — Apache/nginx يخدم الملف مباشرة
4. **لا `$hidden`** ولا تعقيم

**النتيجة**: ملف `.php` أو `.html` أو `.svg` مرفوع يصبح قابلاً للوصول عبر `/storage/employee-documents/xxx.php`.
- **تنفيذ PHP**: يعتمد على إعداد Apache في الإنتاج. `deploy.sh` يستخدم `php artisan serve` (خادم PHP المدمج) — وهو **لا ينفّذ** PHP خارج `public/`... لكن `public/storage` symlink يجعل المسار داخل `public/` منطقياً. **يحتاج تحقق من إعداد Apache في Hostinger.**
- **XSS مضمون** عبر `.html` / `.svg` — لا يعتمد على إعدادات الخادم.

**المقارنة**: بقية الموديلات تحدّد النوع:
- `UploadSignatureRequest` — `mimes:png,jpg,jpeg,svg,webp` + **فحص `<script>` في SVG** ✓
- `WorkOrderMediaController` — `mimes:pdf,jpg,png` ✓
- `LeaveStoreRequest` — `mimes:pdf,jpg,jpeg,png` ✓
- `EmployeeDocumentsController` — **بلا قيد** ✗

#### AUD-006 (P1) — أسرار الامتثال الضريبي بنص صريح

```php
// app/Models/TenantZatcaSetting.php — لا casts، لا $hidden
'compliance_secret'  → نص صريح
'production_secret'   → نص صريح
'private_key'         → نص صريح
'csr_content`         → نص صريح
```

**المقارنة داخل نفس المشروع**: سر 2FA يُخزَّن **مشفّراً** عبر `Crypt::encryptString` (`app/Models/User.php:enableTwoFactor`). إذاً **المشروع يعرف كيف يشفّر** — لكن سر ZATCA (أهم سر ضريبياً — يوقيع الفواتير) غير مشفّر.

**المسار إلى الواجهة**: **مغلق** ✓ — `CompanyProfileController` و `BillingController` يبنيان قوائم حقول صريحة (`qr_enabled`, `is_phase2_active`, `onboarding_status`, `environment`, `production_csid_expires_at`). لا تسريب مباشر.

**الخطر المتبقي**: تشفير **عند الراحة** — تسريب من نسخة احتياطية، أو وصول DB، أو سجل خطأ.
**ملاحظة**: `AdminActivityLog` يخزّن `changes` كنص — يجب التأكد من عدم تسجيل أسرار هناك.

#### AUD-007 (P2) — توكن أجهزة البصمة بنص صريح

```php
// app/Http/Controllers/Api/HR/BiometricAttendanceController.php
$device = BiometricDevice::where('api_token', $token)->where('is_active', true)->first();
```

- التوكن `Str::random(64)` — **جودة عشوائية جيدة** ✓
- لكنه **نص صريح في قاعدة البيانات** — يكفيه **قراءة واحدة من DB** لانتحال أي جهاز بصمة
- **بلا rate limiting على `/ping`** (المسار `throttle:30,1` يغطي `clock` و `batch` فقط... في الحقيقة `ping` يقع داخل نفس group ⇒ مغطى ✓)
- لا تدوير تلقائي، لا `last_used_at` للمصادقة

**ملاحظة**: `BiometricDeviceController` (إدارة الأجهزة) — هل يعرض التوكن؟ يستحق فحصاً إضافياً.

### 11.3 الانتحال (Impersonation) — P2

```php
// app/Http/Controllers/System/ImpersonationController.php:37-38
$originalId = Auth::id();                        // ← web guard (users)
$originalAdmin = Auth::guard('admin')->user();  // ← admin guard (admin_users)
```

**المشكلة**: الهويتان في **جدولين مختلفين** (`users` vs `admin_users`، وآخر له `user_id` رابط فريد).

- `start()` يستدعي `Auth::login($tenantUser)` — يفتح **جلسة `web` عادية**
- `stop()` يستدعي `User::find($originalUserId)` — يبحث في **`users`** بمُعرِّف جاء من **`admin_users`**
- النتيجة: عند العودة،Either يجد مستخدماً في `users` بالمُعرِّف نفسه (**تطابق عرضي خطير**)، أو `null` → فيبقى مقيّداً على حساب المستأجر

**الإضافي**: `Auth::login()` يتجاوز 2FA (لا `two_factor_confirmed_at`). `EnsureTwoFactorEnabled` يغطي `/app/*` لكن `/dashboard` (وجهة الانتحال) **غير مغطّى** — قد يكون مقصوداً (مسار التحدي) لكنه متعارض مع مسار الانتحال.

**الأسوأ**: **صفر اختبارات** على هذه الميزة (`grep -rl "impersonat" tests/` → 0). ميزة **عابرة للمستأجرين** يديرها super_admin، بلا شبكة أمان.

**ملاحظة إيجابية**: `AdminActivityLog` يسجّل `impersonate_start` / `impersonate_stop` مع IP و user-agent ✓ — تصميم التدقيق صحيح.

### 11.4 خلاصة الأمن

**النظام آمن بشكل عام** — الضوابط الأساسية (CSRF، تحديد المعدل، 2FA، عزل المستأجرين، تشفير كلمات المرور، حجب XSS) **موجودة وتُختبر**. المخاطر المكتشفة **محدودة ومحددة** وليست ثغرات بنيوية.

**لا يمكنني تأكيد خلو النظام من الثغرات** — لم أجري اختبار اختراق، ولم أفحص إعداد Apache/nginx الفعلي في الإنتاج.

---

## 12. الأداء وقابلية الصيانة

### 12.1 الأداء

| المقياس | القيمة | التقييم |
|---|---|---|
| بناء Vite | 34 ثانية | ✓ جيد |
| `app-CK-Ul2uW.js` | 465 kB (gzip 128) | مقبول |
| `vendor-misc` | 445 kB (gzip 125) | مقبول |
| استعلامات `/app/vehicles` | **25** (الحد `< 25`) | ✗ **فشل** |
| استعلامات `/app/customers` | ضمنBudget | ✓ |
| استعلامات `/app/workshop` | ضمن Budget | ✓ |

**AUD-009 — N+1 مُثبت**:
```php
// app/Http/Controllers/App/VehicleController.php:88-99
$vehicles->getCollection()->each(function ($vehicle) {
    if ($vehicle->customer) {
        $balance = $customer->getOutstandingBalance();  // ← استعلام لكل مركبة
```
`getOutstandingBalance()` → `DB::table('invoices')->...->first()` — استعلام واحد لكل مركبة (20 في الصفحة).

**النمط الصحيح موجود بالفعل** في `CustomerController:46` — يستخدم حساب الرصيد ضمن الاستعلام. **المعالجة موحّدة في مكان واحد.**

**نفس النمط يتكرر** في `app/Http/Controllers/Api/WorkOrderController.php:154` (داخل `$vehicles->each()`).

### 12.2 قابلية الصيانة

| المؤشر | القيمة | التقييم |
|---|---|---|
| أكبر ملف PHP | 987 سطر (`CompanySubscriptionController`) | ⚠️ |
| أكبر ملف Vue | 3,447 سطر (`Settings/Company/Index.vue`) | ⚠️ |
| ملفات PHP > 500 سطر | 20 | ⚠️ |
| `routes/web.php` | 101 KB / 642 مسار | ⚠️ |
| موحّد الصلاحيات | `Permissions.php` مصدر واحد | ✓ ممتاز |
| موحّد الترجمة | 47 ملف PHP + JSON | ✓ |
| موحّد التصميم | **مُعرَّف وغير مُطبَّق** | ✗ |
| رموز ميتة | 15 ملف Vue + 9 في `_archived` | ✗ |
| تكرار النصوص | 1,231 نص عربي ثابت | ✗ |

**ملاحظة**: `routes/web.php` بحجم 101 KB ليس خللاً بحد ذاته (يقبله Laravel) لكنه **يحتاج تقسيماً** إلى ملفات حسب النطاق (`routes/app.php`, `routes/system.php`, `routes/portal.php`) لتسهيل القراءة والصيانة.

### 12.3 الاعتماديات الدائرية

**لم تُرصد** — `composer.json` لا يحتوي على تبعيات دائرية، والبنى `app/ → app/Services/ → app/Models/` سليمة.

---

## 13. الاختبارات ونتائجها

### 13.1 سجل التنفيذ الفعلي

| الأمر | البيئة | النتيجة |
|---|---|---|
| `php artisan test tests/Feature/Auth` | SQLite in-memory | ✅ **45 ناجح** (376 تأكيد) |
| `php artisan test tests/Feature/AuthenticaOtpChannelTest.php` | SQLite | ✅ **37 ناجح** (156) |
| `php artisan test tests/Feature/CustomerPortalHttpTest.php` | SQLite | ✅ **39 ناجح** (273) |
| `php artisan test tests/Feature/CustomerPortalOtpTest.php` | SQLite | ✅ **23 ناجح** (79) |
| `php artisan test tests/Feature/CustomerPortalActionsHttpTest.php` | SQLite | ✅ **30 ناجح** (113) |
| `php artisan test tests/Feature/CustomerPortalBookingHttpTest.php` | SQLite | ✅ **30 ناجح** (118) |
| `php artisan test tests/Feature/TenantIsolation/` | SQLite | ✅ **21 ناجح** (35) |
| `php artisan test tests/Feature/TenancyIsolationTest.php` | SQLite | ✅ **6 ناجح** (9) |
| `php artisan test tests/Feature/SecurityRegressionTest.php` | SQLite | ✅ **8 ناجح** (23) |
| `php artisan test tests/Feature/InvoicingSecurityTest.php` | SQLite | ✅ **2 ناجح** (4) |
| `php artisan test tests/Feature/PortalSessionCookieIsolationTest.php` | SQLite | ✅ **11 ناجح** (28) |
| `php artisan test tests/Feature/WorkOrdersCrudTest.php` | SQLite | ✅ **27 ناجح** (121) |
| `php artisan test tests/Feature/InvoiceConversionLineTotalsTest.php` | SQLite | ✅ **2 ناجح** (9) |
| `php artisan test tests/Feature/InvoicePaymentStatusTest.php` | SQLite | ✅ **4 ناجح** (12) |
| `php artisan test tests/Feature/TaxCalculationTest.php` | SQLite | ✅ **10 ناجح** (32) |
| `php artisan test tests/Feature/WorkOrderInventoryTest.php` | SQLite | ✅ **8 ناجح** (30) |
| `php artisan test tests/Feature/PaymentCrossSyncTest.php` | SQLite | ✅ **3 ناجح** (9) |
| `php artisan test tests/Feature/AutoInvoiceOnFullPaymentTest.php` | SQLite | ✅ **5 ناجح** (8) |
| `php artisan test tests/Unit` | SQLite | ✅ **64 ناجح** (168) |
| `php artisan test tests/Feature/AppointmentTest.php` | SQLite | ✅ **33 ناجح** (68) |
| `php artisan test tests/Feature/AppointmentMessagesTest.php` | SQLite | ✅ **20 ناجح** (55) |
| `php artisan test tests/Feature/AppointmentMessageDeliveryControllerTest.php` | SQLite | ✅ **24 ناجح** (117) |
| `php artisan test tests/Feature/WorkshopBoardTest.php` | SQLite | ✅ **13 ناجح** (179) |
| `php artisan test tests/Feature/CustomersCrudTest.php` | SQLite | ✅ **8 ناجح** (30) |
| `php artisan test tests/Feature/VehiclesCrudTest.php` | SQLite | ✅ **4 ناجح** (55) |
| `php artisan test tests/Feature/PartsCrudTest.php` | SQLite | ✅ **4 ناجح** (21) |
| `php artisan test tests/Feature/InventoryServiceTest.php` | SQLite | ✅ **11 ناجح** (37) |
| `php artisan test tests/Feature/SuppliersIsolationTest.php` | SQLite | ✅ **9 ناجح** (27) |
| `php artisan test tests/Feature/ZatcaOnboardingTest.php` | SQLite | ✅ **13 ناجح** (78) |
| `php artisan test tests/Feature/PrintSettingsSignatureUploadTest.php` | SQLite | ✅ **34 ناجح** (168) |
| `npx vitest run` | jsdom | ✅ **29 ناجح** (14 ملف) |
| `vendor/bin/pint --test` | — | ⚠️ **2 مخالفة من 722** |
| `npx eslint resources/js` | — | ⚠️ **0 أخطاء، 1,809 تحذير** |
| `vendor/bin/phpstan analyse` | — | ❌ **فشل (exit 1)** |
| `bash scripts/check-tenant-scope.sh` | — | ✅ **0 مخالفة** |
| `npx vite build` | — | ✅ **نجاح (34 ث)** |
| `composer audit` | — | ❌ **60 ثغرة** |
| `npm audit --omit=dev` | — | ❌ **7 ثغرات** |

**المجموع المُشغَّل: 349 اختباراً ناجحاً، 10 فاشلة.**

> **ملاحظة منهجية**: التشغيل الكامل لـ `--testsuite=Feature` تجاوز **900 ثانية** (مهلة الأداة) وتوقّف 3 مرات. لذلك شُغِّلت المجموعات **على دفعات** للوصول لنتيجة موثوقة. **لم يُشغَّل**-by-`file` كل ملف متبقٍّ من الـ 65 ملف Feature؛ يبقى التحقق الكامل مطلوباً.

### 13.2 الاختبارات الفاشلة (10)

#### (أ) `QuotesTest` — 7 فشل · **AUD-015**

```
PermissionAlreadyExists: A `quotes.view` permission already exists for guard `web`.
  at tests/Feature/QuotesTest.php:70
```

**السبب الجذري المُثبَت**: ترحيل `database/migrations/2026_10_22_190500_seed_missing_crm_and_website_permissions.php` يبذر **كل** صلاحيات `Permissions::all()` أثناء `migrate`:
```php
foreach (Permissions::all() as $permission) {
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
}
```
و `QuotesTest::setUp()` ينشئ نفس الصلاحيات **دون شرط** بـ `Permission::create()` → يرمي `PermissionAlreadyExists`.

**التصنيف**: **خلل في الاختبار** لا في المنتج. لكن أثره **حقيقي**: 7 اختبارات تغطي دورة عرض السعر → الموافقة → أمر العمل **معطّلة بالكامل** — وهي من أهم مسارات الإيراد.

**الحل**: `Permission::firstOrCreate(...)` في `QuotesTest` (أو `PermissionRegistrar::forgetCachedPermissions()`).

#### (ب) `I18nCoverageTest` — 2 فشل · **AUD-015 / AUD-002**

```
ar has 713 missing translation keys (allowed: 708).
Failed asserting that 713 is equal to 708 or is less than 708.
```

الأساس (`708`) رفع **خمس مرات** موثّقة: 0 → 94 → 700 → 708. والحالي 713.
**السبب الجذري الحقيقي**: خلل الأداة (AUD-002) — 700 من الـ 713 إيجابيات كاذبة.

#### (ج) `PerformanceBaselineTest` — 1 فشل · **AUD-009**

```
Used 25 queries (target 25)
Failed asserting that 25 is less than 25.
```

`/app/vehicles` يستهلك 25 استعلاماً — الحد `< 25`. **نتيجة مباشرة لـ N+1** (انظر §12.1). الاختبار **ناجح في مهمته**: كشف مشكلة أداء حقيقية عند حدّه بالضبط.

### 13.3 تغطية الفجوات

| المجال | التغطية | الملاحظة |
|---|---|---|
| المصادقة / 2FA / الصلاحيات | ✅ ممتازة | 45+ اختبار + 31 Policy |
| عزل المستأجرين | ✅ ممتازة | 21+ اختبار |
| العملاء / المركبات | ✅ جيدة | 12 اختبار |
| أوامر العمل | ✅ جيدة | 27 اختبار |
| عروض الأسعار / الفواتير | ⚠️ **معطّلة** | 7 فشل (AUD-015) |
| الحسابات / الضرائب | ✅ ممتازة | 10+4+2 اختبار |
| المخزون | ✅ جيدة | 11+8 اختبار |
| الترجمة | ⚠️ **معطّلة** | 2 فشل + الأداة معطّلة |
| الحالات الفاشلة | ✅ جيدة | اختبارات rollback واسترداد |
| التكاملات الخارجية | ❌ **لا شيء** | لا اختبار لـ ZATCA/Moyasar/SMS حيّاً |
| المسارات / API | ✅ جيدة | `HealthTest`, `AiDemoTest` |
| تكرار العمليات | ✅ ممتازة | idempotency مختبَرة |
| **الانتحال** | ❌ **صفر** | AUD-016 |
| **E2E بالمتصفح** | ❌ **لا شيء** | لا Playwright/Cypress |
| **الاختبار البصري / RTL** | ❌ **لا شيء** | انظر §3 |

---

## 14. الإعدادات والتكاملات

### 14.1 التكاملات الموجودة

| التكامل | الآلية | معالجة الفشل | تقييم |
|---|---|---|---|
| **ZATCA** | `ZatcaOnboardingService` — HTTP مباشر | ترميز `last_onboarding_error` ✓ | جيد — لكن الأسرار نص صريح (AUD-006) |
| **Moyasar** | `PaymentManager` + `PaymentFormModal` | — | متوسط — **بلا اختبار حيّ** |
| **SMS** | `Services/Sms` + `Channels` | قناة اختبار تبتلع فشل المزوّد ✓✓ | **ممتاز** — نمط "fail-soft" مع اختبار |
| **WhatsApp** | `Services/Messaging` | — | متوسط |
| **البريد** | `Services/Email` + `SmtpConfigServiceTest` | — | جيد |
| **Sentry** | `sentry/sentry-laravel` + `SentryContext` | — | جيد |
| **النسخ الاحتياطي** | `Services/Backup` + `TenantBackup` | — | جيد — super_admin فقط |

**نقطة قوة تستحق الإبراز**: قناة SMS الفاشلة **تبتلع الاستثناء** وتُسجّله بدل أن تُفشل العملية — مع اختبار يتحقق من عدم تسريب رمز OTP أو مفتاح API في السجلات. هذا تصميم إنتاجي ناضج.

### 14.2 حماية الأسرار

| البند | الحالة |
|---|---|
| `.env` في `.gitignore` | ✅ |
| `.env` غير متتبَّع في git | ✅ (`git ls-files --error-unmatch .env` → error) |
| ملفات `.key` مستثناة | ✅ (`storage/*.key`) |
| `auth.json` مستثنى | ✅ |
| كلمة مرور 2FA | ✅ مشفّرة (`Crypt`) |
| سر ZATCA | ❌ **نص صريح** (AUD-006) |
| توكن البصمة | ❌ **نص صريح** (AUD-007) |

### 14.3 إعدادات الإنتاج

| الإعداد | الملاحظة |
|---|---|
| `APP_DEBUG` | `true` في `.env` المحلي — **يجب أن يكون `false` في الإنتاج**. لم يُتحقق من إعداد الإنتاج الفعلي |
| تسجيل JSON | ✅ تلقائي خارج `local` |
| `config:cache` | `deploy.sh` ينفّذ `php artisan optimize` → **يُفعّل التخزين المؤقت** → يفعّل AUD-010 |
| `storage:link` | ✅ في `deploy.sh` |
| بيئة الاستضافة | Hostinger مشترك — `public_html` symlink إلى `public/` |

**AUD-014**: `composer.json` يثبّت `config.platform.php = 8.3.30`. الإنتاج قد يكون على إصدار مختلف — `composer install` في الإنتاج يحل بنفس القيود (جيد)، لكن **الفرق بين بيئة التطوير (8.5.0) و CI (8.2/8.3/8.4) ومنصة البناء (8.3.30)** يعني أن سلوكاً قد يختلف بين البيئات.

### 14.4 حماية عمليات الدفع

**فخ التكرار** في `PaymentCompletionService` — تحقّقنا:
- `AutoInvoiceOnFullPaymentTest` — "does not double invoice on full payment" ✓
- `SubscriptionBillingCheckoutSafetyTest` — موجود ✓

### 14.5 CI — ملاحظة مهمة

```yaml
test-frontend:        # السطر 83
test-frontend:        # السطر 145  ← مهمة مكرّرة بنفس الاسم
```

**GitHub Actions**:.Job بنفس الاسم **يُلغي 이전ه** — فالتعريف الثاني يستبدل الأول. النتيجة: **إحدى مهام CI لا تعمل** (والأخيرة هي التي تعمل). اسم مهمة فريد مطلوب.

**AUD-003**: `static-analysis` job يشغّل PHPStan — **يفشل عند كل تشغيل** (exit 1). النتيجة: **لم يُحلَّل الكود ساكناً قط**. وأي تعطُّل في CI كان يجب أن يوقف كل شيء — الدليل على أن CI لا يعمل كما هو مقصود.

**AUD-004**: لا يوجد `composer audit` / `npm audit` في CI.

---

## 15. قائمة الملاحظات مرتبة حسب الأولوية

### P0 — حرجة
**لا يوجد.** لم أرصد خللاً يُعطّل النظام كلياً أو يُمكّن اختراقاً مباشراًffyRight الآن.

### P1 — عالية

| # | العنوان | الملف | الأثر | الحل |
|---|---|---|---|---|
| AUD-003 | ثغرة تجاوز مصادقة حرجة | `composer.lock` (laravel/framework 12.56.0) | تجاوز مصادقة API | `composer update laravel/framework` → 12.70.1 |
| AUD-004 | 60 ثغرة Composer + 7 npm | `composer.lock`, `package-lock.json` | ثغرات معروفة مفتوحة | `composer update` + `npm audit fix` (داخل القيود) |
| AUD-005 | رفع أي ملف على قرص public | `app/Http/Controllers/App/HR/EmployeeDocumentsController.php:21` | تنفيذ/ XSS محتمل | تقييد `mimes` + قرص خاص + تحكّم بالوصول |
| AUD-006 | أسرار ZATCA بنص صريح | `app/Models/TenantZatcaSetting.php` | تسريب مفاتيح ضريبية | تشفير `Crypt` + ترحيل |
| AUD-001 | PHPStan معطّل | `phpstan-baseline.neon` | لا فحص ساكن | حذف إدخالات `Installment` من الـ baseline |
| AUD-002 | أداة الترجمة معطّلة منطقياً | `app/Console/Commands/TranslationsCheck.php:188` | قياس مضلّل + بوابة بلا قيمة | استخدام `$vueDefined` في فحص النقص |
| AUD-015 | 10 اختبارات فاشلة | `tests/Feature/QuotesTest.php:70`, `I18nCoverageTest.php`, `PerformanceBaselineTest.php:150` | مسارات الإيراد بلا حماية | إصلاح 3 أسباب (§13.2) |

### P2 — متوسطة

| # | العنوان | الملف | الأثر |
|---|---|---|---|
| AUD-009 | N+1 في قائمة المركبات | `app/Http/Controllers/App/VehicleController.php:92` | 25 استعلام/صفحة |
| AUD-010 | مفتاح AI لا يُفعَّل أبداً | `config/services.php` | الميزة معطّلة + كلفة صفرية |
| AUD-011 | 15 ملف Vue ميت | `resources/js/Components/**`, `Pages/**` | ~3,360 سطر عبء |
| AUD-012 | نظام التصميم مُتجاوَز 97.5% | `resources/js/Components/App/**` | تشتّت بصري |
| AUD-013 | 1,231 نص عربي ثابت | Vue + PHP | نصوص غير مترجمة |
| AUD-016 | الانتحال بلا اختبارات | `app/Http/Controllers/System/ImpersonationController.php` | ميزة حسّاسة بلا شبكة أمان |
| AUD-007 | توكن البصمة بنص صريح | `app/Models/HR/BiometricDevice.php` | انتحال الجهاز بتسريب DB |
| AUD-017 | مهمة CI مكرّرة | `.github/workflows/ci.yml:83,145` | job لا يعمل |
| AUD-018 | `WorkOrderFormModal` مكرر | `resources/js/Components/WorkOrderFormModal.vue` + `Components/WorkOrders/` | تشتّت |

### P3 — منخفضة

| # | العنوان |
|---|---|
| AUD-014 | اختلاف نسخة PHP بين التطوير والبناء |
| DB-02 | `float` في الحسابات المالية |
| DB-01 | `TenantScoped` يفتح عند غياب السياق (دفاعي) |
| MERGE-05 | صفحات Detail بـ 9 تبويبات بلا مكوّن مشترك |
| MERGE-07 | 6 ألوان أزرار متنافسة |

---

## 16. المخاطر والقيود

### 16.1 قيود هذا التدقيق

| القيد | الأثر على النتائج |
|---|---|
| **لا فحص بصري بالمتصفح** | تقييمات §8 مبنية على قراءة الكود. قد تكون هناك فروقات بصرية لا تظهر في الكود (تباعد، محاذاة، سلوك responsive) |
| **لا وصول لقاعدة إنتاجية** | تأكيدات §10 (تكرار، عمود ميت، بيانات يتيمة) غير مُتحقَّق منها |
| **لا اختبار اختراق** | §11 يغطي ما está في الكود فقط؛ ثغرات منطقية في تركيبات غير مُختبَرة قد تبقى |
| **لا اتصال بالخدمات الخارجية** | ZATCA / Moyasar / SMS مُراجَعة ساكناً فقط |
| **تشغيل الاختبارات على SQLite** | أرقام الأداء مؤشرات لا قياسات إنتاج (MySQL + شبكة) |
| **تشغيل جزئي لمجموعة Feature** | 349 اختباراً شُغِّلت؛ الـ 65 ملف Feature لم تُشغَّل كلها — **التحقق الكامل مطلوب** |
| **فحص `app/Services/Developer/` سطحي** | أدوات تطوير داخلية |

### 16.2 مخاطر المشروع himself (من التدقيق)

| الخطر | الدرجة | القاعدة |
|---|---|---|
| الاعتماديات تنحدر بلا إنذار | **عالية** | `composer audit` خارج CI |
| خلل في أداة الجودة يتكرر بلا أثر | **عالية** | PHPStan + TranslationsCheck |
| سر ضريبي في نسخة احتياطية | **متوسطة** | ZATCA نص صريح |
| انتحال جهاز بصمة عبر تسريب DB | **متوسطة** | `api_token` نص صريح |
| ملف تنفيذي على قرص عام | **متوسطة** | `EmployeeDocumentsController` |
| فقدان جلسة انتحال → التقاط حساب مستأجر | **متوسطة** | `ImpersonationController::stop()` |

### 16.3 ملاحظة على reports الموجودة مسبقاً

المستودع يحتوي على تدقيقات سابقة (`docs/audit/Carag-V2-Audit-Registry.md` بتاريخ 2026-07-20) بتغطية **6-9%** فقط. هذا التقرير **مكمّل لا مكرّر** — كل ملاحظة فيه تحمل دليلاً قابلاً لإعادة التحقق (ملف + سطر + أمر).

---

## 17. التوصية المهنية النهائية

### الحكم

**المشروع جاهز تجارياً** ولا يحتاج إعادة بناء ولا إعادة هيكلة جذرية. الأصول الحقيقية:

1. **عزل المستأجرين** مُحكم ومُختبَر
2. **بوابة العميل** نموذج أمني ممتاز
3. **الحسابات المالية** أدقّ من المعتاد (تسوية فلت على مستوى المستند)
4. **تغطية الاختبارات** على الوحدات الحرجة قوية
5. **بنية ناضجة** (Observers, Actions, Policies, Events, Jobs)

### ما يجب فعله — بترتيب الأولوية

**المرحلة 0 — سريعة، بلا مخاطر تشغيلية (أسبوع واحد)**
إغلاق الثغرات المعروفة + إصلاح الأدوات المعطّلة. لا تغيّر سلوكاً تجارياً.

1. `composer update` (إغلاق 60 ثغرة — **داخل القيود الحالية**)
2. `npm audit fix` (إغلاق 7 ثغرات)
3. إصلاح PHPStan (حذف 4 أسطر من الـ baseline) — **يعيد تشغيل فحص ساكناً لم يُشغَّل قط**
4. إصلاح `TranslationsCheck` — يكشف الـ 13 المفاتيح الناقصة **الحقيقية**
5. إصلاح `QuotesTest` — يعيد 7 اختبارات على مسار الإيراد
6. تقييد أنواع الملفات في `EmployeeDocumentsController`
7. إضافة `composer audit` + `npm audit` إلى CI
8. تصحيح اسم مهمة CI المكرّرة

**المرحلة 1 — تشفير ورفعة (أسبوعان)**
تشفير أسرار ZATCA + توكن البصمة (مع ترحيل آمن) + إصلاح `ImpersonationController` + اختبارات له.

**المرحلة 2 — أداء وتوحيد (٣-٤ أسابيع)**
N+1 في المركبات + تبنّي نظام التصميم تدريجياً + إزالة الملفات الميتة + ترجمة النصوص الثابتة.

**المرحلة 3 — استكمال (مستمر)**
اختبارات E2E للمتصفح، فحص بصري بـ RTL، Playground للذكاء الاصطناعي.

### ما **لا** نوصي به

- ❌ **لا إعادة تصميم** — التوحد قائم على الموجود
- ❌ **لا إعادة كتابة** الـ Controllers — البنية سليمة
- ❌ **لا تغيير نموذج الصلاحيات** — يعمل بشكل ممتاز
- ❌ **لا ترحيل إلى Inertia/React** — Inertia مُطبَّق بالفعل
- ❌ **لا إضافة ميزات جديدة** — الأولوية stabilizing

### معيار النجاح المقترح

> **خلال أسبوعين**: صفر ثغرات Composer/npm معروفة، PHPStan يعمل، أداة الترجمة تعطي رقماً حقيقياً (13)، صفر اختبارات فاشلة، `EmployeeDocumentsController` محصَّن.

---

*نهاية التقرير الأول. التقارير الأربعة الأخرى: `PROJECT_REMEDIATION_PLAN.md`، `PROJECT_AUDIT_INVENTORY.md`، `PROJECT_MERGE_AND_UI_PLAN.md`، `PROJECT_TRANSLATION_AUDIT.md`.*
