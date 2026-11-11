# 05 — تدقيق نظام التصميم والهوية البصرية (Design System Audit)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

---

## 1. الحكم العام

> **الأساس موجود واحترافي. المشكلة حوكمة وتبنٍّ، لا غياب أسس.**

المشروع يملك نظام توكن كامل (`resources/js/Design/tokens.css` + `tokens.json`)، استراتيجية dark mode سليمة، خط معتمد صحيح، مكوّنات معتمدة عالية التبنّي (`DataTable` 100 · `SearchableSelect` 125 · `PageHeader` 115). لكن **التطبيق لا يستهلك التوكن** — التخطيطات تستخدم `bg-gray-100` / `dark:bg-gray-900` الخام، بينما `DataTable` يستخدم `bg-surface` الدلالي.

**النتيجة:** مكوّن مشترك يبدو **مختلفاً بصرياً** عن محيطه. هذا بالضبط سبب «اختلاف اللوحات».

---

## 2. مصادر التوكن المُتحقَّق منها

| المصدر | المحتوى | السطر |
|---|---|---|
| `tailwind.config.js` | class-strategy للـ dark mode، خطوط، ألوان دلالية | `:18-89` |
| `resources/js/Design/tokens.css` | متغيّرات CSS: ألوان، مسافات، أنصاف أقطار، طباعة، حركة، ظلال، z-index | `:13-217` |
| `resources/js/Design/tokens.json` | نسخة JSON من نفس العقد | `:1-224` |
| `resources/js/Design/reset.css` | Reset، معالجة focus، reduced-motion | `:9-139` |
| `resources/js/Design/typography.css` | مقياس طباعي سائل، تطبيق الخط، helper للأرقام LTR | `:29-149` |
| `resources/js/Design/global-styles.css` | أصناف مساعدة: زر، بطاقة، input، شارة حالة | `:8-177` |
| `resources/css/app.css` | نقطة دخول Tailwind، تجاوزات input، قواعد الطباعة | `:1-243` |
| `resources/css/landing.css` | **نظام بصري منفصل للموقع العام** | `:35-2085` |

**ترتيب الاستيراد** في `resources/js/app.js:1-9`: Tailwind → tokens → reset → typography → global-styles. ✅ سليم.

---

## 3. الألوان المُقاسة

### 3.1 التوكن الدلالي

| التوكن | فاتح | داكن | السطر |
|---|---|---|---|
| `brand-primary` | `#2563eb` | `#3b82f6` | `:16` / `:182` |
| `brand-primary-hover` | `#1d4ed8` | `#60a5fa` | `:17` / `:183` |
| `brand-primary-active` | `#1e40af` | `#93c5fd` | `:18` / `:184` |
| `brand-on` | `#ffffff` | `#0f172a` | `:21` / `:187` |
| `action-primary` | `#2563eb` | `#3b82f6` | `:30` / `:196` |
| `accent-gold` | `#f59e0b` | `#fbbf24` | `:24` / `:190` |
| `background` | `#f8fafc` | `#0b1120` | `:38` / `:170` |
| `surface` | `#ffffff` | `#131d2e` | `:39` / `:171` |
| `surface-muted` | `#f1f5f9` | `#1e2a3e` | `:40` / `:172` |
| `border` | `#e2e8f0` | `#1e2d42` | `:42` / `:174` |
| `text-primary` | `#0f172a` | `#f1f5f9` | `:46` / `:177` |
| `text-secondary` | `#475569` | `#94a3b8` | `:47` / `:178` |
| `text-muted` | `#94a3b8` | `#64748b` | `:48` / `:179` |
| `success` | `#16a34a` | — | `:52` |
| `warning` | `#f59e0b` | — | `:54` |
| `danger` | `#dc2626` | — | `:56` |
| `info` | `#0ea5e9` | — | `:58` |
| `focus-ring` | `#2563eb` | — | `:62` |

### 3.2 ⚠️ ملاحظتان حقيقيتان

**(أ) عائلتان متطابقتان بلا معنى:**
`brand-primary` و`action-primary` لهما **نفس القيم تماماً** (`#2563eb` / `#3b82f6`). هذا اصطلاح مزدوج لعدد واحد — لا تمييز دلالي.

**(ب) ألوان feedback "soft" لا تُستبدل في الوضع الداكن:**
`--color-success-soft` (`#dcfce7`)، `warning-soft` (`#fffbeb`)، `danger-soft` (`#fee2e2`)، `info-soft` (`#e0f2fe`) — **مُعرَّفة خارج كتلة `:root.dark`** (`:167-216`).

**النتيجة:** خلفية فاتحة (أخضر فاتح) مع نص داكن في الوضع الداكن ⇒ **تباين ضعيف**. These are used directly in `resources/js/Design/global-styles.css:127-169` (the shared status badges) — أي **الشارات الشائعة نفسها** هي المتأثرة.

### 3.3 ألوان مثبتة خارج التوكن — **148 ظهور hex في 22 ملفاً**

| الملف | مثال |
|---|---|
| `Components/CustomerPortal/StatusBadge.vue:22-35` | `#dcfce7`, `#166534`, `#fef3c7`, `#92400e` |
| `Components/CustomerPortal/PrimaryCard.vue:24` | `#2563eb`, `#0ea5e9` |
| `Components/Quotes/QuoteFormModal.vue:988-1009` | لوحة ألوان طباعة كاملة |
| `Components/WorkOrders/WorkOrderFormModal.vue:1234-1255` | **لوحة الطباعة نفسها مكرَّرة** |
| `Components/Print/PrintLayout.vue:141` | `#3b82f6` |
| `Components/Print/StandardPrintControls.vue:29` | `#3b82f6` |
| `Components/Subscription/PaymentMethodSelector.vue:147-232` | ألوان طرق دفع |
| `Components/System/Website/PreviewPane.vue:56-74` | `#2563eb`, `#6366f1`, `#ffffff` |
| `Components/DashboardV2/widgets/StatusBreakdown.vue:9-30` | لوحة ألوان الرسوم |
| `Layouts/CustomerPortalLayout.vue:21,164,238-239` | `#f6f8fb`, `#2563eb`, `#0ea5e9` |

**والموقع العام** له نظام منفصل بالكامل: `resources/css/landing.css:35-74` — `--bg` `#050508`/`#fafafa`، `--text` `#ffffff`/`#0f172a`، `--text3` `#7c869b`/`#687384`.

> **الأثر:** تغيير لون العلامة التجارية في `tokens.css` **لن يؤثر** على الموقع العام ولا البوابة ولا لوحة الطباعة.

---

## 4. الطباعة

| الخاصية | القيمة | المصدر |
|---|---|---|
| Sans | `IBM Plex Sans Arabic`, `Noto Kufi Arabic`, `Inter`, `system-ui`, `-apple-system`, `sans-serif` | `tokens.css:116-122` |
| Display | `IBM Plex Sans Arabic`, `Noto Kufi Arabic`, `system-ui`, `sans-serif` | `tokens.css:118-120` |
| Mono | `JetBrains Mono`, `Fira Code`, `ui-monospace`, `SFMono-Regular`, `monospace` | `tokens.css:121` |

### الخط المعتمد

✅ **مُتحقَّق:** `IBM Plex Sans Arabic` هو **الخط الوحيد المُحمَّل فعلياً** — مُستضاف ذاتياً بمقاطع عربية ولاتينية، مُحمَّل مسبقاً في `resources/views/app.blade.php:45-64`، ومطبَّق في `typography.css:29-45` و `app.css:107`.

**لا يوجد** أي طلب Google Fonts أو خط محظور في الكود النشط. `Tajawal` يظهر فقط في تعليقات تاريخية تشرح إزالته (`typography.css:14-23`).

⚠️ **ملاحظةRigidity:** أسماء `Noto Kufi Arabic` و`Inter` و`JetBrains Mono` و`Fira Code` باقية في عقد التوكن كـ fallbacks. الدستور ينص على أن IBM Plex هو **الخط الوحيد** — حتى تسمية الـ fallback تخلق التباساً. وبما أن IBM Plex Sans Arabic لا يغطي الشيفرة الأحادية، فالعائلة أحادية `JetBrains Mono` مفيدة فعلاً — لكنها تحتاج **توثيقاً صريحاً** كاستثناء.

### المقياس الطباعي

| المستوى | القيمة |
|---|---|
| `xs` … `4xl` | `0.75rem` … `2.25rem` (`tokens.css:97-104`) |
| `h1` سائل | `clamp(1.875rem, 1.5rem + 1.5vw, 2.5rem)` (`typography.css:62`) |
| `h2` سائل | `clamp(1.5rem, 1.25rem + 1vw, 2rem)` (`typography.css:66`) |
| `h3` سائل | `clamp(1.25rem, 1.1rem + 0.6vw, 1.5rem)` (`typography.css:70`) |
| الأوزان | 400, 500, 600, 700 (`tokens.css:106-109`) |
| ارتفاع السطر | 1.25, 1.375, 1.5, 1.625 (`tokens.css:111-114`) |
| حجم HTML أساسي | سائل 15px–17px (`typography.css:29-34`) |

---

## 5. المسافات والأنصاف والظلال

### ⚠️ نظامان متوازيان للمسافات

`tokens.css:72-85` يعرّف 13 قيمة مسافة كمتغيّرات CSS. لكن **`tailwind.config.js` لا يربط `theme.extend.spacing` بهذه المتغيّرات** ⇒ Tailwind يستخدم سلّمه الافتراضي، بينما الأصناف المساعدة تستهلك المتغيّرات مباشرة (`global-styles.css:13-18`).

النتيجة: **نظامان متوازيان** يتفقان عند القيم المألوفة لكن **غير مربوطين ميكانيكياً**.

### أنصاف الأقطار — انحراف مقيس

| الموضع | القيمة | المصدر |
|---|---|---|
| `PageHeader` المعتمد | `rounded-2xl` | `Components/PageHeader.vue:63` |
| `PageHeader` الميت (Dashboard) | **بلا إطار** | `DashboardV2/common/PageHeader.vue:12-14` |
| `BaseModal` | `rounded-lg` | `Components/BaseModal.vue:32-33` |
| `DialogModal` | `rounded-lg` | `Components/DialogModal.vue:39` |
| `AuthShell` (بوابة العميل) | `rounded-[2rem]` | `CustomerPortal/AuthShell.vue:22` |
| `CustomPage` | `rounded-2xl` | `Public/CustomPage.vue:141` |
| `SystemLayout` تنقّل | `rounded-lg` | `SystemLayout.vue:60` |
| `AppLayout` تنقّل | `rounded-lg` | `AppLayout.vue:102` |
| `EmployeePortalLayout` تنقّل | `rounded-xl` | `EmployeePortalLayout.vue:49` |
| `CustomerPortalLayout` تنقّل | `rounded-2xl` / `rounded-xl` | `CustomerPortalLayout.vue:57-65` |
| `Public/Site/Index.vue` تنقّل | `rounded-lg` | `:129` |

**5 أنصاف أقطار مختلفة** في التنقل وحده.

### الظلال

`tokens.css:133-143` يعرّف `none, xs, sm, md, lg, xl, focus` — مع تجاوزات داكنة في `:210-214`. ✅ جيد.
لكن الموقع العام له ظلال خاصة (`landing.css:153, 209, 327-333`).

---

## 6. z-index — الفجوة المقيسة

| التوكن المعلن | القيمة | السطر |
|---|---|---|
| `dropdown` | 1000 | `tokens.css:145-153` |
| `sticky` | 1100 | |
| `modal` | **1300** | |
| `popover` | 1400 | |
| `toast` | 1700 | |

**الاستخدام الفعلي:** `z-50` في Tailwind (= 50) — `Layouts/AppLayout.vue:13` · `SystemLayout.vue:138-200` · `Components/Modal.vue:176,186` · `BaseModal.vue:112`.

> **إ_nodejsimportance:** الـ modal الفعلي z-index = **50**، بينما التوكن المُعلن = **1300**. أي عنصر بـ `z-[1000]` سيطفو **فوق** الـ modal. غير حرج اليوم (لا يوجد z-index من ذلك النوع)، لكنه **فخ قادم**.

---

## 7. الحركة (Motion)

| التوكن | القيمة |
|---|---|
| `fast` / `base` / `slow` / `slower` | 120ms / 180ms / 280ms / 400ms (`tokens.css:124-127`) |
| `standard` / `emphasized` / `decelerated` | `cubic-bezier(0.2,0,0,1)` … (`tokens.css:128-131`) |

**حركة غير مُدارة:** `transition: all 0.4s` (`landing.css:141`) · `all 0.2s` (`landing.css:247,288,326`) · `all 0.35s` (`landing.css:427-436`) · `0.3s ease` (`BaseModal.vue:150-158`) · `duration-300` (`Modal.vue:189-210`).

> `transition: all` على 0.4 ثانية في صفحة عامة تُنشئ أداءً رديئاً — يُنصح بتقييدها إلى خصائص محددة.

✅ **reduced-motion مُطبَّق عالمياً** (`reset.css:129-138`).

---

## 8. خريطة اللوحات — لماذا تختلف

### 8.1 الأصداف

| اللوحة | Layout | الهيدر | اللوحة اللونية | Dark Mode | RTL |
|---|---|---|---|---|---|
| `/system` (منصة) | `SystemLayout.vue` | شريط جانبي ثابت 56px | gray + indigo خام | ✅ `dark:` كامل | `isRtl` |
| مالك المركز | `AppLayout.vue` | جانبي + علوي | gray + indigo + emerald/amber/green/sky/violet | ✅ كامل | `isRtl` |
| إدارة الفروع | `AppLayout.vue` (صفحة مستأجر) | `PageHeader` المعتمد | inherits | inherited | منطقي |
| الموظفون/الفنيون | `AppLayout` (HR) + `EmployeePortalLayout` (`/app/my`) | منفصلان | **تدرجات purple→indigo** | ✅ | `isRtl` |
| بوابة العميل | `CustomerPortalLayout.vue` + `AuthShell.vue` | sticky + تنقّل سفلي | tenant primary/secondary | ❌ **يُلغى** | `dir` محلي |
| الموقع العام | `LandingPreview.vue` + `landing/*` | `LandingHeader.vue` | نظام منفصل | `[data-theme]` + root | `dir` محلي |
| مواقع المستأجرين | `Public/Site/Index.vue` | sticky خاص | slate + tenant primary | ✅ | `dir` محلي |
| عروض/فواتير/مشاركة | `AppLayout` + print layouts | مختلط | مختلط | مختلط | مختلط |

### 8.2 الأسباب الخمسة الملموسة

| # | السبب | الدليل |
|---|---|---|
| **أ** | **خمسة أصداف مستقلة** — ليست تنويعات لقشرة واحدة | `AppLayout.vue:2,42-53,97-106` · `SystemLayout.vue:2,7-17,44-64` · `EmployeePortalLayout.vue:2-21,43-52` · `CustomerPortalLayout.vue:17-43` · `AuthLayout.vue:19-24,43,63` |
| **ب** | **لوحات ألوان متضاربة** | التوكن أزرق `#2563eb` · التخطيطات indigo/purple/amber · الموقع العام `--primary` خاص · البوابة `--tenant-primary` |
| **ج** | **أنصاف أقطار مختلفة** | `rounded-lg` / `rounded-xl` / `rounded-2xl` / `rounded-[2rem]` |
| **د** | **عقود Dark Mode مختلفة** | البوابة **تحذف** صنف `dark`: `CustomerPortalLayout.vue:270-273` و`AuthShell.vue:189` |
| **هـ** | **استهلاك التون غير موحّد** | `DataTable.vue:126-135,151-163,193-211` دلالي ↔ `AppLayout.vue:2,45` خام |

**الجواب المختصر على «لماذا تختلف»:** خمسة أصداف بُنيت مستقلة، كلٌّ بلوحة ألوانه، وكلٌّ يستهلك التون بطريقته. **الـ tokens موجودة لكنها ليست إلزامية.**

---

## 9. التباين في الهيدر — ملاحظة دقيقة

`Components/PageHeader.vue:134-145` يعرض `extra` **قبل** `actions`.

| المتطلب | الحالة | الدليل |
|---|---|---|
| `rounded-2xl` | ✅ | `:63` |
| إطار زجاجي | ✅ | `:63` (`backdrop-blur-md`) |
| `p-4 sm:p-5` | ✅ | `:63` |
| عنوان + أيقونة + شارة عدد | ✅ | `:86-118` |
| شريط أدوات (مجموعة) | ⚠️ جزئي | slot `actions` عام `:139-145` |
| **زر إجراء رئيسي مميَّز** | ❌ **لا يوجد slot منفصل** | — |
| **تثبيت CTA في أقصى الزاوية** | ❌ **غير مضمون** | `flex-wrap` — `:139-145` |
| **ترتيب: شريط الأدوات قبل CTA** | ❌ **غير قابل لل imposition** | المُستدعي يضعه في `actions` |

**والأسوأ:** نسخة `DashboardV2/common/PageHeader.vue` **ميتة** (0 مرجع — `14_EVIDENCE_INDEX.md` EV-09) ⇒ ليست مشكلة تبنٍّ، بل **نسخة منسيّة**. وبينما هي ميتة، **لا يوجد slot للـ CTA في النسخة المعتمدة** ⇒ جولة واحدة (إضافة slotين) تُصلح بنيوياً.

---

## 10. الامتثال لسجل المكوّنات المعتمدة

| المكوّن | موجود | الاستخدام المُقاس | الحالة |
|---|---|---|---|
| `SearchableSelect` | ✅ | **125 ملف** | ✅ ممتاز |
| `AppToggle` | ✅ | **79 ملف** | ✅ ممتاز |
| `DataTable` | ✅ | **100 ملف** | ✅ ممتاز |
| `PageHeader` | ✅ | **115 ملف** | ✅ |
| `AppPrintButton` | ✅ | 32 ملف | ✅ |
| `CustomDatePicker` | ✅ | 39 ملف | ✅ |
| `AppViewModeToggle` | ✅ | 23 ملف | ✅ |
| `AppTabs` | ✅ | 20 ملف | ✅ |
| `AppPagination` | ✅ | 20 ملف | ✅ |
| `SaudiPlateDisplay` | ✅ | 15 ملف | ✅ |
| `ConfirmModal` | ✅ | 12 ملف | ✅ |
| `FormField` | ✅ | **11 ملف** | ⚠️ ضعيف |
| `SelectableCard` | ✅ | 10 ملف | ✅ |
| `SignaturePad` | ✅ | 3 ملف | ⚠️ محدود |
| `RadioGroup` | ✅ | **2 ملف** | ⚠️ مهمل |
| `FormSection` | ✅ | **0** | 🔴 **مطلوب في الدستور — ميت** |
| `SaudiPlateInput` | ✅ | 0 مباشر | عبر `VehicleFormModal` |

### HTML الخام — أرقام مقيسة

| العنصر | الوسوم | الملفات | بديل معتمد |
|---|---|---|---|
| `<button>` | **1,322** | **276** | `AppButton` (11 وسم فقط!) |
| `<input>` | **719** | **196** | `AppInput` |
| `<textarea>` | **122** | **67** | `AppTextarea` |
| `<select>` | **2** | **2** | ✅ ممتاز — لا تجاوز في الصفحات |
| `type="checkbox"` | 21 | 16 | `AppCheckbox` |
| `type="radio"` | 35 | 18 | `RadioGroup` |

> **`AppButton` مستخدم في 11 وسم فقط بينما `<button>` خام في 1,322 وسم.** هذا هو الفجوة الحقيقي في التبنّي — المكوّن المعتمد موجود لكن التبنّي ضعيف جداً.

---

## 11. RTL / LTR

### إدارة الاتجاه

| السطح | الآلية | السطر |
|---|---|---|
| عام | `document.documentElement.dir` حسب اللغة | `i18n/index.js:13-18` |
| المستأجر | يضبط الاتجاه بنفسه | `AppLayout.vue:3552-3554, 3655-3656` |
| الموظف | يضبط عند تغيير اللغة | `EmployeePortalLayout.vue:449-451` |
| البوابة | `dir` محلي على الجذر | `CustomerPortalLayout.vue:17-20` |
| الموقع العام | `dir` محلي | `Public/Site/Index.vue:71-74` · `LandingPreview.vue:276-278` |
| صفحة مخصصة | **`dir="rtl"` مثبَّت** | `Public/CustomPage.vue:46-49` |

### 🔴 العيب المؤكَّد

```js
// resources/js/Pages/Public/CustomPage.vue:16-18
const isRtl = computed(() => {
    return document.documentElement.dir === 'rtl' || true;   // ← || true يُبطل فرع LTR
});
```

**إصلاح بدالة واحدة.** صفحة عامة تُعرض دائماً RTL مهما كانت اللغة.

###_utilities الفيزيائية (تكسر في RTL)

| النطاق | العدد | الملفات |
|---|---|---|
| Pages | 130 | 62 |
| Components | 106 | 40 |
| Layouts | 29 | 4 |

أمثلة: `PageHeader.vue:68,72` (`-right-16`/`-left-16`) · `SystemLayout.vue:187` (`absolute top-0 right-0`) · `AuthLayout.vue:47` (`absolute -top-8 left-0`).

**✅ إيجابي:** `DataTable.vue:108-117,161-167` يستخدم `text-start`/`text-end` بشكل صحيح.

---

## 12. Dark Mode

✅ **الأساس سليم:** class strategy (`tailwind.config.js:18`) · تفضيل أولي من `localStorage`/`prefers-color-scheme` (`theme.js:5-18`) · صنف root مع حفظ (`theme.js:20-31`) · حالة مشتركة (`useTheme.js:17-47`) · تون داكن (`tokens.css:167-216`).

### التعارضات

| # | التعارض | الدليل |
|---|---|---|
| 1 | **البوابة تُلغي dark** | `CustomerPortalLayout.vue:270-273` تحذف صنف `dark` عند التركيب؛ `AuthShell.vue:189` كذلك |
| 2 | **الموقع العام لنظام ثانٍ** | `[data-theme='dark'/'light']` في `landing.css:35-74` + root `dark`/`light-mode` في `landing/useLanding.js:28-30` |
| 3 | **feedback soft غير داكن** | §3.2 (أ) |
| 4 | **ألوان فاتحة مثبتة** | `CustomerPortal/StatusBadge.vue:22-35` · `CustomerPortal/PrimaryCard.vue:24` · `System/Website/PreviewPane.vue:56-74` |
| 5 | **`CustomPage` دائماً RTL** | §11 |

---

## 13. الاستجابة (Responsive)

### ✅ ما يعمل

| العنصر | الدليل |
|---|---|
| الجوانب الجانبية تختفي تحت `md` | `AppLayout.vue:43-49` · `SystemLayout.vue:7-13` · `EmployeePortalLayout.vue:10-14` |
| بوابة العميل: تنقّل علوي → سفلي عند `md` | `CustomerPortalLayout.vue:57-74` → `:149-174` |
| `DataTable` يتمرّر أفقياً | `DataTable.vue:149` (`overflow-x-auto`) |
| المودالات متجاوبة | `Modal.vue:161-170,186,214-215` · `BaseModal.vue:32-33,68-73` |

### ⚠️ المخاطر

| المخاطرة | الدليل |
|---|---|
| هيدر الموقع العام قد ينضغط (شعار 180px + زران في صف واحد) | `Public/Site/Index.vue:92-126`؛ زر البوابة يختفي تحت `sm` فقط `:127-140` |
| **`CustomPage` تنقّل سطح مكتب بلا بديل جوال** | `Public/CustomPage.vue:94-110` → `hidden md:flex` بلا قائمة جوال |
| التذييل محاذاة فيزيائية | `CustomPage.vue:163-168` → `text-center md:text-right` |
| الهيدر المعتمد يلتفّ | `PageHeader.vue:77-79, 139-145` → `flex-wrap` |
| **41 جدولاً خاماً بلا `overflow-x-auto`** | `DataTable` محمي، لكن الجداول الخام لا ترث الغلاف |
| ارتفاع أدنى ثابت في بوابة العميل | `AuthShell.vue:25` (`min-h-[680px]`) · `:106` (`min-h-[620px]`) ⇒ صفحات طويلة على الجوال |

---

## 14. إمكانية الوصول (Accessibility)

### ✅ أسس قوية

| الميزة | الدليل |
|---|---|
| focus-visible عام | `reset.css:118-127` (حدود 2px بلون العلامة) |
| reduced-motion | `reset.css:129-138` |
| **`<dialog>` أصلي + focus trap + Escape + استعادة focus** | `Modal.vue:50-79, 107-139, 174-182` + `BaseModal.vue` |
| عنوان المودال مرتبط | `Modal.vue:66-71` يبحث `h1,h2,h3,[data-modal-title]` |
| الأرقام LTR في RTL | `typography.css:140-149` (`app-ltr-numerals`) |

### ⚠️ العيوب المؤكَّدة

| # | العيب | الدليل | الأثر |
|---|---|---|---|
| **A** | **أزرار أيقونة بلا اسم ميسَّر** | ~490 وسم `<button>`؛ الأمثلة بلا `aria-label`: `SystemLayout.vue:141-153` (هامبرغر)، `:279-313` (مبدّل المظهر)، `:315-329` (قائمة المستخدم)، `:167-191` (جرس الإشعارات — `title` فقط) · `EmployeePortalLayout.vue:169-191` | قارئ الشاشة يقرأ «زر» بلا معنى |
| **B** | **رؤوس الجداول القابلة للترتيب ليست عناصر تفاعلية** | `DataTable.vue:157-188` — `<th>` فيه `@click` فقط، بلا `tabindex`/`role`/`keydown` | **الترتيب بالكيبورد مستحيل** |
| **C** | **الصفوف القابلة للنقر ليست مفاتيح** | `DataTable.vue:193-204` — `<tr>` فيه `@click` بلا `tabindex`/`role` | مستخدم الكيبورد لا يستطيع فتح السجل |
| **D** | **عنوان `DialogModal` ليس عنواناً دلالياً** | `DialogModal.vue:28-31` — `<div data-modal-title>` | association تعمل، لكن outline المستند ضعيف |
| **E** | **إخفاء focus بلا بديل** | `EmployeePortalLayout.vue:97, 174` — `focus:outline-hidden` بلا حلقة بديلة | العنصر يفقد مؤشر التركيز |
| **F** | **`FormField` بـ11 ملف فقط** | مقابل عشرات النماذج | حقول بلا `<label>` مرتبط |

### مخاطر التباين (تحتاج اختباراً مُصيَّراً)

| الموضع | القيمة | المخاطرة |
|---|---|---|
| `text-text-muted` فاتح | `#94a3b8` على `#ffffff` | **منخفض لنص عادي** — Royal Blue contrast ≈ 2.6:1 |
| `text-text-muted` داكن | `#64748b` على `#131d2e` | منخفض |
| feedback soft في الوضع الداكن | خلفية فاتحة + نص داكن | **مرتفع** |
| landing `--text3` | `#7c869b` / `#687384` | منخفض |

> ⚠️ **لم يتم التحقق** — النسب هنا تقديرية من القيم لا من تصيير فعلي. يلزم اختبار تباين مُصيَّر (axe/Lighthouse) قبل ادعاء WCAG.

---

## 15. التصميم الموحد المقترح

### 15.1 عقد التون — نموذج كامل

```css
:root {
    /* Brand — مصدر واحد، و action-* يشير إليه */
    --color-brand-primary: #2563eb;
    --color-brand-primary-hover: #1d4ed8;
    --color-brand-primary-active: #1e40af;
    --color-brand-on: #ffffff;

    --color-action-primary: var(--color-brand-primary);
    --color-action-primary-hover: var(--color-brand-primary-hover);
    --color-action-primary-active: var(--color-brand-primary-active);
    --color-action-on: var(--color-brand-on);

    /* Surfaces */
    --color-background: #f8fafc;
    --color-surface: #ffffff;
    --color-surface-muted: #f1f5f9;
    --color-surface-raised: #ffffff;
    --color-border: #e2e8f0;
    --color-border-strong: #cbd5e1;

    /* Text */
    --color-text-primary: #0f172a;
    --color-text-secondary: #475569;
    --color-text-muted: #64748b;      /* ← مُصحَّح: #475569 لتمرير نص عادي */
    --color-text-inverse: #ffffff;

    /* Feedback — مع لون نص لكل حالة */
    --color-success: #16a34a;
    --color-success-soft: #dcfce7;
    --color-success-text: #166534;
    --color-warning: #f59e0b;
    --color-warning-soft: #fffbeb;
    --color-warning-text: #92400e;
    --color-danger: #dc2626;
    --color-danger-soft: #fee2e2;
    --color-danger-text: #991b1b;
    --color-info: #0ea5e9;
    --color-info-soft: #e0f2fe;
    --color-info-text: #075985;

    --color-focus-ring: #2563eb;
    --color-focus-ring-soft: rgb(37 99 235 / 0.35);
}

:root.dark {
    --color-background: #0b1120;
    --color-surface: #131d2e;
    --color-surface-muted: #1e2a3e;
    --color-surface-raised: #1a2537;
    --color-border: #1e2d42;
    --color-border-strong: #334155;

    --color-text-primary: #f1f5f9;
    --color-text-secondary: #94a3b8;
    --color-text-muted: #94a3b8;
    --color-text-inverse: #0f172a;

    /* ✅ يُضاف: ألوان feedback soft ونصها للوضع الداكن */
    --color-success-soft: rgb(22 163 74 / 0.16);
    --color-success-text: #86efac;
    --color-warning-soft: rgb(245 158 11 / 0.16);
    --color-warning-text: #fcd34d;
    --color-danger-soft: rgb(220 38 66 / 0.16);
    --color-danger-text: #fca5a5;
    --color-info-soft: rgb(14 165 233 / 0.16);
    --color-info-text: #7dd3fc;
}
```

### 15.2 الطبقات المقترحة

| الطبقة | المكوّنات |
|---|---|
| **Base** | `AppButton` · `AppIconButton` · `AppInput` · `AppTextarea` · `AppSelect` · `AppCheckbox` · `AppRadio` · `AppSurface` · `AppDivider` · `AppAvatar` |
| **Form** | `FormField` · `FormSection` · `FormActions` · `SearchableSelect` · `CustomDatePicker` · `AppToggle` · `RadioGroup` · `SelectableCard` · `SignaturePad` · `SaudiPlateInput` · `FormErrorSummary` |
| **Data display** | `DataTable` · `AppPagination` · `AppEmptyState` · `AppSkeleton` · `AppStatusBadge` · `MetricCard` · `KeyValueList` |
| **Navigation** | `AppTabs` · `AppSidebar` · `AppTopbar` · `AppNavItem` · `AppBreadcrumbs` · `AppViewModeToggle` · `AppCommandPalette` · `AppDropdownMenu` |
| **Feedback** | `AppToast` · `AppConfirmModal` · `AppDialog` · `AppAlert` · `AppLoadingState` · `AppErrorState` |
| **Business** | `WorkOrderHeader` · `WorkOrderTabs` · `InvoiceStatusBadge` · `VehiclePlate` · `CustomerIdentity` · `TenantBrandMark` · `EmployeeIdentity` |
| **Layout** | `SystemLayout` · `TenantLayout` · `CustomerLayout` · `PublicLayout` · `AuthLayout` · `PrintLayout` · `PageHeader` |

### 15.3 الأصداف الخمسة

| القشرة | الغرض | البنية |
|---|---|---|
| **SystemLayout** | إدارة المنصة | sidebar/topbar مُتوَّن، تنقّل نظامي مضغوط، بلا هوية مستأجر |
| **TenantLayout** | تشغيل المستأجر/المركز | sidebar/topbar مُتوَّن، هوية المستأجر، سياق المركز، مبدّل الفروع |
| **CustomerLayout** | بوابة العميل | تون هوية المستأجر، تنقّل آمن، تنقّل سفلي متجاوب، dark اختياري |
| **PublicLayout** | الموقع العام ومواقع المستأجرين | نطاق تون عام، تجاوز هوية المستأجر، محتوى CMS، بلا تنقّل مُصادَق |
| **AuthLayout** | توثيق (مستأجر/منصة/عميل) | قشرة موحّدة، شعار، تبديل مظهر ولغة |

> **قرار مطلوب من المالك:** `EmployeePortalLayout` هل يصبح **تنقّلاً داخل `TenantLayout`** (موصى به — يوحّدColour/HDR/Dark) أم **منتجاً منفصلاً** بلون بنفسجي مميَّز؟ هذه **قرار علامة تجارية** لا قرار تقني.

### 15.4 عقد `PageHeader` المقترح

```vue
<PageHeader :title="title" :total-count="total" :count-label="countLabel">
    <template #icon>…</template>
    <template #toolbar>
        <AppPrintButton /> <AppViewModeToggle /> <AppTabs />
    </template>
    <template #cta>
        <AppButton>＋ إضافة</AppButton>
    </template>
</PageHeader>
```

**الشكل المطلوب:**
```
[رجوع؟] [أيقونة] [عنوان + شارة العدد]  ─────────  [شريط الأدوات]  [CTA]
```

**متطلبات التنفيذ:**
- الجذر: `rounded-2xl` + `p-4 sm:p-5` + سطح دلالي + حد + `backdrop-blur-md`
- `justify-between` + `ms-auto` على شريط الأدوات
- **CTA آخر ابن في DOM** ومثبَّت بصرياً في الزاوية الخارجية
- على الشاشات الصغيرة يلتف الشريط تحت العنوان، وCTA يبقى أول صف الإجراءات
- **فصل slotين (`toolbar` / `cta`) يمنع الخلط بين الترتيب** — وهو جوهر إصلاح §9

---

## 16. خطة التوحيد البصري (عرضية فقط — لا مسارات ولا صلاحيات ولا عقود)

| المرحلة | الهدف | الملفات | الجهد |
|---|---|---|---|
| **V-0** | تثبيت العقد: تصحيح مسار `usePrint` في الدستور · قرار بوابة الموظف · إضافة slotين للـ CTA | `AGENTS.md` · `PageHeader.vue` | 0.5 يوم |
| **V-1** | **التون إلزامي**: ربط `spacing`/`radius`/`shadow`/`z-index` في Tailwind بالتون · إضافة foreground للحوالات · توحيد `brand`/`action` | `tailwind.config.js` · `tokens.css` | 1 يوم |
| **V-2** | استخراج `AppSidebar`/`AppTopbar`/`AppNavItem`/`AppUserMenu`/`AppThemeToggle` واستخدامها في System + Tenant | `AppLayout.vue` · `SystemLayout.vue` | 2-3 أيام |
| **V-3** | `PageHeader` + slotين · توحيد `AppStatusBadge` (5 عائلات → 1) · حذف `DashboardV2/common/PageHeader.vue` الميت | `PageHeader.vue` · 5 badge | 2-3 أيام |
| **V-4** | نقل البوابة العامة ومواقع المستأجرين إلى نفس عقد التون · **إيقاف حذف صنف `dark`** · إصلاح `CustomPage` RTL | `landing.css` · `CustomerPortalLayout.vue` · `AuthShell.vue` · `CustomPage.vue` | 2-3 أيام |
| **V-5** | RTL: `ms-`/`me-`/`ps-`/`pe-` بدل `left-`/`right-` (265 موضعاً) | 106 ملف | 3 أيام |
| **V-6** | a11y: `aria-label` لأزرار الأيقونات · `DataTable` قابل للترتيب/النقر بالكيبورد · إصلاح `focus:outline-hidden` | `DataTable.vue` · 2 layout | 2-3 أيام |
| **V-7** | الترحيل التدريجي: `AppButton` (1,322 وسم خام ⇒ هدف 300) · `AppInput` · `AppTextarea` · `FormField` | 276 ملف | 2-3 أسابيع |
| **V-8** | قيود ESLint: منع hex خام · منع `gray-`/`slate-`/`indigo-`/`purple-` خام في المكوّنات المشتركة · منع `<select>`/checkbox خام | `eslint.config.js` | 0.5 يوم |

### ⚠️ ترتيب مقترح فعلياً (مُعاد ضبطه)

**V-0 → V-1 → V-3 → V-4 → V-2 → V-5 → V-6 → V-7 → V-8**

**لماذا V-3 قبل V-2:** توحيد الهيدر والشارات **أعلى عائد بصري لكل ساعة** — 230 موقع استيراد، ومكون ميت يُحذف. واستخراج الأصداف (V-2) أتقلّ لأن `AppLayout.vue` ضخم (3,600+ سطر).

**لماذا V-8 في النهاية:** القيود فقط نافعة **بعد** أن يستقر النمط، وإلا تعطّل المشروع بundreds الأخطاء في يوم واحد.

---

## 17. ملخص النتائج

| المحور | التقييم | الجملة القابلة للتنفيذ |
|---|---|---|
| أساس التوكن | ✅ ممتاز | `tokens.css` + `tokens.json` كاملان |
| الخط | ✅ صحيح | IBM Plex Sans Arabic فقط — مُحمَّل ذاتياً |
| استيعاب التوكن | ❌ ضعيف | التخطيطات تستخدم `gray-`/`indigo-` الخام |
| توحيد اللوحات | ⚠️ 5 أصداف | استخرج `AppSidebar`/`AppTopbar` مشتركَين |
| توحيد الهيدر | ⚠️ | أضف slotين + احذف النسخة الميتة |
| توحيد الشارات | ⚠️ | 5 عائلات → `AppStatusBadge` واحدة |
| RTL | ⚠️ | 265 موضعاً فيزيائياً + عيب `\|\| true` |
| Dark Mode | ⚠️ | ألوان soft غير داكنة + البوابة تُلغي dark |
| a11y | ⚠️ | 4 عيوب محددة (أزرار بلا اسم، جدول غير قابل للكيبورد) |
| الاستجابة | ⚠️ | `CustomPage` بلا تنقّل جوال |
