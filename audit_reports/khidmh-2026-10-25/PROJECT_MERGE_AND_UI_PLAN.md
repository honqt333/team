# خطة الدمج وتوحيد الواجهة — KHIDMH.PRO (خدمة برو)

**التاريخ:** 2026-10-25
**مرافق:** `PROJECT_AUDIT_REPORT.md` §7، §8
**الحالة:** مقترح — **لم يُنفَّذ أي دمج**

---

## مبدأavings

قبل أي توحيد، ثلاثة ضوابط:

| الضابط | التطبيق |
|---|---|
| **لا دمج لاختلاف الوظيفة** | صفحة "العملاء" وصفحة "المركبات" تبدوان متشابهتين لكنهما يخدمان عملاء مختلفين. **لا ندمجهما** |
| **لا مكوّنات عملاقة** | مكوّن بأكثر من ~400 سطر يصبح نقطة تعقيد جديدة. **نقسّم** |
| **الوظائف تبقى مستقلة** | كل دمج يجب أن يبقى لكل كيان منطق خاص به. **مكوّن عرض فقط** |

---

## القسم الأول — الفرص المؤكَّدة بالأدلة

### MERGE-01 — مكوّنات نظام التصميم غير مُتبنّاة

#### 1. معرّف الملاحظة
`MERGE-01` · P2 · **توحيد جزئي**

#### 2. الملفات المعنية
```
resources/js/Components/App/AppButton.vue
resources/js/Components/App/AppInput.vue
resources/js/Components/App/AppTextarea.vue
resources/js/Components/App/AppSelect.vue
resources/js/Components/App/AppCheckbox.vue
resources/js/app.js   (التسجيل العام — الأسطر 85-89)
```

#### 3. دليل التكرار
| المقياس | القيمة |
|---|---|
| `<button>` أصلي | **1,231** |
| `<AppButton>` | **24** |
| `<input>` أصلي | **799** |
| `<AppInput>` | 4 ملفات |
| `<AppSelect>` | **0 استخدام إنتاجي** |
| `<AppTable>`, `<AppModal>`, `<AppBadge>`, `<AppCard>` | **0 — غير موجودة أصلاً** |
| `SearchableSelect` (المكوّن الفعلي) | 173 في 98 ملف |
| ملفات تستخدم `var(--token)` | **10 من 400** (2.5%) |

**المشكلة الأعمق**: نظام التصميم **مُعرَّف** (`Design/tokens.json` → `tokens.css`)، ومحمَّل عالمياً (`app.js`)، لكن **97.5% من الواجهة تتجاهله**.

#### 4. التنظيم المقترح
**لا ندمج الصفحات. ندمج "الطبقة الأساسية".**

```
الخطوة 1: استخراج أصناف دلالية من tokens.css
   .btn / .btn--primary / .btn--danger / .btn--ghost / .btn--subtle
   .input / .select / .textarea / .checkbox
   .card / .badge / .table / .modal

الخطوة 2: AppButton/AppInput… تستخدم هذه الأصناف (تغيير داخلي، بلا مظهر)

الخطوة 3: تبنٍّ تدريجي — استبدال <button class="..."> بـ <AppButton variant="...">
```

**التحذير الأهم**: `AppSelect` **لا يقبل `options`** — يقبل `<slot>` فقط. لكن اختباره يمرّر `options`:
```
[Vue warn]: Failed setting prop "options" on <select>: value [object Object],[object Object] is invalid.
```
هذا **خلل حقيقي** في المكوّن، وليس مجردPreference.

#### 5. الوظائف التي يجب أن تبقى مستقلة
- `SearchableSelect` — **لا يُدمج** مع `AppSelect`. الأول بحثي بقاعدة بيانات، الثاني قائمة ثابتة. دمجهما يخلق مكوّناً بم_props معقدة.
- `Components/App/*.story.vue` — تبقى للعرض (Storybook-style)
- الاختبارات الحالية — تبقى

#### 6. الأثر المتوقع
- **الصيانة**: تغيير زر في مكان واحد بدل 1,231 نسخة
- **الأداء**: **لا تغيير** (المكوّنات نفسها)
- **الحجم**: **انخفاض** طفيف (حزمة أصغر عند استخدام `<AppButton>` بدل أصناف Tailwind المتكررة)
- **البصري**: **لا تغيير** إن نُفّذ بشكل صحيح (نفس المظهر بالضبط)

#### 7. المخاطر المحتملة
| الخطر | الاحتمال | التخفيف |
|---|---|---|
| تغيّر بصري غير مقصود | متوسط | فحص بصري قبل/بعد لكل صفحة |
| كسر صفحات لا نعرفها | منخفض | التبنّي **تدريجي** — صفحة في كل مرة |
| تبنّي `AppSelect` قبل إصلاحه | مؤكد | **إصلاح `AppSelect` أولاً** (MERGE-01a) |

#### 8. الاختبارات اللازمة
- اختبار بصري (لقطة شاشة قبل/بعد)
- اختبار وحدة لكل مكوّن (موجود لـ 4 من 5)
- اختبار تكامل: صفحة محوّلة تعمل بالكامل

#### 9. الأولوية
**P2** — والأثر **تراكمي**: كل شهر تأخير = ~30 صفحة تُكتب بالطريقة القديمة.

#### 10. القرار المقترح
> **توحيد جزئي** — ابدأ بإصلاح `AppSelect`، ثم استخراج الأصناف الدلالية، ثم تبنٍّ تدريجي. **لا تُنشئ** `AppTable`/`AppModal` جديدين إلا عند أول استخدام حقيقي (وإلا فسنخلق نقطة تعقيد جديدة).

---

### MERGE-01a — خلل `AppSelect` prop `options`

#### الأدلة
`resources/js/Components/App/AppSelect.vue` — `defineProps` لا يذكر `options`:
```js
const props = defineProps({
    id: ..., modelValue: ..., label: ..., hint: ..., error: ..., required: ..., disabled: ..., placeholder: ...
});
```
`options` غير معرَّف ⇒ يمرّ عبر `useAttrs()` ⇒ `v-bind="attrs"` على `<select>` الأصلي ⇒ attribute غير صالح.

```
[Vue warn]: Failed setting prop "options" on <select>: value [object Object],[object Object] is invalid.
  at patchDOMProp (node_modules/@vue/runtime-dom/dist/runtime-dom.cjs.js:603:13)
```

#### القرار
> **إصلاح.** خياران:
> - **(أ)** إضافة prop `options` مع دعم `value`/`label` + إبقاء الـ slot
> - **(ب)** تعديل الاختبار ليمرّر `<option>` عبر slot
>
> **توصيتي: (أ)** — لأن `SearchableSelect` (173 استخدام) هو المعتمد، والتوحيد بين `<select>` البسيط و`SearchableSelect` (بحث + صفحات) يجب أن يبدأ من توحيد الـ API لا من المكوّن.

**P1** (خلل فعلي، مُثبت في سجل الاختبارات) — لكنه **لا أثر إنتاجياً** حالياً (صفر استخدام إنتاجي لـ `AppSelect`).

---

### MERGE-02 — ملفات Vue ميتة

#### الأدلة
رسم بياني الاستيراد (Python: تحليل كل `import` في 400 ملف) + `grep -rn "import.*<Name>"` = **0 لكل اسم**:

| الملف | الأسطر | الدليل |
|---|---|---|
| `Components/Dashboard/_archived/` (9 ملفات) | ~1,600 | 0 import |
| `Components/Dashboard/AlertsWidget.vue` | 188 | 0 import |
| `Components/Dashboard/DashboardCustomizer.vue` | 241 | 0 import |
| `Components/Dashboard/RevenueChart.vue` | 170 | 0 import |
| `Components/Dashboard/StatsCard.vue` | 221 | 0 import |
| `Components/Dashboard/WeeklyWorkOrdersChart.vue` | 108 | 0 import |
| `Components/Dashboard/WorkOrdersStatusChart.vue` | 156 | 0 import |
| `Components/WorkOrders/WorkOrderItemModal.vue` | 734 | 0 import |
| `Components/Inventory/AddPartModal.vue` | 297 | 0 import (المطابقات أسماء دوال) |
| `Pages/Settings/Company/MyReceipts.vue` | 234 | 0 import |
| `Pages/Settings/Company/Tabs/SubscriptionHistoryTab.vue` | 45 | 0 import |
| `Pages/Settings/System/Sections/ColorsSection.vue` | 205 | 0 import |
| `Pages/Settings/System/Sections/MakesSection.vue` | 273 | 0 import |
| `Pages/System/Tenants/ShowComponents/TenantInfoCards.vue` | 381 | 0 import |
| `Components/ApplicationMark.vue` | 3 | 0 import |
| `Components/AuthenticationCardLogo.vue` | 25 | 0 import |
| **المجموع** | **~3,360** | |

#### التنظيم المقترح
حذف في **commit واحد** مع:
- تأكيد `vite build` ناجح
- تأكيد كل الاختبارات ناجحة
- تأكيد `php artisan route:list` بلا تغيير (لا مسار Inertia يشير إليها)

#### الوظائف التي يجب أن تبقى مستقلة
- `Components/App/*.story.vue` — للعرض، ليست ميتة
- `Components/App/*.test.ts` — مصاحبة
- `Design/` — نظام تصميم

#### الأثر
- **الصيانة**: إزالة ~3,360 سطر مربكة للمطوّر الجديد
- **الأداء**: **لا تغيير** (لم تكن في الحزمة — الـ Vite يتجاهل غير المستورد)
- **المخاطر**: منخفضة — لكن **يجب التحقق من `Dashboard/` مقابل `DashboardV2/`** (انظر V-10)

#### القرار
> **حذف** — بعد التحقق من `DashboardV2` (18+20 ملف). **P2**.

---

### MERGE-03 — `WorkOrderFormModal` مكرر

| النسخة | المسار | الأسطر | الاستخدام |
|---|---|---|---|
| أ | `resources/js/Components/WorkOrderFormModal.vue` | 442 | **0** |
| ب | `resources/js/Components/WorkOrders/WorkOrderFormModal.vue` | 1,301 | **4** (العملاء، المركبات، أوامر العمل ×2) |

#### القرار
> **حذف النسخة (أ) فقط.** النسخة (ب) هي المستخدمة ولها نفس اسم المكوّن (مربك). **P2** — سهل وآمن.

---

### MERGE-04 — دوال حساب رصيد العميل

#### الأدلة
نفس الحساب مُنفَّذ بطريقتين:

**الطريقة (أ) — N+1** (`app/Http/Controllers/App/VehicleController.php:88-99`):
```php
$vehicles->getCollection()->each(function ($vehicle) {
    if ($vehicle->customer) {
        $balance = $customer->getOutstandingBalance();   // ← استعلام لكل مركبة
```

**الطريقة (ب) — صحيحة** (`app/Http/Controllers/App/CustomerController.php:46`):
```php
// يُحسب الرصيد ضمن الاستعلام — بلا N+1
```

**التكرار الثالث** (`app/Http/Controllers/Api/WorkOrderController.php:154`) — نفس النمط (أ).

**النتيجة المُقاسة**: `PerformanceBaselineTest` يفشل — `/app/vehicles` = **25 استعلام** (الحد `< 25`).

#### التنظيم المقترح
```
استخراج helper مشترك:
  app/Support/CustomerCreditMetrics.php
    → attachTo(Collection $vehicles): void
      يحسب أرصدة كل العملاء دفعة واحدة ثم يربطها
```
يستدعيه: `VehicleController`، `Api\WorkOrderController`، `CustomerController`.

#### الوظائف التي يجب أن تبقى مستقلة
- `Customer::getOutstandingBalance()` — تبقى (تُستخدم في `WorkOrderStoreRequest` لفحص حد الائتمان — استعلام مفرد هناك مناسب)
- الفحص لحظي عند إنشاء أمر عمل (حد الائتمان) — **يجب أن يبقى استعلاماً مفرداً محدّثاً**، لا قيمة مخزّنة

#### الأثر
- **الأداء**: من 25 استعلام إلى ~6
- **الصيانة**: منطق واحد بدل ثلاثة
- **المخاطر**: متوسطة (أرقام مالية معروضة) — **يلزم اختبار تكافؤ**

#### الاختبارات
1. `PerformanceBaselineTest` ينجح
2. **اختبار تكافؤ**: نفس الرصيد/الحد/التجاوز قبل وبعد
3. صفحة المركبات تعرض نفس الأرقام

#### القرار
> **توحيد** — P2 (مرتبط بـ AUD-009).

---

### MERGE-05 — صفحات Detail ذات التبويبات

#### الأدلة
9 صفحات تُعرّف `activeTab` بلا مكوّن مشترك:

```
Pages/Customers/Show.vue          (2,696 سطر)  17 إشارة
Pages/Vehicles/Show.vue           (2,169 سطر)  19 إشارة
Pages/Quotes/Show.vue             (1,663 سطر)  14 إشارة
Pages/WorkOrders/Show.vue         (1,312 سطر)  25 إشارة
Pages/Invoices/Show.vue           (  885 سطر)  14 إشارة
Pages/HR/Employees/Show.vue
Pages/Purchasing/Invoices/Show.vue
Pages/Purchasing/Suppliers/Show.vue
Pages/Settings/Centers/Show.vue
```

**لا يوجد مكوّن تبويبات مشترك** — `Common/` فيها 7 ملفات فقط (ConfirmModal, LoadingSpinner, PartsDisplay, SignaturePad, SortIcon + اختباران).

#### التنظيم المقترح
```vue
<!-- Components/Common/DetailTabs.vue -->
<!-- عرض فقط: يستقبل تعريف التبويبات كـ prop -->
<DetailTabs :tabs="tabs" v-model:active="activeTab" />
```
التبويبات تُعرَّف في كل صفحة (الأسماء والأيقونات والمحتوى تختلف). **المكوّن لا يعرف المحتوى**.

#### الوظائف التي يجب أن تبقى مستقلة
- محتوى كل تبويب — يبقى في الصفحة
- تعريف التبويبات — يبقى في الصفحة
- منطق التبديل — يبقى في الصفحة

#### الأثر / المخاطر
- **الأثر**: متواضع على الكود، **مفيد على الاتساق البصري** (تبويبات متطابقة عبر 9 صفحات)
- **المخاطر**: مكوّن ورابط (prop) يفقد السياق

#### القرار
> **توحيد جزئي** — P3. **ليس أولوية** لأن الأثر المرئي محدود مقارنة بـ MERGE-01.

---

### MERGE-06 — النصوص الثابتة العربية

#### الأدلة

| الطبقة | العدد | أمثلة |
|---|---|---|
| قوالب Vue | **723** | `System/Website/Tabs/LandingTab.vue` (47)، `System/Profile/Index.vue` (29)، `System/Credits/WhatsappBalances.vue` (23) |
| متحكمات PHP | **508** | `CustomerImportExportController.php:59`، `CenterSettingsController.php:156`، `ReportsController.php:171-179`، `CompanyProfileController.php:101-104` |

**أسوأ حالة**: رؤوس CSV في `ReportsController.php:171-179` بالعربية **فقط** ⇒ **تقرير المدفوعات غير متاح بالإنجليزية**.

**حالة خاصة**: `__('تم الحفظ بنجاح')` — مفتاح حرفي بدل مفتاح في `lang/`.

#### التنظيم المقترح
| الأولوية | النطاق | العدد | التقنية |
|---|---|---|---|
| 1 | رسائل JSON للمستخدم | ~10 | `__('messages.…')` |
| 2 | `__('…')` بمفتاح حرفي | ~8 | استبدال بمفتاح في `lang/` |
| 3 | رؤوس CSV | ~20 | ترجمة + عمود إنجليزي أو تبديل حسب اللغة |
| 4 | قوالب SMS | ~4 | قالب في `lang/` |
| 5 | نصوص Vue | ~700 | `t('…')` |

#### الوظائف التي يجب أن تبقى مستقلة
- `company_profile.php` قوالب SMS — **لكل مستأجر نصوصه الخاصة** (من `Tenant::getNotificationTemplate`). **التieni**: الترجمة تُطبَّق على **القيمة الافتراضية** فقط، لا على نص المستأجر.

#### الاختبارات
- `translations:check` (بعد إصلاح AUD-002) لا يُبلّغ عن شيء
- اختبار يرصد النصوص الثابتة الجديدة (حارس انحدار)

#### القرار
> **توحيد** — P2. **الأثر التجاري حقيقي**: مستخدم إنجليزي يرى شاشة عربية.

---

### MERGE-07 — ألوان الأزرار المتنافسة

#### الأدلة

| اللون | الاستخدام |
|---|---|
| indigo | 2,522 |
| emerald | 2,018 |
| red | 2,238 |
| gold/amber | 1,879 |
| blue | 1,352 |
| violet | 656 |

**أكثر 20 نمط زر تكراراً** — كلها نسخ-لصق حرفي:
```
class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg"   ×10
class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 ..."      ×7
```

#### التنظيم المقترح
تحديد **لوحة دلالية** (لا اختيار ألوان جديدة):
```css
--color-action-primary     /* indigo → يبقى */
--color-action-success     /* emerald */
--color-action-danger      /* red */
--color-action-brand       /* gold  — لون الهوية */
--color-action-neutral     /* gray */
```
ثم استبدال `bg-indigo-600 hover:bg-indigo-700` ⇒ `btn btn--primary`.

#### القرار
> **توحيد** — P3. **ليس** إعادة تصميم. نفس الألوان، مسار واحد.

---

## القسم الثاني — فرص مُرشَّحة تحتاج تحليل إضافي

| # | الفرصة | لماذا تحتاج تحليلاً | الأولوية |
|---|---|---|---|
| CAND-01 | `Components/Dashboard/` (18) مقابل `Components/DashboardV2/` (20) | أيهما حيّ؟ هل V2 بديل كامل أم جزئي؟ يحتاج رسم بياني استيراد لكلاهما | P2 |
| CAND-02 | `Components/App/` مقابل `Components/Common/` | تقسيم غامض: ما الفرق؟ يحتاج جرد لكل ملف | P3 |
| CAND-03 | `Services/Purchasing/` (1 ملف، 567 سطر) + `PurchasingService` | تقسيم محتمل لملف كبير | P3 |
| CAND-04 | `Services/CustomerPortal/` (9 ملفات) | 9 خدمات لبوابة واحدة — هل تقسيم الوظائف واضح؟ | P3 |
| CAND-05 | `Components/Print/PrintTemplates/TemplateDefaultA4.vue` (1,950 سطر) + `TemplateThermal80.vue` | قوالب طباعة متشابهة — هل يمكن اشتقاق قالب مشترك؟ | P3 |
| CAND-06 | `Controllers/App/HR/` (12+) | تقسيم واسع — يحتاج جرداً | P3 |
| CAND-07 | `routes/web.php` (101 KB) | تقسيم إلى ملفات حسب النطاق | P3 |
| CAND-08 | مجلدات في جذر المشروع: `ca0f48f5-9ddc-4351-ab55-d95a57cd41af/`، 4 تقارير HTML (~3.6 MB) | تنظيف — **ليس دمجاً** لكن عبء على المستودع | P2 |

---

## القسم الثالث — تنظيم التصميم المشترك المقترح

> **لا نبدأ بإعادة تصميم.** أولا قائمة الفروقات (فوقها)، ثم الخطة.

### 3.1 الدور الحالي لملفات `resources/js/Design/`

| الملف | الأسطر | الدور | ملاحظة |
|---|---|---|---|
| `tokens.json` | ~200 | **مصدر واحد** للألوان والمسافات والخطوط والحركة | ✅ جيد — **يحتفظ** بدوره |
| `tokens.css` | 173 | المتغيرات المُولَّدة (`--color-*`, `--space-*`, `--font-*`) | ✅ يحتفظ |
| `reset.css` | 139 | تصفير الأنماط | ✅ يحتفظ |
| `typography.css` | 138 | أنماط الخطوط | ✅ يحتفظ |
| `resources/css/app.css` | 242 | أنماط Tailwind + عام | ⚠️ تداخل محتمل مع `Design/` — يحتاج مراجعة |

**الملاحظة**: `Design/` يُحمَّل **قبل** `app.css`؟ الترتيب في `app.js`:
```js
import '../css/app.css';   // السطر 1
import './bootstrap';
import '@/Design/tokens.css';       // السطر 6
import '@/Design/reset.css';        // السطر 7
import '@/Design/typography.css';   // السطر 8
```
`app.css` **قبل** `reset.css` ⇒ **`reset.css` قد يتجاوز أنماط `app.css`**. يحتاج مراجعة!

### 3.2 نظام التصميم المقترح

```
Design/
  tokens.json          ← مصدر واحد (موجود) ✓
  tokens.css           ← متغيرات (موجود) ✓
  reset.css            ← تصفير (موجود) ✓
  typography.css       ← خطوط (موجود) ✓
  components.css       ← جديد: أصناف دلالية مشتقّة من tokens
```

#### 3.2.1 الألوان — إضافة، لا استبدال

```css
/* components.css — مشتقّة من tokens.css */
:root {
  /* دلالية — تشير إلى الأساسي */
  --color-action-primary:   var(--color-brand-primary);
  --color-action-primary-hover: var(--color-brand-primary-hover);
  --color-action-success:    #10b981;   /* emerald الحالي */
  --color-action-danger:     #dc2626;   /* red الحالي */
  --color-action-info:       #2563eb;   /* blue الحالي */
  --color-action-neutral:    #f1f5f9;   /* gray الحالي */
}
```
**لا يغيّر أي لون ظاهر** — فقط يعطي اسماً موحّداً.

#### 3.2.2 الخطوط والمسافات — من `tokens.json` مباشرة

| الرمز | القيمة | الاستخدام |
|---|---|---|
| `--font-family-sans` | من tokens | الخط الأساسي |
| `--font-size-xs/sm/base/lg/xl` | من tokens | السلّم |
| `--space-1..10` | من tokens | المسافات |
| `--radius-sm/md/lg/xl` | من tokens | الانحناءات |
| `--shadow-xs/sm/md/lg` | من tokens | الظلال |
| `--motion-duration-fast/normal` | من tokens | الحركة |

#### 3.2.3 أنماط الأزرار والحقول

```css
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: var(--space-2);
  font-family: var(--font-family-sans);
  font-size: var(--font-size-sm); font-weight: var(--font-weight-medium);
  line-height: var(--line-height-normal);
  border-radius: var(--radius-lg);
  transition: background-color var(--motion-duration-fast) var(--motion-ease-standard);
  height: var(--size-control-md);
  padding-inline: var(--space-4);
}
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn--primary { background: var(--color-action-primary); color: #fff; }
.btn--primary:hover:not(:disabled) { background: var(--color-brand-primary-hover); }
.btn--danger  { background: var(--color-action-danger);  color: #fff; }
.btn--ghost   { background: transparent; border: 1px solid var(--color-border-strong); color: var(--color-text-primary); }
```

#### 3.2.4 الجداول والبطاقات

```css
.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xs);
}
.table { width: 100%; border-collapse: collapse; font-size: var(--font-size-sm); }
.table th { text-align: start; font-weight: var(--font-weight-semibold);
             color: var(--color-text-secondary); padding: var(--space-3); border-bottom: 1px solid var(--color-border); }
.table td { padding: var(--space-3); border-bottom: 1px solid var(--color-border); }
```

#### 3.2.5 النوافذ المنبثقة

لا يوجد مكوّن مشترك حالياً. `useConfirm.js` موجود — **راجعه أولاً**: قد يكون mechanism منبثقاً يمكن توحيده عليه.

#### 3.2.6 حالات التحميل والفراغ والخطأ

| الحالة | النمط الحالي | المقترح |
|---|---|---|
| تحميل | `LoadingSpinner.vue` (موجود — استخدام واحد فقط!) | تبنٍّ أوسع |
| فراغ | متفرّق | `.empty-state` موحّد |
| خطأ | `ConfirmModal` / `useToast` | `.error-state` + `useToast` |

**ملاحظة**: `LoadingSpinner` في 4 ملفات فقط رغم وجوده — نفس نمط MERGE-01.

#### 3.2.7 قواعد RTL/LTR

| القاعدة | التطبيق |
|---|---|
| **خصائص منطقية فقط** | `margin-inline-start` بدل `ml-`، `padding-inline-end` بدل `pr-` |
| **`text-align: start/end`** | بدل `left/right` |
| **`inset-inline-start`** | للأيقونات |
| **`dir="ltr"` للمحتوى ثنائي الاتجاه** | أرقام، بريد، VIN، مبالغ — **الممارسة الحالية صحيحة** (584 استخدام) |
| **أيقونات الاتجاهية** | `→` تتحوّل مع `dir` تلقائياً عبر الخصائص المنطقية |

**الوضع الحالي**: 92 خاصية فيزيائية (`ml/mr/pl/pr`) مقابل 65 منطقية (`ms/me`). تحويل تدريجي.

#### 3.2.8 الاستجابة للشاشات

| النقطة | السلوك |
|---|---|
| `< 640px` | قائمة جانبية مطوية، جدول → بطاقات، أزرار بعرض كامل |
| `640-1024px` | عمودان أو ثلاثة |
| `> 1024px` | تخطيط كامل |

**ملاحظة**: `AppLayout.vue` (2,593 سطر) يحتاج مراجعة منفصلة — حجمه وحده يستحق تتبّع.

---

## القسم الرابع — خطة التنفيذ

### 4.1 التسلسل المقترح

| الخطوة | المهمة | المدة | المخاطر |
|---|---|---|---|
| 1 | **إصلاح `AppSelect`** (MERGE-01a) | 0.5 يوم | منخفضة |
| 2 | **حذف الملفات الميتة** (MERGE-02) | 0.5 يوم | منخفضة |
| 3 | **حذف `WorkOrderFormModal` المكرر** (MERGE-03) | 15 دقيقة | منخفضة |
| 4 | **تنظيف جذر المشروع** (CAND-08) | 15 دقيقة | منخفضة |
| 5 | **توحيد حساب الرصيد** (MERGE-04) | 1 يوم | متوسطة — اختبارات تكافؤ |
| 6 | **استخراج `components.css`** | 1 يوم | منخفضة — بلا تغيير بصري |
| 7 | **توحيد الألوان الدلالية** (MERGE-07) | 1 يوم | منخفضة |
| 8 | **ترجمة النصوص ذات الأولوية** (MERGE-06 — رتبة 1-4) | 2 يوم | منخفضة |
| 9 | **تبنّي تدريجي** (MERGE-01 — صفحات) | 1-2 أسبوع/صفحة | متوسطة — فحص بصري |
| 10 | **صفحات Detail** (MERGE-05) | 2 يوم | منخفضة |
| 11 | **ترجمة بقية نصوص Vue** (MERGE-06 — رتبة 5) | 3-5 أيام | منخفضة |
| 12 | **تقسيم `routes/web.php`** (CAND-07) | 1 يوم | منخفضة — تحقق آلي |

### 4.2 قواعد إلزامية في كل خطوة

1. **لا كسر بصري** — لقطة شاشة قبل/بعد لكل صفحة
2. **اختبارات خضراء** بعد كل خطوة
3. **commit واحد لكل خطوة** — لا تجميع
4. **لا كسر التوازن** — لو كسرت خطوة، تراجعها فوراً
5. **`git status` نظيف** في نهاية كل خطوة

### 4.3 خطة اختبار ما بعد التوحيد

| المستوى | الاختبار |
|---|---|
| وحدة | اختبار لكل مكوّن جديد/معدَّل |
| تكامل | المسار الكامل لكل صفحة مُحوَّلة |
| بصري | لقطة قبل/بعد (صفحة × لغة × وضع فاتح/داكن) |
| بناء | `vite build` بلا تحذيرات جديدة |
| أداء | `PerformanceBaselineTest` بعد MERGE-04 |
| رجوع | `git revert` لكل commit |

### 4.4 ما لا ندمجه — قرار صريح

| لا ندمجه | السبب |
|---|---|
| `Customers/Show` + `Vehicles/Show` | كيانان مختلفان وظيفياً |
| `AppSelect` + `SearchableSelect` | أحدهما بحث بقاعدة بيانات والآخر قائمة ثابتة |
| `Services/CustomerPortal/*` | 9 خدمات بوابة — التقسيم واضح وظيفياً |
| `Components/Dashboard` + `DashboardV2` | **يحتاج تحليلاً أولاً** (CAND-01) |
| كل `*FormModal.vue` (23 ملف) | **نمط** مشترك (نوافذ + نموذج + حفظ) لكن **محتوى كل واحد فريد** — مكوّن عام واحد سيصبح مزدحماً. المقترح: توحيد **قالب** (هيكل + زر + حالة تحميل) عبر `v-slot` لا توحيد المحتوى |

---

## ملخص الفرص — جدول القرار

| # | الفرصة | الأولوية | القرار | الأثر المتوقع | المخاطرة | الاختبارات |
|---|---|---|---|---|---|---|
| MERGE-01a | `AppSelect` prop | **P1** | إصلاح | سلوك خاطئ مُصلَح | منخفضة | وحدة |
| MERGE-01 | نظام التصميم | P2 | توحيد جزئي | اتساق + صيانة | متوسطة | بصري |
| MERGE-02 | ملفات ميتة (15) | P2 | **حذف** | ‎-3,360 سطر | منخفضة | بناء + اختبارات |
| MERGE-03 | مودال مكرر | P2 | **حذف** | ‎-442 سطر | منخفضة | بناء + اختبارات |
| MERGE-04 | حساب الرصيد | P2 | **توحيد** | ‎-19 استعلام/صفحة | متوسطة | تكافؤ رقمي |
| MERGE-05 | صفحات Detail | P3 | توحيد جزئي | اتساق بصري | منخفضة | بصري |
| MERGE-06 | نصوص ثابتة (1,231) | P2 | **توحيد** | لغة سليمة | منخفضة | translations:check |
| MERGE-07 | ألوان متنافسة | P3 | توحيد | مرونة | منخفضة | بصري |
| CAND-08 | ملفات في الجذر | P2 | تنظيف | مستودع أنظف | منخفضة | — |

---

*نهاية خطة الدمج. لم يُنفَّذ أي دمج.*
