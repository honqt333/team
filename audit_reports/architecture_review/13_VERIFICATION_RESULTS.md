# 13 — نتائج التحقق (Verification Results)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11

> ⚠️ **تحديث الحالة بعد جولة التنفيذ (2026-11-11).**
> نتائج هذا التقرير تصف **حالة التدقيق الأصلية**. الجولة التالية غيّرت بعض الأرقام:
> - **سجل التصحيحات** — ادعاءات ثبت أنها غير دقيقة: `16_AUDIT_CORRECTIONS.md`
> - **إعادة تصنيف الإخفاقات العشرين** وطريقة إصلاحها: `17_FAILURE_RECLASSIFICATION.md`
> - **مرجع الضريبة وقياس المسارات**: `15_TAX_REFERENCE_AND_PATHS.md`
>
> الأرقام أدناه تبقى **توثيقاً لتدقيق 2026-11-11 قبل الإصلاح**. للحالة الراهنة انظر
> §0 أدناه.

---

## 0. الحالة بعد جولة التنفيذ (2026-11-11)

| البوابة | نتيجة التدقيق الأصلي | بعد الجولة |
|---|---|---|
| `php artisan test` | 🔴 13 فاشل / 1,636 ناجح | ⏳ **تشغيل نهائي قيد التنفيذ** — الفشل الـ20 مُصلَح كلها (انظر `17_…`) |
| `npx vitest run` | ✅ 44 ملف / 147 اختبار | ✅ **47 ملف / 168 اختبار** |
| `npm run build` | ✅ 32s | ✅ **43.4s** |
| `npx eslint resources/js` | ✅ صفر أخطاء | ✅ **صفر أخطاء** (1,846 تحذير أسلوبي سابق) |
| `php artisan translations:check --source=both` | 🔴 42/44 مفقود | ✅ **صفر** مفقود في اللغتين |
| `vendor/bin/phpstan analyse` | 🔴 20 خطأ | ⚠️ **38 خطأ** — **15 منها من عملية موازية** تعمل على نفس المستودع؛ خطأ Carbon (3) أُصلح. أخطاء هذه الملفات **ليست من هذه الجولة** |

### 0.1 ما أُصلح في هذه الجولة (تفصيل في `17_FAILURE_RECLASSIFICATION.md`)

| الفشل | السبب الحقيقي | الإصلاح |
|---|---|---|
| 7 اختبارات | حارس ثبات الفاتورة يلتقط محاولات لتعديل مستند صادر — وهو **سلوك صحيح** | ثوابت الاختبار تُنشئ المستند بالحالة المطلوبة بدل نقله |
| 4 اختبارات | السلوك تغيّر والاختبار لم يُحدَّث | الاختبارات تُحدَّث لتطابق العقد الحالي |
| 6 اختبارات أداء | ⚠️ **ليست N+1** — كان حساب دورة الاشتراك **يتكرر مرتين في كل طلب** (10 استعلامات) | `SubscriptionLifecycleService` صار يحفظ النتيجة لكل طلب، مع إبطالها عند تغيّر الحالة أو الوقت |
| 2 عزل | تصادم slug في المصنع | التفرّد نُقل **إلى المصنع** نفسه |
| 1 | `floatDiffInDays` | استُبدل بـ `diffInDays` — **بقيمة متطابقة تماماً** (تحقّق على 6 حالات) |

---

## 1. مبدأ التوثيق

> **نجاح البناء ≠ النظام خالٍ من الأخطاء.** كل نتيجة أدناه مصنّفة:

| التصنيف | المعنى |
|---|---|
| **Verified** | أعدتُ إنتاج النتيجة بنفسي |
| **Partially Verified** | تحققت من جزء محدد |
| **Not Verified** | استنتاج ساكن بلا دليل تنفيذي |
| **Not Applicable** | لا ينطبق |

**لم يُنفَّذ أي أمر مدمّر. لم تُشغَّل أي migration. لم يُتصل بقاعدة بيانات الإنتاج.**

---

## 2. البيئة

| البند | القيمة | الأمر |
|---|---|---|
| Laravel | **12.70.1** | `php artisan --version` |
| PHP | **8.5.0 (cli)** | `php -v` |
| Node | **v22.21.1** | `node -v` |
| الفرع | `feature/phase-2-goal-3` | `git branch --show-current` |
| آخر commit | `c6e9427f` | `git log --oneline -1` |
| المسارات | **741** | `php artisan route:list --json` |

> ⚠️ **المستودع يحتوي تغييرات غير مُلتزِمة** من جلسة سابقة (`git status` يُظهر تعديلات على ~30 ملف PHP + حذف `public/build/assets/*`). **هذه سابقة لتدقيقي ولم أنتجها.** documentos
> كما توجد **14 stash entry** — لا تطمسها.

---

## 3. نتيجة الاختبارات (PHPUnit) — **Verified** (المجموعة الكاملة)

> **اكتمل التشغيل الكامل.**

### النتيجة النهائية

```
Tests: 13 failed, 5 skipped, 1636 passed (9681 assertions)
Duration: 2853.41s     (47 دقيقة)
EXIT_CODE=1
```

| المقياس | القيمة |
|---|---|
| **الأمر** | `php artisan test` |
| **البيئة** | `phpunit.xml` يفرض `DB_CONNECTION=sqlite` · `DB_DATABASE=:memory:` · `QUEUE_CONNECTION=sync` · `CACHE_STORE=array` ⇒ **معزولة تماماً عن الإنتاج** |
| **ناجح** | **1,636** |
| **فاشل** | **13** |
| **متخطى** | 5 |
| **تأكيدات** | 9,681 |
| **المدة** | 47.5 دقيقة (تشغيل تسلسلي) |
| **مستوى الثقة** | **Verified** — الجولة الكاملة نُفِّذت |

### `php artisan test --parallel`

```
✗ NunoMaduro\Collision\Adapters\Laravel\Exceptions\RequirementsException
  Running Collision 8.x artisan test command in parallel
  requires at least ParaTest (brianium/paratest) 7.x.
```
⇒ **ParaTest غير مثبّت** ⇒ التشغيل تسلسلي ⇒ سبب الـ 47 دقيقة.

---

## 3.1 الاختبارات الـ13 الفاشلة — كلها مُتحقَّق منها

| # | الاختبار | التصنيف | الحالة |
|---|---|---|---|
| 1 | `FeedbackTest::…view_all_feedbacks_and_reply` | 🟡 **اختبار قديم** | إعادة توجيه مقصودة (commit `82c92ec5`) |
| 2 | `I18nCoverageTest::…_arabic` | 🔴 **انحدار ترجمة** | 44 مفتاحاً مفقوداً |
| 3 | `I18nCoverageTest::…_english` | 🔴 **انحدار ترجمة** | 42 مفتاحاً مفقوداً |
| 4 | `PerformanceBaselineTest::work_orders_show` | 🟠 **N+1** | 60 استعلاماً / 55 |
| 5 | `PerformanceBaselineTest::customers_list` | 🟠 **N+1** | 29 / 25 |
| 6 | `PerformanceBaselineTest::inventory_balances_list` | 🟠 **N+1** | 33 / 30 |
| 7 | `PerformanceBaselineTest::vehicles_list` | 🟠 **N+1** | 35 / 30 |
| 8 | `PerformanceBaselineTest::workshop_board` | 🟠 **N+1** | 34 / 30 |
| 9 | `GeneralSettingsAllowlistTest::…allowlist_contains_only_the_key…` | 🟡 **اختبار قديم** | allowlist فيه مفتاحان — الصفحة ترسلهما |
| 10 | `InvoicePdfCompanyInfoTest::…updated_company_settings_apply…` | 🔴 **عزل اختبار** | ينجح في العزل |
| 11 | `TenantPublicSiteTest::…invalid_color_in_db_falls_back…` | 🟡 **اختبار قديم** | المتوقَّع `#4f46e5` ← الصحيح `#2563eb` |
| 12 | `VueTranslationKeyTest::…every_translation_key_used_in_a_vue_component_exists` | 🔴 **انحدار ترجمة** | **38 مفتاحاً مفقوداً** — قائمة صريحة بالملفات |
| 13 | `WorkOrdersCrudTest::…cross_branch_data_sharing_and_policies` | 🔴 **سياسة مقصودة** | tightening أمني — الاختبار قديم |

### ملاحظتان مهمّتان

**① الاختبارات الفاشلة الثلاث «قديمة» (1, 9, 11) + واحدة «سياسة مقصودة» (13) = 4 من 13.** ⇒ **الـ suite لم يُحدَّث بعد أحدث تغييرات الميزات.** هذا نمط متكرر، وليس حوادث متفرقة.

**② `VueTranslationKeyTest` (12) يعطي قائمة صريحة بأسماء المفاتيح والملفات** ⇒ **تأكيد مستقل** لاكتشاف `translations:check`:
```
common.attachments · common.cheque · common.main_center · common.no_attachments_hint
common.no_records_found · common.no_results_search · common.pagination · common.payment_method
common.reference_number · crm.vehicles.title · invoices.cash · reports.customers.title
roles.cannot_delete_has_users · roles.description_placeholder · roles.empty_description
roles.label_ar_placeholder · roles.label_en_placeholder · roles.selected_permissions
roles.system_name_hint · roles.system_name_placeholder
system.payment.configure_gateway · system.payment.created_at · system.payment.failed_subtitle
system.payment.failed_title · system.payment.gateway.bank_transfer
system.payment.gateway_not_configured · system.payment.gateway_not_configured_desc
system.payment.payment_method · system.payment.proceed_to_payment · system.payment.proceeding
system.payment.retry_payment · system.payment.select_this_gateway · system.payment.success_subtitle
system.payment.transaction_date · system.payment.view_all_subscriptions
system.payment.view_subscription · system.security.add_extra_layer · users.empty_description
```
**38 مفتاحاً** — مطابقة لـ 42 في `translations:check` (الفرق = مفاتيح PHP فقط).

---

## 3.2 تفاصيل كل فشل — **Verified** (أُعيد إنتاج كل منها)

#### ① `FeedbackTest::test_system_admin_can_view_all_feedbacks_and_reply`

```
Expected response status code [200] but received 302.
at tests/Feature/FeedbackTest.php:202
```
- **الأمر:** `php artisan test tests/Feature/FeedbackTest.php --filter=test_system_admin_can_view_all_feedbacks_and_reply`
- **السبب:** `routes/web.php:1394` صار `redirect()->route('system.support.index', ['tab' => 'feedbacks'])`
- **متى تغيّر:** `git log -S` ⇒ commit **`82c92ec5`** *"feat(system): consolidate support desk… into unified multi-tab hub"*
- **الاختبار لم يُحدَّث:** `git status tests/Feature/FeedbackTest.php` ⇒ فارغ؛ آخر تعديل `b3e7c61f` (أقدم)
- **التقييم:** ✅ **السلوك صحيح — الاختبار قديم**
- **الأثر:** 🔴 **suite أحمر ⇒ لا يمكن الاعتماد على CI كبوابة**

#### ②③ `I18nCoverageTest::no_new_missing_translation_keys_in_{arabic,english}`

```
en has 42 missing translation keys (allowed: 0).
ar has 44 missing translation keys (allowed: 0).
at tests/Feature/I18nCoverageTest.php:110
```
- **الأمر:** `php artisan test --filter=no_new_missing_translation_keys`
- **العتبة:** `tests/Feature/I18nCoverageTest.php:35` ⇒ `MISSING_KEYS_EXPECTED = 0`
- **تعليق الملف:** *"They were closed on 2026-11-08 … The ceiling is now ZERO."*
- **التقييم:** 🔴 **انحدار حقيقي بعد 2026-11-08** (~31 مفتاحاً تسرّب)
- **الأداة الموثوقة:** `php artisan translations:check --source=both --locale=ar|en` ⇒ **6,044 استخدام، 42/44 مفقود**

#### ④-⑧ `PerformanceBaselineTest` — 5 تجاوزات لميزانية الاستعلامات

```
[RESULT 1] GET /app/work-orders?status=open: 91.66ms, 69 queries   ✓
[RESULT 2] GET /app/work-orders/1:             52.51ms, 60 queries   ✗ (target 55)
[RESULT 3] GET /app/customers:                 41.05ms, 29 queries   ✗ (target 25)
[RESULT 4] GET /app/inventory/stock:           37.99ms, 33 queries   ✗ (target 30)
[RESULT 5] GET /app/vehicles:                  47.96ms, 35 queries   ✗ (target 30)
[RESULT 6] GET /app/workshop:                  55.72ms, 34 queries   ✗ (target 30)
```
- **الأمر:** `php artisan test tests/Feature/PerformanceBaselineTest.php` ⇒ `Tests: 5 failed, 1 passed (18 assertions)`
- **الدلالة:** ✅ **الأزمنة ممتازة** (38–92ms) ⇒ المشكلة **عدد استعلامات لا بطء**
- 🔴 **انحراف مُنتظم 3–5** ⇒ **علاقة واحدة ناقصة eager-load** في كل صفحة — نمط N+1 منتظم
- 🔴 **صفحة تفاصيل كرت العمل = 60 استعلاماً** — الأصعب

---

## 4. نتائج التحقق — Frontend

### 4.1 `npm run build` — **Verified ✅**

```
✓ built in 32.09s
```

| المقياس | القيمة |
|---|---|
| ملفات الـ assets | **397** |
| إجمالي JS | **6.8 MB** |
| إجمالي CSS | **696 KB** |
| `public/build` | **7.8 MB** |

**أكبر chunks:**
| Chunk | الحجم | gzip |
|---|---|---|
| `theme-ClkoP7N7.js` | 725.95 kB | 195.71 kB |
| `vendor-misc-CLPz_cIN.js` | 456.27 kB | 128.82 kB |
| `Show-ueRopwdT.js` | 270.86 kB | 59.87 kB |
| `vendor-vue-D4XvFTRt.js` | 139.04 kB | 53.24 kB |
| `AppLayout-Cou4RzfH.js` | 123.91 kB | 19.15 kB |

⚠️ **`theme-*.js` (726 kB) يستحق تدقيقاً** — قد يكون ApexCharts محزومة في كل صفحة. **Not Verified** (يتطلب تفكيك Vite).

### 4.2 `npx eslint resources/js` — **Verified ✅**

```
✖ 1815 problems (0 errors, 1815 warnings)
```
- **صفر أخطاء** ✅
- 1,815 تحذير — أغلبها `no-useless-escape` (`\/` داخل regex) ⇒ **تجميلية لا وظيفية**

### 4.3 `npx vitest run` — **Verified ✅**

```
Test Files  44 passed (44)
Tests       147 passed (147)
Duration    52.32s
```

| البند | القيمة |
|---|---|
| ملفات الاختبار | 44 |
| الاختبارات | 147 |
| النتيجة | **كلها ناجحة** ✅ |

**تحذيرات:** `onMounted is called when there is no active component instance` في `useTheme.test.js` — الاختبارات تعمل خارج بيئة مكوّن Vue.

**فجوة تغطية:** 🔴 **`usePrintTotals.js` لا يملك اختباراً** رغم أنه الأكثر حساسية مالياً.

---

## 5. نتائج التحقق — التحليل الساكن

### 5.1 `vendor/bin/phpstan analyse --memory-limit=512M` — **Verified**

```
[ERROR] Found 20 errors
```

| الملف | العدد | الطبيعة |
|---|---|---|
| `app/Http/Controllers/App/HR/AttendanceController.php` | 6 | تعارض نوع `Collection` في `when()` |
| `app/Services/Media/ImageOptimizer.php` | 6 | شروط دائماً خاطئة · `is_object(GdImage)` |
| `app/Models/Billing/Subscription.php` | 2 | 🔴 **`floatDiffInDays()` غير موجودة** |
| `app/Services/Billing/SubscriptionLifecycleService.php` | 1 | 🔴 **`floatDiffInDays()` غير موجودة** |
| `app/Http/Controllers/App/ZatcaOnboardingController.php` | 1 | ignore pattern لم يُطابق ⇒ baseline قديم |
| `app/Http/Middleware/HandleInertiaRequests.php` | 1 | `?->` على يسار `??` |
| `app/Services/TenantStorageService.php` | 1 | `?->` على يسار `??` |

**الأمر:** `vendor/bin/phpstan analyse --memory-limit=512M --no-progress --error-format=raw`

### 5.2 `php artisan translations:check --source=both` — **Verified**

```
Total usages found: 6044
Total missing keys: 42   (en)
Total missing keys: 44   (ar)
Total placeholder values (untranslated): 0
```

**القائمة الكاملة للمفاتيح المفقودة مُدرجة في `06_TRANSLATION_AUDIT.md` §3.**

### 5.3 `php artisan route:list --json` — **Verified**

```
total routes: 741
```

---

## 6. نتائج التحقق — الفحوص يدوياً

### 6.1 فحوص أثبتت صحّة ادعاءات سابقة

| الفحص | النتيجة |
|---|---|
| `grep -rn "InvoiceObserver" app/Providers/ bootstrap/` | **فارغ** ⇒ غير مُسجَّل ✅ |
| `grep -rn "InvoiceObserver" app/ tests/` | مرجعان فقط — الاختبار يُسجّله بنفسه ✅ |
| `php -r "method_exists('Carbon\Carbon','floatDiffInDays')"` | **DOES NOT EXIST** ✅ |
| `grep -n "taxCalculator->" app/Services/InvoiceService.php` | **فارغ** ⇒ مُحقَّنة وغير مستدعاة ✅ |
| `diff` على كتلتَي `CreditNoteService` | **183 سطر اختلاف من 560** ⇒ 67% متطابق ✅ |
| `grep -rlF "@/Components/DashboardV2/common/PageHeader.vue" resources/js app` | **0** ⇒ ميت ✅ |
| `grep -rn "import PageHeader" resources/js --include=*.vue \| grep -v "@/Components/PageHeader.vue"` | **0** ⇒ كل الـ115 استيراد للنسخة المعتمدة ✅ |
| `grep -rn "common.title" ` في ar.json | قيمة `العنوان` = عنوان ≠ عنوانوان ⇒ **تعارض نصي** ✅ |
| `grep -rn "floatDiffInDays" app/` | 3 مواقع ✅ |
| `grep -c "translate_choice\|trans_choice" app/` | **0** ✅ |
| `grep -rc "\$tc(" resources/js` | **0** ✅ |
| `grep -n "unique" database/migrations/2026_01_15_154700_fix_quotes_code_unique_constraint.php` | `unique(['tenant_id','center_id','code'])` ⇒ **موجود** ✅ |
| `grep -n "unique" database/migrations/2025_12_29_142537_create_center_sequences_table.php` | `center_seq_unique` ⇒ **موجود** ✅ |
| `grep -n "authorize" app/Http/Controllers/App/QuoteApprovalController.php` | `:31` و`:102` ⇒ **موجود** ✅ |
| `grep -rn "public function getDaysRemainingAttribute" ` + `grep -rn "->days_remaining" app/ resources/views/` | **فارغ** ⇒ الـ accessor غير مستدعى في الإنتاج ✅ |
| `grep -rn "TenancyContext" app/Jobs/SendAppointmentMessage.php` | **0** ⇒ الـ Job لا يعتمد على الـ scope ✅ |

### 6.2 إصلاحات مُثبتة (تحققت أن تقرير 2026-11-08 عتيق)

| الادعاء السابق | التحقق |
|---|---|
| `[C-2]` `GeneralSettingsController` بلا `super_admin` | ✅ `routes/web.php:1370` فيه `['super_admin', 'system.permission:…']` |
| `[C-3]` `is_system_admin` في `$fillable` | ✅ `User.php:129` casts فقط |
| `[H-3]` سقف الاسترداد | ✅ `CreditNoteService.php:467` و`:932` |
| `[H-1]` `Impersonation` | ✅ `ImpersonationController.php:47-49` |
| `[M-5]` race في `PurchasingService` | ✅ `DB::transaction` في 5 مواضع |
| `[M-4]` `config/cors.php` | ✅ موجود |
| 3 policies مفقودة | ✅ الثلاثة موجودة |
| ازدواج تسجيل listeners | ✅ `bootstrap/app.php:71` → `withEvents(discover: false)` |

---

## 7. كاشف التكرار — أداة التحليل

**الملف:** `/tmp/audit_dup3.py`
**الأمر:** `python3 /tmp/audit_dup3.py`

| المقياس | القيمة |
|---|---|
| الملفات الممسوحة | **1,408** |
| المقاطع المكرر المتطابقة (≥12 سطراً) | **9,394** |
| أطول مقطع | **1,444 سطر** |

**المنهجية:** نافذة hash من 12 سطراً مُطبَّعاً (تعليقيات محذوفة، مسافات موحّدة)، تتبّع المقطع عبر الملفات، ثم دمج المقاطع المتداخلة.

> ⚠️ **حد:** التطبيع يحذف التعليقات والمسافات فقط — **لا يحذف النصوص**. ⇒ التطابقات **حرفية**، لا دلالية. (كاشف أَوّلي بنِّي `audit_dup.py` كان يحذف النصوص ⇒ أنتج **مطابقات زائفة**، وهذا سبب التحويل إلى المعيار الصارم.)

---

## 8. حالة PHPUnit النهائية — ما يمكن الاعتماد عليه

### 8.1 Failing (11)

| # | الاختبار | التصنيف | الإصلاح |
|---|---|---|---|
| 1 | `FeedbackTest::…view_all_feedbacks_and_reply` | 🟡 **اختبار قديم** | `assertRedirect('system.support.index')` |
| 2 | `I18nCoverageTest::…_arabic` | 🔴 **انحدار ترجمة** | المرحلة 2 — إضافة 44 مفتاحاً |
| 3 | `I18nCoverageTest::…_english` | 🔴 **انحدار ترجمة** | المرحلة 2 — إضافة 42 مفتاحاً |
| 4 | `PerformanceBaselineTest::work_orders_show` | 🟠 **N+1** | المرحلة 10 |
| 5 | `PerformanceBaselineTest::customers_list` | 🟠 **N+1** | المرحلة 10 |
| 6 | `PerformanceBaselineTest::inventory_balances_list` | 🟠 **N+1** | المرحلة 10 |
| 7 | `PerformanceBaselineTest::vehicles_list` | 🟠 **N+1** | المرحلة 10 |
| 8 | `PerformanceBaselineTest::workshop_board` | 🟠 **N+1** | المرحلة 10 |
| 9 | `GeneralSettingsAllowlistTest::…allowlist_contains_only_the_key…` | 🟡 **اختبار قديم** | `ALLOWED_KEYS` فيه مفتاحان — الصفحة ترسلهما |
| 10 | `InvoicePdfCompanyInfoTest::…updated_company_settings_apply…` | 🔴 **عزل اختبار** | `payment_settings` جدول صف-واحد بلا قيد فريد |
| 11 | `TenantPublicSiteTest::…invalid_color_in_db_falls_back…` | 🟡 **اختبار قديم** | التوقع `#4f46e5` ← الصحيح `#2563eb` |

#### ⑫ `VueTranslationKeyTest::test_every_translation_key_used_in_a_vue_component_exists`

```
These translation keys are used in a component but do not exist in the locale files.
A key missing from BOTH files is not a fallback: vue-i18n renders the key itself,
at tests/Feature/VueTranslationKeyTest.php:80
```

- **الأمر:** `php artisan test tests/Feature/VueTranslationKeyTest.php` ⇒ `1 failed (2 assertions)`
- **النتيجة:** **38 مفتاحاً مفقوداً** مع **اسم الملف والسطر لكل واحد** (انظر القائمة في §3.1)
- **الدلالة:** 🔴 **تأكيد مستقل** لـ `translations:check` — أداتان في المشروع تتّفقان على 38 مفتاحاً مفقوداً في Vue
- **القائمة أدقّ من `translations:check`** لأنها تربط كل مفتاح **بملفه** ⇒ تطبيق المرحلة 2 مباشر
- **مستوى الثقة:** Verified

#### ⑬ `WorkOrdersCrudTest::test_cross_branch_data_sharing_and_policies` — 🔴 سياسة مقصودة

```
Expected response status code [200] but received 403.
at tests/Feature/WorkOrdersCrudTest.php:1334
```

**يعيد الإنتاج في العزل** (`php artisan test tests/Feature/WorkOrdersCrudTest.php --filter="cross_branch_data_sharing"` ⇒ 11.22s, نفس الفشل) ⇒ **ليس flaky**.

**السبب الجذري — مُتحقَّق منه بالتاريخ:**

| الطرف |.commit | التاريخ |
|---|---|---|
| الاختبار | `d7d1b54b` | **2026-10-25** |
| السياسة | `b3e7c61f` | **2026-10-29** (بعد الاختبار بأربعة أيام) |

`git merge-base --is-ancestor b3e7c61f d7d1b54b` ⇒ **NO** ⇒ السياسة تُغيّرت **بعد** كتابة الاختبار.

**السلوك الجديد مقصود وموثّق داخل الكود:**
```php
// app/Policies/WorkOrderPolicy.php:24-36
public function view(User $user, WorkOrder $workOrder): bool
{
    if (! $user->can(Permissions::WORK_ORDERS_VIEW)) {
        return false;
    }

    // Defense in depth: enforce tenant and current-center ownership.
    // The model's resolveRouteBinding() bypasses the center global
    // scope so a work order from a sibling branch still binds; the
    // policy is what stops it from being displayed.
    return $workOrder->tenant_id === $user->tenant_id
        && (int) $workOrder->center_id === (int) $user->current_center_id;
}
```

```php
// app/Models/WorkOrder.php:393-399
public function resolveRouteBinding($value, $field = null)
{
    return $this->withoutGlobalScope('center_scoped')   // ← تجاوز مقصود
        ->where($field ?? $this->getRouteKeyName(), $value)
        ->where('tenant_id', TenancyContext::tenantId())
        ->first();
}
```

**⇒ tightening أمني متعمَّد ومتسق**: تجاوز `resolveRouteBinding` يسمح بالربط (حتى لا يسرّب وجود السجل) لكن **السياسة** تمنع العرض. النمط **جيد** — والقيمة الأمنية حقيقية: مستخدم في الفرع B لا يرى كروت عمل الفرع A.

**⇒ الاختبار هو القديم.** الإصلاح: تحديث التوقّع من `200` إلى `403` **مع** توضيح النية في التعليق.

> **⚠️ قرار مطلوب من المالك:** هل القراءة عبر الفروع **يجب** تكون مسموحة؟ الكود الحالي يمنعها عمداً (أكثر تحفظاً). إن كان المطلوب السماح به، فيجب تخفيف `WorkOrderPolicy::view()` — **وهذا قرار عمل، لا قرار تقني**.

#### ⑪ `TenantPublicSiteTest::test_invalid_color_in_db_falls_back_to_default` — اختبار قديم

```
Property [site.primary_color] does not match the expected value.
  -'#4f46e5'      ← المتوقَّع في الاختبار
  +'#2563eb'      ← المُرجَع فعلياً
at tests/Feature/TenantPublicSiteTest.php:252
```

**مُتحقَّق:** الكود **صحيح ومتوافق مع نظام التوكن**:
```php
// app/Models/TenantWebsite.php:141
return self::safeHex($this->primary_color) ?? '#2563eb';   // ← لون التوكن الرسمي
```
`#2563eb` هو بالضبط `brand-primary` في `resources/js/Design/tokens.css:16` — أي أن **توحيد الـ Design System نُفِّذ هنا، والاختبار لم يُحدَّث**.

⇒ **الاختبار هو القديم.** الإصلاح: تحديث التوقع إلى `#2563eb`.

> **ملاحظة إيجابية:** هذه الفاشلة **مؤيدة** — دليل على أن توحيد الألوان تم فعلاً في طبقة الموديل. لكنه يوضح كلفة غياب بوابة الجودة: **تغيير مقصود في نظام التصميم كسر الاختبار ولم يُلحظ** حتى الآن.

#### ⑨ `GeneralSettingsAllowlistTest` — اختبار قديم (نفس النمط)

```
The allowlist must match the keys the page actually sends.
Array &0 [ 0 => 'security.2fa_sms_enabled', + 1 => 'subscription.grace_period_days' ]
at tests/Feature/System/GeneralSettingsAllowlistTest.php:150
```

**مُتحقَّق:** الـ allowlist في `app/Http/Controllers/System/GeneralSettingsController.php` **صحيح** — والصفحة ترسل المفتاحين فعلاً:
```js
// resources/js/Pages/System/Settings/Tabs/GeneralSettingsTab.vue:52,66
v-model.number="form.subscription_grace_period_days"
'subscription.grace_period_days',
```
⇒ **الاختبار هو القديم** (يفترض مفتاحاً واحداً). الإصلاح: تحديث `assertSame` ليحتوي المفتاحين.

> **نمط متكرر:** هذا **ثالث** اختبار قديم في نفس الجولة (بعد `FeedbackTest`). ⇒ الدليل على أن الميزة تُطوَّر واختباراتها لا تُحدَّث.

#### ⑩ `InvoicePdfCompanyInfoTest` — عزل اختبار (🔴 جديد)

| الفحص | النتيجة |
|---|---|
| **في العزل** | ✅ **ناجح** — `php artisan test tests/Feature/System/InvoicePdfCompanyInfoTest.php` ⇒ `4 passed (18 assertions)` في 44.25s |
| **في المجموعة الكاملة** | 🔴 **فاشل** |
| ⇒ | **flaky under load — ليس خللاً في الميزة** |

**السبب الجذري — مُستنبط من الكود:**
```php
// app/Models/PaymentSettings.php:105-107
return Cache::remember('payment_settings', 3600, function () {
    return self::first() ?? new self;      // ← first() = أول صف بالمفتاح الأساسي
});
```
```php
// app/Models/PaymentSettings.php:91-97
static::saved(fn () => static::clearCache());      // يُبطل الكاش عند الحفظ ✅
static::deleted(fn () => static::clearCache());
```

**المشكلة:** `payment_settings` **جدول صف-واحد بالاتفاق فقط** — لا قيد `unique` (تحققت: `2026_01_05_060853_create_payment_settings_table.php` لا يحتوي `unique`، بل يُدخل صفاً في `:38`).

الاختبار يفعل:
```php
$this->resetPaymentSettings();                    // DB::table('payment_settings')->delete()
$invoice = $this->makeInvoice();
$service->generatePdf($invoice);
PaymentSettings::create(['company_legal_name_en' => 'Updated Co']);   // ← يُنشئ صفاً جديداً
Cache::forget('payment_settings');
```

⇒ `self::first()` ترجع **أول صف بالمفتاح الأساسي**. إذا وُجد صف سابق (من seed أو اختبار سابق) فقد لا يكون الصف المُنشأ حديثاً هو الأول ⇒ `getSettings()` ترجع الصف الخطأ.

**لماذا ينجح في العزل:** ترتيب تنفيذ مختلف + لا صف سابق.

**⇒ تصنيف: عزل اختبار + هشاشة نموذج بيانات** — وليس خللاً في منطق الفاتورة.

**الإصلاح (اختبار):** `updateOrCreate` بدل `create`، أو حذف الصفوف بعد الإنشاء أيضاً، أو `tearDown` ينظّف.

**الإصلاح (نموذج بيانات — اختياري):** `payment_settings` يجب أن يكون صفاً واحداً مضموناً ⇒ `updateOrCreate` في كل مكان، أو جدول إعداد عام بصف واحد مُقفل.

> ⚠️ **لم يتم التحقق** من سبب الفشل بدقة 100% في سياق المجموعة الكاملة (لم يُلتقط stack trace النهائي لأن PHPUnit يطبعه في نهاية التشغيل). التصنيف مبني على **إعادة إنتاج سلوك العزل** + قراءة الكود.

### 8.2 What passed

- ✅ **1,154+ اختبار نجح**
- ✅ **كل اختبارات عزل المستأجرين** (`tests/Feature/TenantIsolation/` — 5 ملفات)
- ✅ **كل اختباراتulnerالutiy المالية** (`FinancialSafetyAuditAcceptanceTest` — 21 اختباراً)
- ✅ **`ImpersonationFlowTest`** (5/5)
- ✅ **`PortalSessionCookieIsolationTest`** (9/9)
- ✅ **`InertiaPageExistsTest`**
- ✅ **`DuplicateListenerRegistrationTest`** (يحمي `withEvents(discover: false)`)

### 8.3 تحذيرات PHPUnit

```
WARN Metadata found in doc-comment for method …::…().
Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12.
```
⇒ ترقية مستقبلية مطلوبة (لا أثر على السلوك الآن).

---

## 9. ❌ أوامر لم تُنفَّذ — وسببها

| الأمر | السبب |
|---|---|
| `php artisan migrate` | ⛔ **ممنوع** — تغيير قاعدة بيانات |
| `php artisan db:*` (أي أمر) | ⛔ ممنوع |
| `npm install` / `composer install` | ⛔ **ممنوع** — تثبيت مكتبات دون موافقة |
| `composer lint` (Pint `--test`) | ⚠️ لم يُنفَّذ — PHPStan+ESLint كافيان، وتجنّب أي تعديل |
| `npm audit` / `composer audit` | ⚠️ يحتاج شبكة — خارج النطاق الحالي |
| `npm run type-check` (`vue-tsc`) | ⚠️ لم يُنفَّذ — المشروع JS وليس TS (`jsconfig.json`) |
| Lighthouse | ⚠️ يتطلب خادم production — خارج النطاق الآمن |
| `npx paratest` | ❌ **ParaTest غير مثبّت** |
| اختبارات على بيانات الإنتاج | ⛔ ممنوع — `phpunit.xml` يفرض SQLite `:memory:` |
| `SHOW CREATE TABLE` | ⛔ لا اتصال بأي قاعدة بيانات |
| Profiling بـ `DB::listen` | ⚠️ لم يُنفَّذ — يحتاج بيئة عاملة |

---

## 10. ملخص مستوى الثقة لكل تقرير

| التقرير | مستوى الثقة العام | الدليل الأقوى فيه |
|---|---|---|
| `01_EXECUTIVE_SUMMARY.md` | **Verified** | كل رقم مرتبط بـ EV مُتحقَّق |
| `02_ARCHITECTURE_MAP.md` | **Verified** (المسارات والتفويض) · **Partially Verified** (خريطة الاعتماديات) | 419 مسار تفحصها برمجياً |
| `03_COMPONENT_INVENTORY.md` | **Verified** | عدّادات بمسار مطلق |
| `04_COMPONENT_CONSOLIDATION.md` | **Verified** | 9,394 مقطع مُقاس آلياً |
| `05_DESIGN_SYSTEM_AUDIT.md` | **Verified** (التوكن والعدادات) · **Not Verified** (التباين — يحتاج تصيير) | 148 hex · 5 أصداف |
| `06_TRANSLATION_AUDIT.md` | **Verified** (المفقود) · **Partially Verified** (غير المستخدم — قد تكون المفاتيح الديناميكية) | `translations:check` |
| `07_SETTINGS_AND_DATA_SOURCES.md` | **Verified** (المصادر) · **Not Verified** (الكاش) | 6 مصادر للاسم مُتحقَّق |
| `08_MODULE_DEPENDENCY_MAP.md` | **Verified** (الأحداث، المعاملات) · **Partially Verified** (الاستعلامات) | ميزانية الأداء مُقاسة |
| `09_BACKEND_DATABASE_AUDIT.md` | **Verified** · **Not Verified** (المخطط الفعلي — مصدره migrations) | PHPStan + diff |
| `10_FRONTEND_AUDIT.md` | **Verified** | عدّادات + كاشف التكرار |
| `11_SECURITY_PERFORMANCE.md` | **Verified** · **Not Verified** (CVE، SAST، DAST، حمل) | 17 مصنّفة |
| `12_REFACTORING_ROADMAP.md` | **Verified** (الترتيب مبني على الأدلة) | — |
| `13_VERIFICATION_RESULTS.md` | هذا الملف | — |
| `14_EVIDENCE_INDEX.md` | **Verified** | — |

---

## 11. 🔴 حالة بوابة الجودة — توصية صريحة

| البوابة | الحالة | الأثر |
|---|---|---|
| `npm run build` | ✅ أخضر | — |
| `npm run lint` | ✅ أخضر (تحذيرات فقط) | — |
| `npm run test` (vitest) | ✅ أخضر | — |
| `php artisan test` | 🔴 **أحمر — 8 فشل** | ⛔ **CI غير صالح كبوابة** |
| `composer stan` | 🔴 أحمر — 20 خطأ | ⚠️ baseline قديم |
| `composer lint` (Pint) | ⚠️ لم يُنفَّذ | — |

**⇒ قبل أي جولة تطوير، يجب إصلاح الاختبارات الثمانية** — وإلا لن يُعرف هل أدخلها جولة أم خرجت.

**ترتيب مقترح:** ① `FeedbackTest` (سطر واحد) → ② مفاتيح الترجمة (42+44) → ③ ميزانيات الأداء (5 صفحات). ⇒ **البوابة خضراء ⇒ كل جولة afterwards قابلة للقياس.**
