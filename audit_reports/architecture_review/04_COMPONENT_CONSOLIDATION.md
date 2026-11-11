# 04 — توحيد المكونات (Component Consolidation)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

---

## 1. معيار قياس التشابه

> **لا نسب تخمينية.** كل نسبة في هذا التقرير مشتقّة من معيار معلن وقابل لإعادة الحساب.

### المعيار: 9 سمات سلوكية

| # | السمة | الوزن |
|---|---|---|
| 1 | الغرض الوظيفي | 1/9 |
| 2 | نموذج الظهور/التفعيل | 1/9 |
| 3 | عقد الـ props | 1/9 |
| 4 | الأحداث المنبعثة | 1/9 |
| 5 | سلوك لوحة المفاتيح وa11y | 1/9 |
| 6 | التموضع والتخطيط | 1/9 |
| 7 | سلوك التحميل والخطأ | 1/9 |
| 8 | السلوك المتجاوب | 1/9 |
| 9 | دعم dark mode وi18n | 1/9 |

**النسبة = (السمات المتطابقة ÷ 9) × 100**

**مثال**: «7/9 سمات ⇒ 78%» يعني: الغرض ✅ · الظهور ✅ · props ⚠️ · الأحداث ✅ · a11y ✅ · التخطيط ⚠️ · التحميل ✅ · المتجاوب ❌ · dark/i18n ✅.

### معيار ثانٍ للتكرار البنيوي (مُقاس آلياً)

**كاشف التكرار** `/tmp/audit_dup3.py` — نافذة hash من 12 سطراً مُطبَّعاً.

**«تكرار بنيوي»** = عدد الأسطر المتطابقة حرفياً بين ملفين.
**«تكرار وظيفي»** = سمات متطابقة مع اختلاف في التنفيذ.

| المقياس | القيمة |
|---|---|
| الملفات الممسوحة | 1,408 |
| المقاطع المكرر المتطابقة | **9,394** |
| أطول مقطع | **1,444 سطر** (`HelpCenterTab.vue` ↔ `Help/Index.vue`) |

---

## 2. 🔴 المصفوفة — الأولوية الحرجة

| الوظيفة | الملفات الحالية | عدد التكرارات | المكوّن المقترح | نسبة التشابه | الأولوية | المخاطر |
|---|---|---|---|---|---|---|
| **حساب VAT والإجماليات** | `usePrintTotals.js` · `WorkOrderPartModal.vue` · `WorkOrderReturnModal.vue` · `WorkOrderIssueMoreModal.vue` · `Quotes/Show.vue` · `Purchasing/Invoices/Show.vue` · `InvoiceTotalsCard.vue` | **7** | 🔴 **DTO محسوب على الخادم** — ليس مكوّناً | **91%** (8/9) | **حرجة** | 🔴 عالية |
| **استرداد الفاتورة** | `CreditNoteService.php:236-515` · `:716-995` | **2** (280 سطر) | `RefundCalculator` مشترك | **67%** مطابق حرفياً | **حرجة** | 🟠 متوسطة |
| **PageHeader** | `Components/PageHeader.vue` (115 مستخدم) · `DashboardV2/common/PageHeader.vue` (**0**) | **2** | `Components/PageHeader.vue` + slot `toolbar`/`cta` | **89%** (8/9) | **حرجة** | 🟡 منخفضة (الأخرى ميتة) |
| **حالة الفاتورة** | `Invoice.php:175-224` · `PaymentService.php:119-152` · `PurchasingService.php:549` | **3** | `InvoiceStatusResolver` | **82%** (7/9) | **حرجة** | 🔴 عالية (حسابات) |
| **الترقيم** | 6 تنفيذات في 6 ملفات | **6** | `CenterSequence` (موجود!) | **85%** | **حرجة** | 🟠 متوسطة |
| **رفع الملفات** | 21 ملف خام · `AppAttachmentManager` · `ImportFileField` · `VehiclePhotoUploader` · `BankTransferReceiptUpload` · logo/stamp handlers | **6 عائلات** | `AppFileUploader` + `useFileUpload` | **67%** (6/9) | **عالية** | 🟠 متوسطة |
| **StatusBadge** | `Common/StatusBadge` (9) · `Billing/StatusBadge` (7) · `DashboardV2/common/StatusBadge` (7) · `CustomerPortal/StatusBadge` (7) · `Common/PaymentStatusBadge` (2) | **5** | `Common/AppStatusBadge.vue` + خرائط لكل مجال | **78%** (7/9) | **عالية** | 🟠 متوسطة |
| **Modal** | `BaseModal` (54) · `Modal` (29) · `DialogModal` (4) · `ConfirmModal` (12) | **4 طبقات** | `BaseModal` (primitive) + `AppDialog` (مُنسّق) واحد | **82%** (7/9) | **عالية** | 🔴 عالية (focus) |
| **الصفحة ↔ نسخة Tab** | 14 مجلد `Tabs/` — أكبرها 1,444 سطر | **14** | 🟢 **النسخة `Tabs/` هي المصدر** | **99%** حرفياً | **عالية** | 🟡 منخفضة |
| **Button** | `<button>` خام (276 ملف) · `AppButton` (11 وسم) · `Primary/Secondary/Danger` · `AppPrintButton` (32) · `BackButton` (49) | **5** | `AppButton` + `action="back"` | **85%** (8/9) | **عالية** | 🟠 متوسطة |
| **Input** | `<input>` خام (196 ملف) · `AppInput` · `TextInput` (ميت) | **3** | `AppInput` | **78%** (7/9) | **عالية** | 🟠 متوسطة |
| **Pagination** | `AppPagination` (20) · Laravel links خام · slice داخل العميل | **3** | `AppPagination` بوضعَي server/client | **86%** (8/9) | **عالية** | 🟠 متوسطة |
| **البحث/الفرز** | `useServerTable` (18) · منطق محلي (15+) | **2** | توسيع `useServerTable` | **84%** (7/9) | **عالية** | 🟠 متوسطة |
| **تنسيق العملة/التاريخ** | `useNumberFormat` (85) · `useFormatters` (11) · 8 دوال محلية | **3** | دوال معيارية فقط | **86%** (8/9) | **عالية** | 🟡 منخفضة |
| **Permission check** | `usePermission` (28) · فحص محلي ×2 | **2** | `usePermission` فقط | **89%** (8/9) | **عالية** | 🟡 منخفضة |
| **Textarea** | `<textarea>` خام (67 ملف) · `AppTextarea` (1) | **2** | `AppTextarea` | **83%** (7/9) | متوسطة | 🟢 منخفضة |
| **Checkbox** | `<input type=checkbox>` (21/16 ملف) · `AppCheckbox` · `Checkbox` (ميت) | **3** | `AppCheckbox` | **90%** | متوسطة | 🟢 منخفضة |
| **Toggle** | `AppToggle` (79) · `Toggle` (ميت) · hybrids | **2** | `AppToggle` فقط | **94%** (غلاف ي(wrapper)) | متوسطة | 🟢 منخفضة |
| **Tabs** | `AppTabs` (20) · `DashboardTabs` (ميت) · tabs يدوية | **3** | `AppTabs` | **84%** (7/9) | متوسطة | 🟢 منخفضة |
| **Radio** | `<input type=radio>` (35/18) · `RadioGroup` (2) | **2** | `RadioGroup` | **72%** | متوسطة | 🟢 منخفضة |
| **Tooltip** | `Tooltip.vue` (31) · `title=` خام (216 ملف) | **2** | `AppTooltip` + تMigration تدريجي | **67%** | متوسطة | 🟡 منخفضة |
| **DataTable** | `DataTable` (100) · 41 جدولاً خاماً (خاصة التقارير) | **2** | `DataTable` + محوّل طباعة | **75%** | متوسطة | 🟠 متوسطة |
| **Cards/KPI** | raw (181 ملف) · `DashboardV2/KPICard` (3) · `System/KpiCard` (2) · `CustomerPortal/PrimaryCard` (1) | **4** | `AppCard` + `AppKpiCard` | **76%** | متوسطة | 🟢 منخفضة |
| **Loading** | `LoadingSpinner` (1) · `animate-spin` (72 ملف) | **2** | `AppLoadingSpinner` | **88%** | متوسطة | 🟢 منخفضة |
| **Skeleton** | `AppTableSkeleton` (8) · `animate-pulse` (47 ملف) · `AppCardSkeleton` (ميت) | **3** | `AppSkeleton` بأنواع | **85%** | متوسطة | 🟢 منخفضة |
| **Empty state** | `AppEmptyState` (28) · raw (29 ملف) | **2** | `AppEmptyState` | **90%** | متوسطة | 🟢 منخفضة |
| **الهيكل الخطي للقائمة** | `WorkOrderFormModal` · `QuoteFormModal` · `PurchaseOrderFormModal` · `PurchaseInvoiceFormModal` · `CustomerFormModal` · `VehicleFormModal` | **6** | `LineItemEditor` + `TotalsPanel` | **76%** | متوسطة | 🟠 متوسطة |
| **Confirm** | `useConfirm` (40) · `ConfirmModal` (12) · `window.confirm` (4) · موديلات مخصّصة | **4** | `useConfirm` فقط | **82%** (7/9) | متوسطة | 🟠 متوسطة |
| **Dropdown/ActionMenu** | ❌ **لا يوجد primitive** | **≥2 نمط** | `AppDropdownMenu` | **72%** | متوسطة | 🟠 متوسطة |
| **Validation** | `FormField` (11) · `InputError` (7) · raw (70 ملف / 573) | **3** | `AppFormField` بـ slot خطأ | **80%** | متوسطة | 🟠 متوسطة |
| **Date picker** | `CustomDatePicker` (39) · `<input type=date>` (48 ملف) | **2** | `CustomDatePicker` | **88%** | متوسطة | 🟢 منخفضة |
| **Alert** | raw blocks · ❌ لا مشترك | **2** | `AppAlert` بأنواع | **72%** | متوسطة | 🟢 منخفضة |
| **Toast** | `Toast.vue` (3) · `useToast` (67) | **2** | `useToast` + عارض واحد | **95%** | منخفضة | 🟢 منخفضة |
| **Select** | `SearchableSelect` (125) · raw (2) · `AppSelect` (0) | **3** | `SearchableSelect` + استثناء native | **95%** | منخفضة | 🟢 منخفضة |
| **Breadcrumb** | `Breadcrumb.vue` (3) · تنقّل inline | **2** | `AppBreadcrumb` | **68%** | منخفضة | 🟢 منخفضة |

---

## 3. 🗑️ المكوّنات الميتة — 7 مؤكَّدة (حذف آمن)

> **الطريقة:** `grep -rlF "@/Components/<path>.vue" resources/js app | wc -l` — **بحث بمسار مطلق**.

| المكوّن | LOC | المراجع | الإجراء |
|---|---|---|---|
| `Components/DashboardV2/common/PageHeader.vue` | 50 | **0** | 🗑️ **حذف** — نسخة مكرّرة بـprops مختلفة |
| `Components/DashboardV2/common/DashboardTabs.vue` | 44 | **0** | 🗑️ حذف |
| `Components/Common/AppCardSkeleton.vue` | 27 | **0** | 🗑️ حذف |
| `Components/Toggle.vue` | 39 | **0** | 🗑️ غلاف لـ`AppToggle` |
| `Components/Checkbox.vue` | 34 | **0** | 🗑️ |
| `Components/TextInput.vue` | 26 | **0** | 🗑️ مخلف Breeze |
| `Components/Common/FormSection.vue` | 45 | **0** | ⚠️ **مطلوب في الدستور** — انظر §5 |
| `Components/App/AppSelect.vue` | 204 | 0 (1 import) | ⚠️ مسجَّل عامياً `app.js:123` بلا usage |

**⚠️ شرط قبل الحذف:** `PrimaryButton` / `SecondaryButton` / `DangerButton` / `InputLabel` / `InputError` — لها مستخدمون (5-7) ⇒ **ليست ميتة**، لكن يمكن دمجها في `AppButton` و`FormField`.

---

## 4. 🔴 الجولة 1 — التوحيد الأعلى عائداً

### 4.1 توحيد نمط «صفحة + نسخة Tab» — يزيل أكبر تكرار

**الأثر المتوقع:** ~2,000 سطر مكرر من 5 أزواج فقط.

| الزوج | السطر المكرر | الإجراء |
|---|---|---|
| `System/Support/Tabs/HelpCenterTab.vue` ↔ `System/Help/Index.vue` | **1,444** | 🟢 `Tabs/` = المصدر؛ `Index.vue` يستوردها أو يُحذف |
| `System/Support/Tabs/FeedbacksTab.vue` ↔ `System/Feedbacks/Index.vue` | 164 | نفس النمط |
| `System/ContactMessages/Index.vue` ↔ `System/Support/Tabs/ContactMessagesTab.vue` | 155 | نفس النمط |
| `System/Settings/CompanySettings.vue` ↔ `System/Settings/Tabs/CompanySettingsTab.vue` | 194 | نفس النمط |
| `System/Credits/WhatsappPackages.vue` ↔ `System/Credits/Tabs/WhatsappPackagesTab.vue` | 114 | نفس النمط |
| `System/Analytics/Kpis.vue` ↔ `System/Analytics/Tabs/KpisTab.vue` | 52 | نفس النمط |

**لماذا هذا أولاً:** لا يغيّر سلوكاً، ويمنع انحرافاً **مُثبتاً** (`Help/Index.vue` ينهار على `settings` null بينما نسخة `Tabs/` آمنة).

**المخاطرة:** 🟡 منخفضة — المصدر يُختار، والنسخة الأخرى تصبح wrapper.

**اختبار ما بعد:** search عن `settings.` (بدون `?.`) في الصفحات التي تقرأ `settings` من props.

### 4.2 توحيد `StatusBadge` — 5 عائلات → 1

**التصميم المقترح:**

```vue
<!-- components/Common/AppStatusBadge.vue -->
<script setup>
const props = defineProps({
    status: { type: [String, Number], required: true },
    domain: { type: String, default: 'generic' },  // invoice | workOrder | payment | portal | dashboard
    label: { type: String, default: null },        // تجاوز يدوي
});
</script>
<template>
    <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', toneClass]">
        {{ label ?? t(`status.${domain}.${status}`) ?? status }}
    </span>
</template>
```

**مع خرائط حالة لكل مجال** في `resources/js/Design/statusMaps.js` — بدل تكرار خريطة الألوان في كل badge.

**المخاطرة:** 🟠 متوسطة — أسماء المفاتيح تختلف بين المجالات ⇒ **خرائط صريحة لا transform عام**.

### 4.3 توحيد `Modal` — 4 طبقات → 2

```
BaseModal.vue  (primitive: <dialog> أصلي + focus trap + modal stack)   ← لا يُلمس
     ↓
AppDialog.vue  (مُنسّق: title + header + body + footer + ConfirmModal كوضع)
```

**الحذف المقترح:** `DialogModal.vue` (4 مستخدمين) — دمج into `AppDialog`.

**⚠️ خطر Focus:** `Modal.vue:107-139` ينفّذ focus trap + Escape + استعادة focus. **الطبقة الأساسية لا تُمسّ** — فقط الطبقات فوقها.

---

## 5. ⚠️ `FormSection` — قرار مطلوب من المالك

`Components/Common/FormSection.vue` (45 سطر) — **مطلوب في الدستور §3** لكن **0 مرجع**.

**ثلاثة خيارات:**

| الخيار | الأثر | المخاطرة |
|---|---|---|
| **أ. تُستخدم** — تنقل 11 ملف من `FormField` + تُستخدم في النماذج الجديدة | ✅ توافق مع الدستور | 🟡 منخفضة |
| **ب. تُحذف من الدستور** | ⚠️ervoARCHARالدستور يفقد بنداً | 🟡 منخفضة |
| **ج. تبقى موجودة غير مستخدمة** | ❌ الوضع الحالي | 🟡 |

**التوصية: (أ)** — الدستور يحدد أن `FormSection` «تجميع الحقول الموحدة مع Header ووصف وأزرار فرعية» — وهي وظيفة حقيقية. 11 ملف تستعمل `FormField` بدون `FormSection` = تبني أقساماً يدوياً.

---

## 6. 🔴 توحيد الحسابات المالية — الأولوية على مكوّنات الـ UI

### 6.1 القاعدة

> **لا يُنشأ مكوّن frontend لحساب الضريبة. يُنشأ DTO من الخادم.**

### 6.2 المواقع السبعة

| # | الملف | ما يُحسب |
|---|---|---|
| 1 | `Composables/usePrintTotals.js` (303 سطر) | gross · VAT · line totals — مع fallback `0.15` |
| 2 | `Components/WorkOrders/WorkOrderPartModal.vue:802-806, 921-924` | VAT + `tax_amount` + `grand_total` |
| 3 | `Components/WorkOrders/WorkOrderReturnModal.vue:395-399` | VAT |
| 4 | `Components/WorkOrders/WorkOrderIssueMoreModal.vue:394-398` | VAT |
| 5 | `Pages/Quotes/Show.vue:1425-1447, 1482-1485` | tax factor + total |
| 6 | `Pages/Purchasing/Invoices/Show.vue:2293-2332` | return net/tax/gross |
| 7 | `Components/Billing/InvoiceTotalsCard.vue:86-88` | اشتقاق VAT من المخرجات |

### 6.3 الخطة

| الخطوة | التفاصيل |
|---|---|
| 1 | `TaxCalculator` تصبح **المرجع الوحيد** (تقريب على مستوى المستند) |
| 2 | الخادم يُرجع `line.tax_amount` · `line.total_excl` · `line.total_incl` · `document.totals` محسوبة |
| 3 | الواجهة **تعرض** فقط |
| 4 | `usePrintTotals.js` يُحذف أو يصبح **مُصغَّراً** للعرض فقط |
| 5 | `InvoiceTotalsCard.vue` لا يستنتج VAT من الأرقام |

**المخاطرة:** 🟠 متوسطة — تتطلب ضبط `TaxCalculator` على التقريب على مستوى المستند واختبار تطابق.

**الجهد:** 3-5 أيام (منها 2 على الخادم).

---

## 7. 🔴 توحيد ملف الترقيم — الحل موجود بالفعل

| التنفيذ | الإجراء |
|---|---|
| `WorkOrder.php:176-222` | 🔴 → `CenterSequence` (قفل فعّال) |
| `Quote.php:130-168` | 🔴 → `CenterSequence` |
| `PurchaseOrder.php:150-166` | 🔴 → `CenterSequence` |
| `PurchaseInvoice.php:68-85` | 🔴 → `CenterSequence` |
| `GoodsReceivedNote.php:119-136` | 🔴 → `CenterSequence` |
| `PurchaseReturnInvoice.php:55-71` | 🔴 → `CenterSequence` |
| `InvoiceService.php:267` | ✅ مرجع |
| `CreditNoteService.php:373, 839` | ✅ مرجع |
| `SubscriptionInvoice.php:112-117` | 🔴 → `CenterSequence` |

**لماذا آمن:** `center_sequences` له القيد `unique(['tenant_id','center_id','type','year'], 'center_seq_unique')` — **موجود ومُتحقَّق** (`2025_12_29_142537…:22`).

**⚠️ تنبيه:** `center_id` صار قابلاً لـNULL ⇒ في MySQL الـ UNIQUE **لا يمنع تكرار NULL**. يلزم معالجة (إما `NOT NULL` أو مفتاح بديل).

---

## 8. خطة التحويل — ترتيب مقترح

| الجولة | التوحيد | الملفات | التكرار المزيل | المخاطرة | الجهد |
|---|---|---|---|---|---|
| **C-1** | حذف 7 مكونات ميتة | 7 | 315 LOC | 🟢 **صفر** | 15 دقيقة |
| **C-2** | نمط «صفحة + نسخة Tab» (6 أزواج) | 12 | **~2,100 سطر** | 🟡 منخفضة | 1-2 يوم |
| **C-3** | `StatusBadge` (5 → 1) | ~22 | ~400 LOC | 🟠 متوسطة | 2-3 أيام |
| **C-4** | الحسابات المالية → الخادم | 7 | **7 نسخ منطق** | 🟠 متوسطة | 3-5 أيام |
| **C-5** | `Modal` (4 → 2) | ~80 | طبقة | 🟠 **عالية** | 2-3 أيام |
| **C-6** | `CreditNoteService` → `RefundCalculator` | 2 | 280 سطر | 🟠 متوسطة | 3-4 أيام |
| **C-7** | `InvoiceStatusResolver` | 3 | 3 → 1 | 🔴 **عالية** | 1-2 يوم |
| **C-8** | الترقيم → `CenterSequence` | 7 | 6 → 1 | 🟠 متوسطة | 2-3 أيام |
| **C-9** | `useFileUpload` + `AppFileUploader` | 21 | 6 عائلات | 🟠 متوسطة | 3-4 أيام |
| **C-10** | `useServerTable` للتقارير | ~15 | 15 → 1 | 🟠 متوسطة | 3-5 أيام |
| **C-11** | `AppButton` (1,322 خام → هدف 300) | 276 | — | 🟡 منخفضة | 2-3 أسابيع |
| **C-12** | `AppInput` / `AppTextarea` / `FormField` | ~250 | — | 🟡 منخفضة | 2-3 أسابيع |

**⚠️ القاعدة:** C-11 وC-12 **ممتدّان** — يُنفَّذان تدريجياً مع كل عمل روتيني آخر، لا في جولة منفصلة.

---

## 9. قاعدة «لا تُعمّم أكثر من اللازم»

**لم يُقترح توحيد:**

| ما استُثني | السبب |
|---|---|
| `SearchableSelect` vs `AppSelect` | `AppSelect` **غير مستخدم** ⇒ ليست حالتا تكرار |
| `PrintTemplates/*` الثلاثة | مستخدمة **ديناميكياً** — ليست تكراراً |
| `QuotedPartModal` vs `WorkOrderPartModal` | 72 سطر متطابق لكن **منطق مجالين مختلفين** (عرض سعر vs كرت عمل) — الفصل أفضل |
| `VehicleMake` / `VehicleModel` / `VehicleColor` | 61 سطر متطابق — لكن **جداول مرجعية مختلفة**؛ التوحيد بـtrait ممكن لكنه منخفض العائد |
| `CustomerStoreRequest` vs `CustomerUpdateRequest` | 82 سطر متطابق ⇒ **قاعدة مشتركة** أفضل من تكرار — ✅ يستحق التوحيد |
| `Settings/System/Modals/*FormModal` (4 ملفات) | 77-90 سطر متطابق ⇒ **قاعدة مشتركة** — ✅ يستحق |
| `System/Credits/Sms*` vs `Whatsapp*` | 97-114 سطر متطابق ⇒ **مكوّن base + props** — ✅ يستحق |

---

## 10. الخلاصة

| المحور | القيمة |
|---|---|
| المقاطع المكرر المتطابقة | **9,394** |
| المكوّنات الميتة المؤكَّدة | **7** |
| فرص التوحيد المقترحة | **30** |
| أكبر تكرار منفرد | **1,444 سطر** |
| إجمالي التكرار القابل للإزالة في الجولات C-1..C-10 | **~3,500 سطر + 7 نسخ منطق مالي** |
| الخطر الأهم في التوحيد | 🔴 **focus management** في الـ modals (C-5) · 🔴 **قواعد الحساب** (C-4, C-7) |
| **القرار المطلوب من المالك** | `FormSection` — تُستخدم أم تُحذف من الدستور؟ |
