# 12 — خطة إعادة الهيكلة المرحلية (Refactoring Roadmap)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

---

## 1. مبدأ الخطة

> **إعادة الهيكلة ليست هدفاً. كل جولة يجب أن تعطي قيمة قابلة للقياس.**

**قاعدة القبول:** كل جولة تُنهي بـ:
1. ✅ **اختبارات قبل وبعد** (نفس المجموعة تنجح)
2. ✅ **طريقة تراجع** (ملف واحد أو git revert)
3. ✅ **لا تغيير** على مسارات أو عقود API أو أسماء أعمدة

**الترتيب أدناه مُعاد ضبطه** بناءً على نتائج التدقيق، **ليس** الترتيب الافتراضي المقترح في الطلب. السبب: الفئة 2 (أخطاء حرجة) كانت **سلوكية لا برمجية**، والفئتان 3-4 (Design System + الترجمة) هما **الأعلى عائداً بأقل مخاطرة**.

---

## 2. نظرة عامة على المراحل

| المرحلة | المحتوى | الجهد | المخاطرة | القيمة |
|---|---|---|---|---|
| **0** | تثبيت خط الأساس | 0.5 يوم | 🟢 صفر | تمMeasure |
| **1** | 🔴 5 إصلاحات حرجة | 3 ساعات | 🟢 صفر | **قصوى** |
| **2** | 🔴 42+44 مفتاح ترجمة + RTL | 1 يوم | 🟢 صفر | **عالية** |
| **3** | 🔴 توحيد حساب الضريبة | 2-3 أيام | 🟠 متوسطة | **عالية** |
| **4** | 🔴 توحيد Header + الشارات + الميتة | 2-3 أيام | 🟡 منخفضة | **عالية** |
| **5** | 🟠 استخراج منطق الاسترداد + حالة الفاتورة | 4-5 أيام | 🟠 متوسطة | **عالية** |
| **6** | 🟠 Design System: التوكن + الأصداف | 1-2 أسبوع | 🟠 متوسطة | **عالية** |
| **7** | 🟠 طبقة الـ Tab + DataTable | 1-2 أسبوع | 🟡 منخفضة | متوسطة |
| **8** | 🟠 إعادة تنظيم الترجمة | 1 أسبوع | 🟡 منخفضة | متوسطة |
| **9** | 🟠 مركزية الإعدادات + snapshots | 1-2 أسبوع | 🟠 متوسطة | **عالية** |
| **10** | 🟡 الأداء + اختبارات الانحدار | 1 أسبوع | 🟡 منخفضة | متوسطة |

**الإجمالي التقديري: ~8-10 أسابيع** بوتيرة جزئية.

---

## 3. المرحلة 0 — تثبيت خط الأساس ✅

**الحالة:** مكتملة (هذه التقارير الـ14).

| المُخرَج | الملف |
|---|---|
| الملخص التنفيذي | `01_EXECUTIVE_SUMMARY.md` |
| الخريطة المعمارية | `02_ARCHITECTURE_MAP.md` |
| جرد المكوّنات | `03_COMPONENT_INVENTORY.md` |
| مصفوفة التوحيد | `04_COMPONENT_CONSOLIDATION.md` |
| تدقيق التصميم | `05_DESIGN_SYSTEM_AUDIT.md` |
| تدقيق الترجمة | `06_TRANSLATION_AUDIT.md` |
| الإعدادات | `07_SETTINGS_AND_DATA_SOURCES.md` |
| ترابط الوحدات | `08_MODULE_DEPENDENCY_MAP.md` |
| Backend + DB | `09_BACKEND_DATABASE_AUDIT.md` |
| Frontend | `10_FRONTEND_AUDIT.md` |
| الأمان والأداء | `11_SECURITY_PERFORMANCE.md` |
| هذه الخطة | `12_REFACTORING_ROADMAP.md` |
| نتائج التحقق | `13_VERIFICATION_RESULTS.md` |
| فهرس الأدلة | `14_EVIDENCE_INDEX.md` |

---

## 4. 🔴 المرحلة 1 — خمسة إصلاحات حرجة (3 ساعات)

> **ملاحظة:** أعيد التحقق من الـ suite كاملة بعد الجولة ⇒ **13 اختبار فاشل**. منها **5 اختبارات قديمة/مقصودة** يجب إصلاحها أيضاً (لا تغيّر سلوكاً، فقط تعيد البوابة للأخضر). مذكورة في §4.1 أدناه.

### النطاق الدقيق: 6 ملفات

| # | الملف | التغيير | الفحص |
|---|---|---|---|
| 1 | `app/Providers/AppServiceProvider.php` | `Invoice::observe(InvoiceObserver::class);` بعد `:252` | — |
| 2 | `tests/Feature/InvoicingSecurityTest.php` | **إزالة** `Invoice::observe(InvoiceObserver::class);` من `:27` | — |
| 3 | `app/Models/Billing/Subscription.php` | `:125,129` — `floatDiffInDays` ⇒ `diffInDays(..., true)` | — |
| 4 | `app/Services/Billing/SubscriptionLifecycleService.php` | `:331` — نفس الإصلاح | — |
| 5 | `resources/js/i18n/lang/en.json` | `common.app_name`: `'Pro service'` ⇒ `'Khidmh Pro'` | — |
| 6 | `resources/js/app.js` | `:31` — إزالة السقوط `'Laravel'` | — |

### 4.1 إصلاحات البوابة الخضراء (5 اختبارات — لا تغيّر سلوكاً)

| # | الملف | التغيير | ملاحظة |
|---|---|---|---|
| 7 | `tests/Feature/FeedbackTest.php:202` | `assertStatus(200)` ⇒ `assertRedirect('system.support.index')` | إعادة توجيه مقصودة (commit `82c92ec5`) |
| 8 | `tests/Feature/System/GeneralSettingsAllowlistTest.php:150` | `assertSame(['security.2fa_sms_enabled'], …)` ⇒ أضف `'subscription.grace_period_days'` | allowlist **صحيح** — الصفحة ترسلهما |
| 9 | `tests/Feature/TenantPublicSiteTest.php:252` | `#4f46e5` ⇒ `#2563eb` | الكود **صحيح** — `#2563eb` هو `brand-primary` |
| 10 | `tests/Feature/WorkOrdersCrudTest.php:1334` | `assertStatus(200)` ⇒ `assertStatus(403)` | ⚠️ **يتطلب قرار المالك** (§17) |
| 11 | `tests/Feature/System/InvoicePdfCompanyInfoTest.php` | `PaymentSettings::create()` ⇒ `updateOrCreate` أو تنظيف بعد الإنشاء | عزل اختبار |

> **الجهد:** 5 اختبارات ≈ **ساعة**. **العائد: البوابة تصبح خضراء ⇒ كل جولة بعدها قابلة للقياس.**

### لماذا هذه أولاً

| السبب | التفصيل |
|---|---|
| **صفر مخاطرة** | كل تغيير سطر واحد. لا يمسّ بنية ولا contracts ولا migrations |
| **أعلى عائد** | حارس قانوني (ZATCA) + اسم المنظومة + خطأ fatal مُحتمل |
| **ينهي 4 اختبارات حمراء** | `I18nCoverageTest` (أُصلح المفاتيح ⇒ ينجح) + `VueTranslationKeyTest` |
| **قابل للتراجع فوراً** | `git checkout HEAD -- <files>` |
| **يقيس أثر الجولات التالية** | خط أساس نظيف |

### الفحص بعد التنفيذ

```bash
php artisan test tests/Feature/InvoicingSecurityTest.php
php artisan test tests/Feature/SubscriptionLifecycleAndRenewalTest.php
php artisan test --filter=no_new_missing_translation_keys   # ما زال أحمر — 42/44 مفقوداً
npm run build
```

> ⚠️ `I18nCoverageTest` و`VueTranslationKeyTest` **سيبقيان أحمر** حتى المرحلة 2. هذا متوقّع.

---

## 5. 🔴 المرحلة 2 — الترجمة المفقودة + RTL (يوم)

### 5.1 إضافة 42 + 44 مفتاحاً

**المصدر جاهز:** `php artisan translations:check --source=both --locale=ar|en` يطبع القائمة الكاملة.

| النطاق | العدد | الأولوية |
|---|---|---|
| `system.payment.*` | 17 | 🔴 **صفحات الدفع — أسوأ UX** |
| `common.*` | 9 | 🔴 `no_records_found` في كل جدول |
| `roles.*` | 8 | 🟠 نموذج الدور |
| `reports.*` | 5 | 🟠 عناوين التقارير |
| `crm.vehicles.title` · `invoices.cash` · `users.empty_description` · `system.security.add_extra_layer` | 4 | 🟠 |
| `auth.invitation_invalid` · `messages.subscription.*` | 3 | 🔴 **middleware — كل مستأجر معطَّل** |

**المخرج:** `translations:check` ⇒ 0 مفقود · `I18nCoverageTest` ⇒ أخضر.

### 5.2 إضافة `missing` handler

```js
// resources/js/i18n/index.js
missing: (key, locale) => {
    if (import.meta.env.DEV) {
        console.warn(`[i18n] missing key "${key}" (${locale})`);
    }
    return key;
},
```

### 5.3 توحيد اللغة بين Vue و PHP

| السطح | الحالي | المقترح |
|---|---|---|
| Vue | `localStorage.getItem('app.locale') \|\| 'ar'` | يُرسل للـ server |
| PHP | `env('APP_LOCALE', 'en')` | يُقرأ من session |

**⇒ المستخدم الذي لم يلمس مبدّل اللغة يرى واجهة عربية برسائل تحقق إنجليزية.**

### 5.4 إصلاح `CustomPage.vue`

```js
// :16-18 — قبل
return document.documentElement.dir === 'rtl' || true;
// بعد
return document.documentElement.dir === 'rtl';
```

### 5.5 فحوص ما بعد

```bash
php artisan translations:check --source=both --locale=ar
php artisan translations:check --source=both --locale=en
php artisan test --filter=I18nCoverageTest
npm run build
```

---

## 6. 🔴 المرحلة 3 — توحيد حساب الضريبة (2-3 أيام)

### المشكلة

`TaxCalculator` المعتمدة (بدون تقريب، للتسوية على مستوى المستند) — **تُستدعى في موضع واحد فقط**. تسع نسخ inline تقرّب لكل سطر ⇒ فرق تراكمي.

### الخطوات

| # | الخطوة | الملفات | ملاحظة |
|---|---|---|---|
| 1 | توثيق سلوك `TaxCalculator` (تقريب على مستوى المستند) | `TaxCalculator.php` | |
| 2 | استبدال النسخة الأولى | `InvoiceService.php:145-152` | مُحقَّنة أصلاً ⇒ تغيير موضعي |
| 3 | استبدال النسخة الثانية | `InvoiceService.php:197-201` | |
| 4 | `CreditNoteService` — المصدران | `:254-258`, `:312-316`, `:733` | ← الجولة 5 |
| 5 | `QuoteLine.php:89-95` · `WorkOrder.php:263` | | |
| 6 | **حساب في الـ controllers** | `PurchaseReturnsController.php:69-73` · `ReportsController.php:2978` | 🔴 الأخطر |
| 7 | **DTO محسوب للخادم** | استجابة `Invoice` + `Quote` | للواجهة |
| 8 | الواجهة **تعرض فقط** | 7 ملفات | `usePrintTotals.js` يُحذف/يُصغَّر |
| 9 | **اختبار تطابق** | `TaxCalculatorParityTest` | 🔴 **ضروري** |

### الاختبار المطلوب

```php
// tests/Feature/TaxCalculatorParityTest.php
// كل مسار حساب ضريبة يجب أن يطابق TaxCalculator::calculateLine()
public function test_invoice_line_tax_matches_tax_calculator(): void { ... }
public function test_credit_note_tax_matches_tax_calculator(): void { ... }
```

### المخاطر

| الخطر | التخفيف |
|---|---|
| تغيير **نتائج** محسوبة (تقريب) | اختبار تطابق قبل/بعد + مراجعة الفروق |
| الواجهة تعتمد على الأرقام المحسوبة محلياً | تحديث الـ DTO + مراجعة الـ 7 مواقع |
| `usePrintTotals.js` مستخدم في الطباعة | ⚠️ الطباعة تحتاج snapshot من الخادم |

**المخاطرة:** 🟠 متوسطة. **الجهد:** 2-3 أيام (2 منها الخادم).

---

## 7. 🔴 المرحلة 4 — Header + الشارات + المكوّنات الميتة (2-3 أيام)

### 7.1 حذف 7 مكونات ميتة (15 دقيقة) 🟢

```bash
# التحقق أولاً (مسار مطلق!)
grep -rlF "@/Components/DashboardV2/common/PageHeader.vue" resources/js app | wc -l   # → 0
grep -rlF "@/Components/DashboardV2/common/DashboardTabs.vue" resources/js app | wc -l   # → 0
grep -rlF "@/Components/Toggle.vue" resources/js app | wc -l                            # → 0
grep -rlF "@/Components/Checkbox.vue" resources/js app | wc -l                          # → 0
grep -rlF "@/Components/TextInput.vue" resources/js app | wc -l                         # → 0
grep -rlF "@/Components/Common/AppCardSkeleton.vue" resources/js app | wc -l            # → 0
grep -rlF "@/Components/Common/FormSection.vue" resources/js app | wc -l               # → 0
```

### 7.2 إضافة slotين لـ `PageHeader` (ساعة)

```vue
<!-- components/PageHeader.vue -->
<template>
    <div class="rounded-2xl border border-border bg-surface/80 p-4 backdrop-blur-md sm:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <!-- العنوان + الأيقونة + الشارة -->
            <div class="flex items-center gap-3">
                <slot name="icon" />
                <div>
                    <h1 class="text-lg font-semibold text-text-primary">{{ title }}</h1>
                    <slot name="subtitle" />
                </div>
                <span v-if="totalCount !== null" class="badge">{{ totalCount }}</span>
            </div>

            <!-- شريط الأدوات — يبقى أولاً -->
            <div v-if="$slots.toolbar" class="ms-auto flex items-center gap-2">
                <slot name="toolbar" />
            </div>

            <!-- CTA — آخر ابن في DOM، مثبَّت في الزاوية -->
            <div v-if="$slots.cta" class="flex items-center gap-2">
                <slot name="cta" />
            </div>
        </div>
    </div>
</template>
```

**🟢 الاختيار:** `toolbar` قبل `cta` **في الـ DOM** ⇒ الترتيب المعماري مُنفَّذ ولا يحتاج `justify-between` هشّ.

### 7.3 توحيد `StatusBadge` (5 → 1) (2-3 أيام)

**انظر `04_COMPONENT_CONSOLIDATION.md` §4.2** — 22 ملف مستخدم.

### 7.4 فحوص ما بعد

```bash
npm run build
npx vitest run
php artisan test tests/Feature/InvoicingSecurityTest.php
```

---

## 8. 🟠 المرحلة 5 — الاسترداد + حالة الفاتورة (4-5 أيام)

### 8.1 استخراج `RefundCalculator` (3-4 أيام)

`CreditNoteService.php` — 280 سطر مكرر ×2.

**الفصل المقترح:**
```
app/Services/CreditNote/RefundCalculator.php     ← المنطق المحسوب (مُشترك)
app/Services/CreditNote/CreditNoteService.php     ← orchestration فقط
```

⚠️ **انتبه للانحراف المُثبت:** نسخة كرت العمل تتعامل مع `pricingMode === 'inclusive'`، ونسخة الفاتورة **لا**. **الـ calculator الموحّد يجب أن يغطي الحالتين.**

### 8.2 `InvoiceStatusResolver` (1-2 يوم)

**قرار مطلوب:** هل `bad_debt` = مدفوع؟

| الخيار | الأثر |
|---|---|
| **لا** (المقترح) | الديون غير المحصّلة ليست مدفوعة — أدقّ محاسبياً |
| نعم | يطابق تقرير المدفوعات الحالي |

⇒ **القرار موثَّق في `09_BACKEND_DATABASE_AUDIT.md` §5.**

### 8.3 خطر

| الخطر | التخفيف |
|---|---|
| تغيير حالة فواتير قائمة | **اختبار لكل transition** قبل/بعد |
| فرق في تقرير المدفوعات | يحدّث الاختبار `FinancialSafetyAuditAcceptanceTest` |

---

## 9. 🟠 المرحلة 6 — Design System (1-2 أسبوع)

**التفاصيل الكاملة في `05_DESIGN_SYSTEM_AUDIT.md` §16.**

| الخطوة | الجهد | الخطر |
|---|---|---|
| V-0: عقد التصميم + قرار بوابة الموظف | 0.5 يوم | 🟢 |
| V-1: التوكن إلزامي (spacing/radius/shadow/z-index + foreground للحوالات) | 1 يوم | 🟢 |
| V-3: `PageHeader` + `AppStatusBadge` | متضمنة في المرحلة 4 | 🟡 |
| V-4: البوابة العامة + مواقع المستأجرين على نفس التون | 2-3 أيام | 🟡 |
| V-2: `AppSidebar`/`AppTopbar` مشترك | 2-3 أيام | 🟠 |
| V-5: RTL (`ms-`/`me-`/`ps-`/`pe-`) — 265 موضعاً | 3 أيام | 🟠 |
| V-6: a11y (أسماء الأزرار · `DataTable` بالكيبورد) | 2-3 أيام | 🟡 |

**⚠️ قرار مطلوب من المالك:** هل `EmployeePortalLayout` يصبح تنقّلاً داخل `TenantLayout` (موصى به) أم منتجاً منفصلاً بلون بنفسجي؟ **قرار علامة تجارية.**

---

## 10. 🟠 المرحلة 7 — طبقة الـ Tab + DataTable (1-2 أسبوع)

| # | الفعل | الجهد |
|---|---|---|
| 1 | 🟢 `Tabs/` = المصدر لستة أزواج | 1-2 يوم |
| 2 | `useServerTable` للتقارير (15+ صفحة) | 3-5 أيام |
| 3 | `DataTable` لـ 41 جدولاً خاماً | 3-5 أيام |
| 4 | `useFileUpload` (21 ملف) | 3-4 أيام |
| 5 | `AppModal` (4 طبقات → 2) | 2-3 أيام |

**⚠️ C-5 (Modal) هو الأعلى خطراً** — `Modal.vue:107-139` ينفّذ focus trap. **الطبقة الأساسية لا تُمسّ.**

---

## 11. 🟠 المرحلة 8 — إعادة تنظيم الترجمة (1 أسبوع)

| # | الفعل | التوفير | المخاطرة |
|---|---|---|---|
| 1 | ⭐ دمج chrome التقارير → `reports.common.*` | **~120** | 🟢 منخفضة |
| 2 | 🔴 حذف `validation.*` من Vue | **123** | 🟢 صفر |
| 3 | تصحيح تذكير «paid» | ~10 | 🟠 متوسطة |
| 4 | تقسيم `common.*` | 0 | 🟢 منخفضة |
| 5 | صيغ الجمع العربية | 0 | 🟢 منخفضة |
| 6 | حذف 1,400 مفتاحاً غير مستخدم | **1,400** | 🟡 منخفضة |
| 7 | استخراج 969+1,217 نصاً مثبَّتاً | 0 | 🟠 متوسطة |
| 8 | 🔴 توحيد 4 أشجار حالة كرت العمل | ~130 | 🔴 **عالية — أخّرت عمداً** |

---

## 12. 🟠 المرحلة 9 — مركزية الإعدادات (1-2 أسبوع)

**التفاصيل في `07_SETTINGS_AND_DATA_SOURCES.md` §9.**

| # | الفعل | المخاطرة |
|---|---|---|
| 1 | `app.*` في Inertia shared props | 🟢 |
| 2 | تحويل كل قارئات اسم المنظومة | 🟢 |
| 3 | 🔴 Migration: `invoices.seller_logo_snapshot` + `stamp` + `address` | 🟠 |
| 4 | توحيد الرقم الضريبي | 🟠 |
| 5 | توحيد شاشات هوية الشركة (4 شاشات) | 🟠 |
| 6 | 🔴 Migration: `tenants.timezone` + ترحيل | 🔴 **عالية** |

---

## 13. 🟡 المرحلة 10 — الأداء + اختبارات الانحدار (أسبوع)

### 13.1 ميزانيات الاستعلامات (5 صفحات فوق الحد)

| المسار | الحالي | الهدف |
|---|---|---|
| `/app/work-orders/1` | **60** | 55 |
| `/app/vehicles` | **35** | 30 |
| `/app/workshop` | **34** | 30 |
| `/app/inventory/stock` | **33** | 30 |
| `/app/customers` | **29** | 25 |

**الطريقة:** إضافة `->with([...])` في الـ controller **قبل** `$query->count()`.

### 13.2 اختبارات الانحدار المطلوبة

| الاختبار | يحمي من |
|---|---|
| `InvoiceObserverRegistrationTest` | EV-01 — `InvoiceObserver` غير مُسجَّل |
| `TaxCalculatorParityTest` | انحراف الضريبة |
| `DocumentNumberingUniquenessTest` | أرقام مكرّرة |
| `InvoiceStatusRuleConsistencyTest` | قواعد `bad_debt` المتعارضة |
| `TransactionBoundaryTest` | عمليات متعددة بلا transaction |
| `SnapshotImmutabilityTest` | تعديل بيانات يغيّر مستنداً سابقاً |
| `TranslationKeyParityTest` | انحدار المفاتيح (موجود — `I18nCoverageTest`) |
| `ComponentRegistryTest` | reintroducing مكونات ميتة / خام |

### 13.3 أداء

| الإجراء | الأثر المتوقع |
|---|---|
| فهرس على `invoices.invoice_number` | 5 تقارير بـ`LIKE 'prefix%'` ⇒ من مسح كامل إلى بحث مفهرس |
| تدقيق `theme-*.js` (726 kB) | قد يكون ApexCharts في كل صفحة |
| `DB::listen` في بيئة اختبار | كشف N+1 الناقص |

---

## 14. مصفوفة الاعتماديات بين الجولات

```
المرحلة 1 ──┬─→ المرحلة 2 ──┬─→ المرحلة 8
 (حرجة)     │   (ترجمة)     │
             │                │
             └─→ المرحلة 3 ──┴─→ المرحلة 5
                (ضريبة)          (استرداد + حالة)
                   │
                   └──→ المرحلة 9
                        (إعدادات: DTO يحتاج base)

المرحلة 4 ──────→ المرحلة 6 ──────→ المرحلة 7
 (header/         (design          (tab layer
  شارات)           system)           + DataTable)

المرحلة 10 (أداء + اختبارات) — بعد كل ما سبق
```

**⚠️ استثناء:** المرحلة 10 (اختبارات الانحدار) يجب أن تبدأ **مبكراً** — إضافة `InvoiceObserverRegistrationTest` و`TaxCalculatorParityTest` في المرحلة 1-3 يحميهما من الانحدار.

---

## 15. تقدير الجهد الإجمالي

| المرحلة | الجهد |
|---|---|
| 0 — خط الأساس | 0.5 يوم (✅ مكتمل) |
| 1 — حرج | **3 ساعات** |
| 2 — ترجمة | 1 يوم |
| 3 — ضريبة | 2-3 أيام |
| 4 — header/شارات | 2-3 أيام |
| 5 — استرداد/حالة | 4-5 أيام |
| 6 — design system | 1-2 أسبوع |
| 7 — tab/DataTable | 1-2 أسبوع |
| 8 — ترجمة منظمة | 1 أسبوع |
| 9 — إعدادات | 1-2 أسبوع |
| 10 — أداء/اختبارات | 1 أسبوع |
| **الإجمالي** | **~8-10 أسابيع** |

**بوتيرة جزئية (4-6 ساعات/يوم):** 3-4 أشهر.
**بوتيرة كاملة:** شهران.

---

## 16. ⚠️ ما يجب عدم فعله

| ❌ | السبب |
|---|---|
| ❌ توحيد 4 أشجار حالة كرت العمل قبل الجولة 8 | 🔴 **عالي** — يمسّ كل قائمة حالات وكل dynamic lookup |
| ❌ توحيد الـ Modals قبل المرحلة 7 | 🔴 **خطر focus regressions** |
| ❌ تغيير `TenantScoped`/`CenterScoped` | 🔴 **الأخطر** — اختبارات العزل شرط |
| ❌ `DB::transaction` حول `ZatcaOnboardingService` دون مراجعة | ⚠️ استدعاء API خارجي — قد يترك حالة نصف مُفعَّلة |
| ❌ حذف `FormSection` قبل قرار المالك | ⚠️-voxelDSM |
| ❌ تعميم `usePrintTotals` كمكوّن | 🔴 **يجب أن ينتقل للخادم** لا يُعمَّم |
| ❌ تحديث `I18nCoverageTest`Threshold | 🔴 **يجب أن يبقى 0** — أي رفع يخفي انحداراً |
| ❌ `npm install` / `composer install` | ❌ دون موافقة |
| ❌ تعديل migrations سابقة | ❌ **Migration جديدة فقط** |
| ❌ تغيير أسماء المسارات أو عقود API | ❌ |

---

## 17. قرار مطلوب من المالك

| # | السؤال | التوصية |
|---|---|---|
| 1 | `FormSection` تُستخدم أم تُحذف من الدستور؟ | **تُستخدم** (11 ملف) |
| 2 | `EmployeePortalLayout` تنقّل داخل `TenantLayout` أم منتج منفصل؟ | **داخل `TenantLayout`** (يوحّد اللون والهيدر) |
| 3 | هل `bad_debt` = مدفوع؟ | **لا** — أدقّ محاسبياً |
| 4 | `tenants.timezone` — UTC أم `Asia/Riyadh` افتراضياً؟ | `Asia/Riyadh` افتراضياً |
| 5 | `center_type` — يبقى نصاً حراً أم enum؟ | **enum** |
| 6 | `center_sequences.center_id` — `NOT NULL` أم مفتاح بديل؟ | **`NOT NULL`** |
| 7 | هل `InvoiceObserver` يجب أن يمنع كل التعديل على الفواتير المُرسلة؟ | ✅ نعم (ZATCA) |
| 8 | 🔴 **هل القراءة عبر الفروع مسموحة؟** `WorkOrderPolicy::view()` يمنعها عمداً | **يتطلب قرار المالك** — إذا كانت مطلوبة ⇒ تخفيف السياسة (قرار عمل لا تقني) |
| 9 | `payment_settings` — `updateOrCreate` في كل مكان أم جدول إعداد عام بصف واحد؟ | **`updateOrCreate`** (أقل خطراً من migration) |
