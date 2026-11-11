# 07 — تدقيق الإعدادات ومصادر البيانات (Settings & Data Sources)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

---

## 1. الحكم العام

> **غير مركزية — لكن المشروع يمتلك البنية الصحيحة بالفعل؛ المشكلة في غياب استخدام.**

الدرس الأهم: `Invoice::booted()` ينفّذ **تجميد بيانات البائع (snapshot)** بشكل ممتاز. المشكلة ليست غياب آلية «المصدر الموحد» — بل أن **الاسم والشعار** لا يستفيدان منها، وأن التون-consumer (الواجهة) يقرأ من مصادر متعددة.

---

## 2. جرد مصادر البيانات

### 2.1 اسم المنظومة — 6 مصادر متضاربة 🔴

| # | المصدر | القيمة | المستوى | الحالة |
|---|---|---|---|---|
| 1 | `config/app.php:18` | `env('APP_NAME', 'Khidmh Pro')` | منصة | ✅ **المعتمد** |
| 2 | `config/app.php:19` | `env('APP_NAME_AR', 'خدمة برو')` | منصة | ✅ **المعتمد** |
| 3 | `ar.json` → `common.app_name` | `'خدمة برو'` | منصة | ⚠️ نسخة |
| 4 | `en.json` → `common.app_name` | 🔴 **`'Pro service'`** | منصة | ❌ **ترجمة آلية معطوبة** |
| 5 | `resources/js/app.js:31` | `i18n.global.t(...) \|\| VITE_APP_NAME \|\| 'Laravel'` | منصة | ❌ **يسقط على `'Laravel'`** |
| 6 | `TemplateThermal80.vue:32,654` | `'مركز خدمة برو'` / `'خدمة برو'` | منصة | ❌ مثبَّت |
| 7 | `TemplateDefaultA4.vue:1336` | `'خدمة برو'` | منصة | ❌ مثبَّت |
| 8 | `Register.vue:573-574` | `'مرحباً بك في خدمة برو'` | منصة | ❌ مثبَّت |
| 9 | `CustomPage.vue:34-35` | `'خدمة برو'` / `'Khidmh Pro'` | منصة | ❌ مثبَّت |
| 10 | `PublicLandingController` / `LandingPreview.vue:71-77` | `settings.primary_color` من CMS | منصة | ⚠️ لون CMS |

**الأثر:** تغيير `APP_NAME` في `.env` **لا يصل** إلى: الواجهة الإنجليزية، قالبَي الطباعة، صفحة التسجيل، الصفحة العامة المخصصة.

> 🔴 **تعارض مباشر**: `config('app.name')` = "Khidmh Pro"، بينما `common.app_name` (en) = "Pro service". **المستخدم يرى اسمين مختلفين لنفس المنظومة** حسب الشاشة.

### 2.2 الهوية القانونية للمنصة

| الحقل | التخزين | المستوى |
|---|---|---|
| `company_legal_name` / `company_legal_name_en` | `payment_settings` (`PaymentSettings.php:31-32`) | منصة |
| `company_logo_path` | `payment_settings` (`PaymentSettings.php:36`) | منصة |
| `company_stamp_path` | `payment_settings` (`PaymentSettings.php:37`) | منصة |

> ⚠️ **`payment_settings` يجمع إعدادين مختلفين**: هوية **المنصة** (اسم قانوني + شعار + ختم) **وإعدادات الدفع**. خلطهما يعني أن تعديل إعدادات الدفع قد يمسّ هوية الفواتير.

### 2.3 هوية المستأجر

| الحقل | المصدر | السطر |
|---|---|---|
| `legal_name` | `tenants` | `Tenant.php:31` |
| `legal_name_en` | `tenants` | `:32` |
| `trade_name` | `tenants` | `:33` |
| `owner_name` | `tenants` | `:34` |
| `vat_number` | `tenants` | `:35` |
| `cr_number` | `tenants` | `:36` |
| `iban` | `tenants` | `:37` |
| `phone` / `email` | `tenants` | `:38-39` |
| `logo_path` | `tenants` | `:40` |
| العنوان | `tenant_addresses` (جدول منفصل) | `TenantAddress.php` |
| `timezone` | 🔴 **غير موجود** | — |
| `currency_code` | `tenant_tax_settings` | `TenantTaxSetting.php:27` |

### 2.4 هوية الفرع (Center)

| الحقل | المصدر | السطر (migration) |
|---|---|---|
| `name_ar` / `name_en` | `centers` | `2025_12_29_180000…:14-15` |
| `center_type` | `centers` — **نص حر!** | `:16` |
| `manager_name` | `centers` | `:15` |
| `license_number` | `centers` | `:17` |
| `vat_number` | `centers` | `:18` ⚠️ **ثالث موضع للرقم الضريبي** |
| `phone` / `email` | `centers` | `:20-21` |
| `logo_light_path` / `logo_dark_path` / `logo_invoice_path` / `stamp_path` | `centers` | `:24-27` |

> ⚠️ **`center_type` نص حر** (`مركز رئيسي` / `فرع الرياض` / `مستودع`) ⇒ لا يمكن فلترة الفروع حسب النوع برمجياً، والتصفية تعتمد على مطابقة نصية. **قاعدة بيانات لا تحكمها بيانات.**

### 2.5 الرقم الضريبي — 4 مواضع لنفس المفهوم 🔴

| الموضع | الكيان | السطر |
|---|---|---|
| `tenants.vat_number` | المستأجر | `Tenant.php:35` |
| `tenant_tax_settings.tax_number` | المستأجر (نطاق ضريبي) | `TenantTaxSetting.php:28` |
| `centers.vat_number` | الفرع | migration `:18` |
| `payment_settings` | المنصة | — |

> **مُتحقَّق**: `Invoice.php:132-134` يفضّل `$tenant->taxSettings?->tax_number ?: $tenant->vat_number` — أي أن **المصدر المعتمد فعلياً هو `tenant_tax_settings`**، و`tenants.vat_number` احتياطي.
> ⇒ **المصدر المعتمد مُعرَّف في الكود لكنه غير مُعلَن** ⇒ لا وجود لـ Single Source of Truth موثَّق.

### 2.6 المنطقة الزمنية 🔴

`config/app.php:71` ⇒ `'timezone' => 'UTC'` — **ثابت لكل المنصة**.

`tenants` **لا يملك عمود `timezone`** (تحققت: `grep -n "timezone" app/Models/Tenant.php` → فارغ).

⇒ **منصة سعودية تعمل بتوقيت UTC.** كل التواريخ تُخزَّن وتُعرض بتوقيت UTC، بينما المحلات في السعودية تعمل بتوقيت CST (UTC+3).

**الأثر:** مواعيد العمل، أوقات الدوام (HR)، وأوقات التقارير كلها **بـ UTC** ⇒ انحراف 3 ساعات على كل Whittall.

### 2.7 العملة

| المستوى | المصدر |
|---|---|
| المنصة | `payment_settings` |
| المستأجر | `tenant_tax_settings.currency_code` (default `'SAR'`) · `invoices.currency_code` default `'SAR'` |

---

## 3. من يقرأ ومن يُعيد الإدخال

### القارئات

| البيانات | القارئات |
|---|---|
| هوية المنصة | `HandleInertiaRequests` (مُتحقَّق: `app.js:31` يقرأ `common.app_name`)، `PublicLandingController`، `TemplateDefaultA4`، `TemplateThermal80`، `Register.vue`، `CustomPage.vue` |
| هوية المستأجر | `HandleInertiaRequests.php:146-163` (يشارك `tenant`)، `Tenant.php`، `Invoice.php:107-135` |
| هوية الفرع | `HandleInertiaRequests.php:165+` (يشارك `center` مع `display_name` محسوب) |
| الإعدادات الضريبية | `TenantTaxSetting` + `Invoice` snapshots |

### 🔴 مَن يُعيد الإدخال

| الشاشة | ما تُعيد إدخاله | السطر |
|---|---|---|
| `Pages/Settings/Company/Index.vue` | اسم الشركة · الشعار · العنوان · الرقم الضريبي · الهاتف · البريد | `Components/Settings/Company/CompanyProfileTab.vue` |
| `Pages/System/Settings/CompanySettings.vue` | **نفس الحقول** — نسخة المنصة | `:116-266, 828-894` |
| `Pages/System/Settings/Tabs/CompanySettingsTab.vue` | **نفس الحقول — نسخة ثالثة** | `:86-230, 759-825` |
| `Pages/Settings/Centers/Show.vue` | اسم الفرع · الشعار · الختم · العنوان | `:493-674, 1644-1677` |
| `Pages/Settings/Website.vue` | محتوى الموقع | `:272-343, 2139+` |

**المشكلة:** `CompanySettings.vue` و`CompanySettingsTab.vue` **مكرَّران بنسبة 194 سطراً مُتطابق** (`03_COMPONENT_INVENTORY.md` EV-07 #3)، و`Company/Index.vue` يكرّر 170 سطراً آخر.

⇒ **4 شاشات تتعامل مع نفس بيانات هوية الشركة** ⇒ اختلاف في الـ defaults والـ payload واحتمال الكتابة فوق بعضها.

---

## 4. 🔴 الفجوة الحرجة: استنساخ المستندات (Snapshots)

### 4.1 ما هو مُنفَّذ بشكل ممتاز ✅

`app/Models/Invoice.php:95-140` — نموذج ممتاز:

```php
protected static function booted(): void
{
    static::creating(function (Invoice $invoice): void {
        if (! $invoice->tenant_id) { return; }

        // Credit notes inherit the issuer on the original document.
        if ($invoice->seller_name_snapshot !== null || $invoice->seller_name_en_snapshot !== null) {
            $invoice->seller_vat_snapshot ??= '';
            $invoice->seller_cr_snapshot ??= '';
            return;   // ← إشعار دائن يرث مُصدِر المستند الأصلي
        }

        $tenant = Tenant::with('taxSettings')->find($invoice->tenant_id);
        if (! $tenant) { return; }

        /*
         * Empty values are frozen as empty strings, not as null.
         * Null is reserved for legacy rows whose issuer identity
         * was never captured, which may still fall back to the
         * live tenant data on reprint. A new invoice issued while
         * the tenant had no VAT/CR must keep that blank identity
         * even after those numbers are added later.
         */
        $invoice->seller_name_snapshot ??= (string) (
            $tenant->legal_name ?: ($tenant->legal_name_en ?: ($tenant->trade_name ?: $tenant->name))
        );
        $invoice->seller_vat_snapshot ??= (string) ($tenant->taxSettings?->tax_number ?: $tenant->vat_number);
        $invoice->seller_cr_snapshot ??= (string) $tenant->cr_number;
    });
}
```

**نقاط القوة المُتحقَّق منها:**
1. ✅ **`??=`** — لا يُعيد الكتابة فوق لقطة موجودة ⇒ الفاتورة القديمة **لا تتغير** عند تعديل بيانات المستأجر.
2. ✅ **الفراغ يُجمَّد كـ `''` لا `null`** — الفاتورة التي صدرت بلا رقم ضريبي **تبقى بلا رقم** بعد إضافته.
3. ✅ **`null` محجوز للصفوف القديمة** — وهي في إعادة الطباعة تسقط على البيانات الحية (سلوك مقصود وموثّق).
4. ✅ **إشعار الدائن يرث المُصدِر** — لا ينشئ هوية جديدة.
5. ✅ **تفضيل `taxSettings.tax_number`** — المصدر الأخص أولاً.

**الأعمدة (migration `2025_12_29_141556…:45-52`):**
`customer_name_snapshot` · `customer_vat_snapshot` · `customer_address_snapshot` · `seller_name_snapshot` · `seller_name_en_snapshot` · `seller_vat_snapshot` · `seller_cr_snapshot` · `tax_enabled_snapshot` · `pricing_mode_snapshot` · `tax_rate_snapshot` · `currency_code`

> **هذا صحيح معمارياً.** أي توصية بـ«أضف snapshot» هنا تكون **خاطئة**.

### 4.2 🔴 الفجوة: ما لا يُستنسَخ

| العنصر | مُستنسَخ؟ | الأثر |
|---|---|---|
| اسم البائع (عربي/إنجليزي) | ✅ | — |
| الرقم الضريبي والسجل التجاري | ✅ | — |
| اسم العميل ورقمه الضريبي وعنوانه | ✅ | — |
| إعدادات الضريبة (مفعّل/نسبة/نمط تسعير) | ✅ | — |
| العملة | ✅ `currency_code` | — |
| **الشعار** | ❌ **لا** | 🔴 **تغيير شعار المركز يغيّر فواتير صادرة سابقاً** |
| **الختم** | ❌ **لا** | 🔴 نفس المشكلة |
| **عنوان البائع** | ❌ **لا** | 🔴 تغيير عنوان المركز يغيّر مستنداً سابقاً |
| **هاتف/بريد البائع** | ❌ **لا** | 🔴 |

> ✅ **تأكيد مُستقل:** يوجد في شجرة العمل migration غير مُتتبَّعة باسم
> `database/migrations/2026_11_11_000000_add_seller_identity_snapshots_to_invoices.php`
> يضيف `seller_name_snapshot` · `seller_name_en_snapshot` · `seller_vat_snapshot` · `seller_cr_snapshot`
> على `invoices` و`subscription_invoices`، **مع دالة backfill** للبيانات الموجودة.
>
> ⇒ **هذا مستقلاً عمّا توصّيت به في هذا التقرير** — أي أن تحليل الفجوة **تطابق مع عمل منفصل قائم في الشجرة**.
> ⇒ **ملاحظة مهمة:** هذا الـ migration **غير مُتتبَّع في git** و**لم يُشغَّل** (لا يمكن التأكد)..
> كما أن الـ migration **لا يغطي الشعار ولا الختم ولا العنوان** — فالفجوة تبقى قائمة.

### 4.3 🔴 الأثر الملموس

مُتحقَّق: `centers.logo_light_path` · `logo_dark_path` · `logo_invoice_path` · `stamp_path` (migration `:24-27`).

**طباعة فاتورة صدرت العام الماضي ستطبع الشعار الحالي — لا شعار وقت الإصدار.**

> **هذا يناقض صراحةً مبدأ ZATCA في الثبات** الذي يطبّقه المشروع على بقية بيانات الفاتورة بنجاح.

**التوصية:** إضافة 4 أعمدة snapshot على `invoices`:
`seller_logo_snapshot` · `seller_stamp_snapshot` · `seller_address_snapshot` · `seller_phone_snapshot`

**قاعدة التسعير:**

| المكوّن | القاعدة |
|---|---|
| اسم/رقم/سجل البائع | ✅ **dinاميكي دائماً** (استنساخ) |
| الرقم الضريبي | ✅ استنساخ |
| إعداد الضريبة | ✅ استنساخ |
| **الشعار/الختم/العنوان** | 📌 **استنساخ عند الإصدار** (historian) |
| **اسم المنصة** | 📌 **نصف ديناميكي**: فواتير المنصة ⇒ استنساخ؛ صفحات المنصة ⇒ ديناميكي |
| **اسم المستأجر في تقرير** | 📌 استنساخ |
| **الإعدادات التشغيلية** (نسبة عتبة، تذكير) | ✅ **ديناميكي دائماً** |

---

## 5. المصدر الموحد المقترح (Single Source of Truth)

### 5.1 على مستوى المنصة

| الحقيقة | المصدر المعتمد | كيف تُقرأ في Backend | كيف تُقرأ في Frontend |
|---|---|---|---|
| اسم المنظومة (عربي) | `config('app.name_ar')` | `config()` | `usePage().props.app.name_ar` |
| اسم المنظومة (إنجليزي) | `config('app.name')` | `config()` | `usePage().props.app.name` |
| الشعار | `payment_settings.company_logo_path` | `PaymentSettings` | `usePage().props.app.logo` |
| الرقم الضريبي | `payment_settings` | — | `props.app.tax_number` |
| العملة الافتراضية | `payment_settings` | — | `props.app.currency` |
| الدعم الفني | `payment_settings` | — | `props.app.support` |

**التنفيذ — نقطة واحدة:** `app/Http/Middleware/HandleInertiaRequests.php`

```php
'app' => [
    'name'      => config('app.name'),
    'name_ar'   => config('app.name_ar'),
    'logo'      => $settings?->company_logo_path,
    'stamp'     => $settings?->company_stamp_path,
    'currency'  => $settings?->default_currency ?? 'SAR',
    'tax_number'=> $settings?->company_tax_number,
],
```

**الفائدة:**
1. 🔴 `app.js:31` يصبح `i18n.global.t('common.app_name')` **أو** `usePage().props.app.name` — **لا سقوط إلى `'Laravel'`**.
2. `common.app_name` في JSON يصبح **احتياطياً فقط** — أو يُحذف.
3. كل الصفحات تقرأ نفس المصدر ⇒ **لا تعارض**.
4. تغيير `.env` ⇒ `php artisan optimize:clear` ⇒ ينعكس في كل مكان.

### 5.2 على مستوى المستأجر

| الحقيقة | المصدر | الوصول |
|---|---|---|
| الاسم القانوني | `tenants.legal_name` | `props.tenant.legal_name` |
| الرقم الضريبي | 🔴 **`tenant_tax_settings.tax_number`** (المعتمد فعلياً في `Invoice.php:133`) | `props.tenant.vat_number` |
| السجل التجاري | `tenants.cr_number` | `props.tenant.cr_number` |
| الشعار | `tenants.logo_path` | `props.tenant.logo` |
| العنوان | `tenant_addresses` | `props.tenant.address` |
| **المنطقة الزمنية** | 🔴 **يجب إضافة `tenants.timezone`** | `props.tenant.timezone` |

**التوصية:** إعلان `tenant_tax_settings.tax_number` كمصدر معتمد في التوثيق، و**إلغاء** `tenants.vat_number` تدريجياً (أو تحويله إلى accessor يقرأ من `taxSettings`).

### 5.3 على مستوى الفرع

| الحقيقة | المصدر | الوصول |
|---|---|---|
| الاسم | `centers.name_ar` / `name_en` | `props.center.display_name` ✅ |
| النوع | `centers.center_type` (نص حر ⚠️) | `props.center.type` |
| الشعار | `centers.logo_light_path` / `logo_dark_path` | `props.center.logo` |
| شعار الفاتورة | `centers.logo_invoice_path` | `props.center.invoice_logo` |
| الختم | `centers.stamp_path` | `props.center.stamp` |

### 5.4 ⏰ المنطقة الزمنية — ثغرة حقيقية

**المقترح:**
1. Migration: `tenants.timezone` (string, default `'Asia/Riyadh'`).
2. `config/app.php` يبقى UTC (صحيح للتخزين).
3. كل عرض/حساب يحوّل عبر `Carbon::setTimezone($tenant->timezone)`.
4. ⚠️ **يحتاج ترحيل البيانات** — لا تغيير على جداول قائمة بلا خطة.

---

## 6. التخزين المؤقت وإبطال الكاش

| الحالة | السلوك الحالي | التوصية |
|---|---|---|
| `HandleInertiaRequests` shared props | **بدون كاش** — يُقرأ من DB كل طلب | كاش قصير (30-60 ثانية) بـ tags على `tenant`/`center` |
| `payment_settings` | يُقرأ مباشرة | كاش مع إبطال عند الحفظ |
| `tenant_tax_settings` | يُقرأ مباشرة | نفس الشيء |
| إعدادات الموقع العام (CMS) | يُقرأ مباشرة | كاش مع إبطال عند التحديث |
| `config('app.name')` | **مُجمَّع (compiled)** | `optimize:clear` بعد التغيير |

**لم يُتحقَّق:** ما إذا كان هناك كاش فعلي على هذه الإعدادات — يحتاج فحص `Cache::` في الـ controllers. (غير مُغلق ضمن حدود هذا التدقيق.)

**نمط الإبطال الموصى به:**

```php
// عند حفظ إعدادات المنصة
Cache::tags(['platform-settings'])->flush();

// عند حفظ إعدادات المستأجر
Cache::tags(['tenant-'.$tenantId, 'platform-settings'])->flush();
```

> ⚠️ **تحذير مهم:** كاش shared props في `HandleInertiaRequests` **يؤثر على كل طلب** ⇒ إبطال ناقص يعني أن المستخدم يرى بيانات قديمة. **الأمان هنا أهم من الأداء** — الاكتفاء بإبطال دقيق.

---

## 7. عزل المستأجرين في الإعدادات

| الآلية | الحالة | الدليل |
|---|---|---|
| `TenantScoped` | ✅ 60 موديل | `app/Models/Concerns/TenantScoped.php` |
| `CenterScoped` | ✅ 45 موديل | `app/Models/Concerns/CenterScoped.php` |
| `TenancyContext::tenantId()` | ✅ يرجع `null` صراحةً على `system/*` | `app/Support/TenancyContext.php:23-25` |
| `payment_settings` | ⚠️ **جدول عام** — بلا `tenant_id` | منصة فقط ✅ |
| `tenant_tax_settings` | ✅ له `tenant_id` | |
| `centers` | ✅ له `tenant_id` | |
| `tenants` | — هو نفسه الجذر | |

**⚠️ نقطة حراسة:** `TenantScoped.php:25` و`CenterScoped.php:24` **لا يطبّقان أي فلتر** عندما يكون السياق `null` — في **worker الطابور** أو **أمر console** أو **مسار `system/*`**.

⇒ **أي استعلام داخل job يعمل بلا tenant scope** ⇒ تسريب محتمل عبر الطابور.
⇒ **يجب أن يضبط الـ job العامل السياق صراحةً** (`TenancyContext::setTenantId($id)`) أو يمرر `tenant_id` صريحاً في الاستعلام.

**لم يُتحقَّق:** هل هناك job واحد؟ (`app/Jobs/` = 1 ملف) — **يجب فحصه**.

---

## 8. ملخص التعارضات

| # | التعارض | النوع | المخاطرة |
|---|---|---|---|
| 1 | `common.app_name` (en) = "Pro service" ≠ `config('app.name')` = "Khidmh Pro" | **متناقض** | 🔴 عالية |
| 2 | `app.js:31` يسقط على `'Laravel'` | **تعارض مع العلامة** | 🔴 عالية |
| 3 | `tenants.vat_number` vs `tenant_tax_settings.tax_number` | **مزدوج** | 🟠 متوسطة (مُفضَّل في الكود لكن غير مُعلَن) |
| 4 | `centers.vat_number` (ثالث موضع) | **تعارض** | 🟠 متوسطة |
| 5 | `center_settings.profile.vat_number` vs `company_profile.profile.vat_number` | **مصدران لنفس التسمية** | 🟠 متوسطة |
| 6 | `tenants.timezone` غير موجود ⇒ UTC لكل المنصة | **نقص** | 🔴 عالية |
| 7 | 4 شاشات تُعيد إدخال هوية الشركة | **تكرار** | 🟠 متوسطة |
| 8 | `payment_settings` يجمع هوية المنصة وإعدادات الدفع | **خلط مسؤوليات** | 🟡 منخفضة |
| 9 | `centers.center_type` نص حر | **ضعف حوكمة البيانات** | 🟡 منخفضة |
| 10 | الشعار/الختم/عنوان البائع غير مُستنسَخ على الفواتير | **نقص استنساخ** | 🔴 عالية |

---

## 9. خطة التنفيذ

| المرحلة | الفعل | المخاطرة | الجهد |
|---|---|---|---|
| **S-0** | 🔴 **تصحيح `en.json` → `common.app_name` = `'Khidmh Pro'`** | **صفر** | دقيقة |
| **S-0** | 🔴 **إزالة السقوط إلى `'Laravel'`** في `app.js:31` | **صفر** | 5 دقائق |
| **S-0** | إصلاح 6 انتهاكات اسم المنظومة في Vue | **صفر** | ساعتان |
| **S-1** | 🔴 **إضافة `app.*` إلى Inertia shared props** (اسم + شعار + عملة) | منخفض | نصف يوم |
| **S-1** | تحويل كل قارئات اسم المنظومة إلى `props.app.*` | منخفض | يوم |
| **S-2** | 🔴 **Migration: `invoices.seller_logo_snapshot` + `seller_stamp_snapshot` + `seller_address_snapshot`** وتعبئتها في `Invoice::booted()` | متوسط | يوم |
| **S-2** | تعديل قوالب الطباعة لقراءة اللقطة | متوسط | نصف يوم |
| **S-3** | **توحيد الرقم الضريبي** — إعلان `tenant_tax_settings` معتمداً · accessor لـ`tenants.vat_number` | متوسط | يوم |
| **S-3** | توحيد شاشات هوية الشركة (`CompanySettings` / `CompanySettingsTab` / `Company/Index`) — مصدر واحد + استنساخ | متوسط | 2-3 أيام |
| **S-4** | 🔴 **Migration: `tenants.timezone`** + ترحيل + تحويل كل التواريخ | 🔴 **عالٍ** — يمسّ جداول قائمة | 3-5 أيام |
| **S-5** | كاش + إبطال دقيق ل.shared props | متوسط | 2-3 أيام |
| **S-6** | توحيد `payment_settings` (فصل الهوية عن الدفع) | متوسط | يوم |
| **S-7** | `center_type` ⇒ enum | متوسط | يوم |

---

## 10. ⚠️ قواعد: ما يجب أن يبقى ديناميكياً

| البيانات | القاعدة | السبب |
|---|---|---|
| اسم المنصة في **صفحات المنصة** | ✅ **ديناميكي دائماً** | تغيير الاسم يجب أن ينعكس فوراً |
| اسم المنصة في **فاتورة صادرة** | 📌 **استنساخ** | مستند قانوني لا يتغير |
| نسبة الضريبة الافتراضية (للإعدادات الجديدة) | ✅ **ديناميكي** | إعداد تشغيلي |
| نسبة الضريبة (لفاتورة صادرة) | 📌 **استنساخ** ✅ مُنفَّذ | `tax_rate_snapshot` |
| عتبة تذكير الاشتراك | ✅ **ديناميكي** | إعداد تشغيلي |
| شعار المنصة في **الواجهة** | ✅ **ديناميكي** | هوية بصرية |
| شعار المنصة في **فاتورة** | 📌 **استنساخ** | مستند قانوني |
| اسم المركز في **تقرير حي** | ✅ **ديناميكي** | عرض |
| اسم المركز في **فاتورة** | 📌 **استنساخ** ✅ مُنفَّذ | مستند قانوني |

---

## 11. الخلاصة

| المحور | التقييم | الجملة القابلة للتنفيذ |
|---|---|---|
| استنساخ المستندات | ✅ **ممتاز** | `Invoice::booted()` — `??=` + تجميد الفراغ + توريث إشعار الدائن |
| فجوة الاستنساخ | 🔴 الشعار/الختم/العنوان | 4 أعمدة جديدة تكفي |
| مصدر اسم المنظومة | 🔴 6 مصادر | `HandleInertiaRequests` ⇒ نقطة واحدة |
| الترجمة الإنجليزية للاسم | 🔴 "Pro service" | سطر واحد |
| الرقم الضريبي | 🟠 4 مواضع | أعلن `tenant_tax_settings` معتمداً |
| المنطقة الزمنية | 🔴 غير موجودة | `tenants.timezone` — يحتاج ترحيل |
| هوية الشركة | 🟠 4 شاشات | مصدر واحد + استنساخ |
| الكاش | ⚠️ غير مُتحقَّق | ابدأ بدون كاش — الأمان أولاً |
| عزل المستأجرين | ✅ جيد، ⚠️ الطابور | `CenterScoped` يفتح بلا سياق — اضبطه في الـ jobs |
| `payment_settings` | 🟡 خلط مسؤوليات | افصل هوية المنصة عن الدفع |
