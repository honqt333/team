# تقرير التحقق — KHIDMH.PRO (خدمة برو)

**التاريخ:** 2026-10-25
**المرجع:** `PROJECT_AUDIT_SUMMARY.md` (والتقارير الخمسة المصدرية)
**الغرض:** تحقّق مستقل من صحة أهم النتائج — **تحقّق لا إصلاح**
**حالة المستودع:** `git status` نظيف — **لم يُعدَّل أي ملف تطبيق، ولم تُثبَّت أو تُحدَّث أي حزمة، ولم يُشغَّل أي migration**

---

## ⚠️ الخلاصة الأهم — نتيجة تغيّر الحكم

> **ثغرة `CVE-2026-114509` (تجاوز مصادقة حرجة) —Confirmed وجودها في الحزمة، لكنConfirmed عدم انطباقها على هذا المشروع.**

السبب: الثغرة في `TokenGuard` (سائق `token`)، والنظام **لا يستخدم هذا السائق إطلاقاً**. هذا **يغيّر أولوية التوصية** — التحديث يبقى مطلوباً (سلوك الحزمة، لا أثر فعلي)، لكنه **ليس عاجلاً أمنياً** كما ظنّ التقرير الأول.

كما اكتُشف أن **قائمة ثغرات npm في التقرير الأول كانت ناقصة وخطأ في التصنيف**: الثغرات المؤثّرة في `dompurify` و `lodash-es` (اعتماديات **تشغيل** لا بناء) لم تُذكر — بينما ذُكر `postcss` (اعتمادية **بناء فقط**).

---

## 1. Laravel و CVE-2026-114509

### 1.1 الإصدار المثبَّت فعلياً

| الموضع | الإصدار | الدليل |
|---|---|---|
| `composer.lock` | **v12.56.0** | `composer.lock` → `laravel/framework` |
| `vendor/` المثبَّت فعلياً | **v12.56.0** | `vendor/composer/installed.json` |
| بيئة التشغيل الفعلية | **12.56.0** | `php artisan --version` → "Laravel Framework 12.56.0" |
| PHP | **8.5.0** (CLI) | `php -v` |
| `config.platform.php` | **8.3.30** | `composer.json` |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `.env` |

**الحالة: مؤكد.** الإصدار في القفل والمثبَّت والمُشغَّل — متطابقان.

### 1.2 ما هي الثغرة بالضبط

من الإ advisories الرسمية (Laravel GHSA-f9x3-7vq9-cph6):

| البند | التفصيل |
|---|---|
| **المكوّن** | `Illuminate\Auth\TokenGuard` — **سائق `token` حصراً** |
| **الآلية** | `user()` يفحص `! empty($token)` فقط ⇒ مصفوفة `[0]` تتجاوز الفحص ⇒ `retrieveByCredentials` يحوّلها إلى `whereIn` ⇒ على **MySQL/MariaDB** تُقارن VARCHAR رقمياً ⇒ يطابق مستخدماً غير مقصود |
| **الإصلاح** | `is_string($token)` في `user()` و `validate()` |
| **النسخ المتأثرة** | `<12.68.0` (فرع 12.x) · `<13.29.0` (فرع 13.x) |
| **شرط الاستغلال** | استخدام سائق `token` + **MySQL/MariaDB** |

> **من نص الإ advisory نفسه**: "The advisory covers the `token` driver only. **Sanctum and Passport register their own guard drivers and are not listed in it.**"

### 1.3 انطباقها على هذا المشروع — **لا ينطبّق**

**أدلة سلبية قاطعة (فحصُ الشيفرة، لا الاستنتاج):**

| الفحص | النتيجة | الأمر |
|---|---|---|
| كل الـ guards في `config/auth.php` | `web` (session) · `admin` (session) · `customer_portal` (session) | `config/auth.php` |
| سائق `token` مُعرَّف؟ | **لا** | `grep "'driver' => 'token'" config/ app/` ⇒ لا نتائج |
| `TokenGuard` مُشار إليه في كود المشروع؟ | **لا** | `grep -rn "TokenGuard" app/ config/ routes/` ⇒ لا نتائج |
| كل استخدامات `auth:` في المسارات | `auth:web,admin` (×2) · `auth:sanctum` (×2) | `grep -rhoE "auth:[a-zA-Z0-9_,]+" routes/ app/` |
| جدول `personal_access_tokens` | **غير موجود** | `grep -rln "personal_access_tokens" database/migrations/` ⇒ لا نتائج |
| كود ينشئ personal access tokens | **لا** | `grep -rn "createToken\|PersonalAccessToken" app/` ⇒ لا نتائج |
| سائق Sanctum | `sanctum` (مُسجَّل ذاتياً) | `vendor/laravel/sanctum/src/SanctumServiceProvider.php:25` |

**فحص الكود المصاب في النسخة المثبَّتة** (لإثبات أن مسار الكود موجود وغير مستخدم):
```php
// vendor/laravel/framework/src/Illuminate/Auth/TokenGuard.php:83
if (! empty($token)) {          // ← السطر المصاب (قبل الإصلاح: is_string(...))
```

**التصنيف النهائي: `مؤكَّد` (الخطر غير منطبق)** — الثغرة موجودة في الحزمة المثبَّتة، لكن **لا يوجد مسار في المشروع يصلها**.

### 1.4 الأثر المتبقي — **مخاطرة غير مباشرة**

| البند | التقييم |
|---|---|
| المساران بـ `auth:sanctum` | `/api/v1/ai/demo/describe-vehicle` و `/api/v1/work-orders/{workOrder}/suggestions` — محميّان بـ `Sanctum` (سائق مستقل) **لا** `TokenGuard` |
| `config/sanctum.php` | **غير موجود** ⇒ Sanctum يعمل بإعدادات افتراضية؛ `Guard.php:32` ⇒ `config('sanctum.guard', 'web')` |
| النتيجة العملية | Sanctum في وضع stateful (جلسة) — لا مسار `api_token` ⇒ **لا استغلال** |

**⇒Conclusion**: الخطر الفعلي على هذا المشروع = **منخفض جداً**. التحديث يبقى **مستحسناً** (أغلفة ثغرات مستقبلية، وتثبيت نسخة مُصلَحة)، لكنه **ليسTreat as emergency**.

**يحتاج تحقق إضافي**: بيئة الإنتاج قد تُعرّف سائق `token` في `config/auth.php` منفصلاً (غير المرحَّل). **يُنصح بفحص `config/auth.php` على الخادم مباشرة قبل إغلاق هذه البند.**

---

## 2. نتائج `composer audit` و `npm audit`

### 2.1 Composer — 60 ثغرة (2 حرجة، 9 عالية)

**الحزم المتأثرة فعلياً المثبَّتة** (من `composer.lock`):

| الحزمة | المثبَّت | المشكلة | الخطورة | الإصلاح | داخل القيود؟ |
|---|---|---|---|---|---|
| `laravel/framework` | v12.56.0 | `CVE-2026-114509` تجاوز مصادقة | **حرجة** | 12.68.0+ | ✅ `^12.0` |
| `phpoffice/phpspreadsheet` | 1.30.3 | `CVE-2026-45034` تجاوز إصلاح + 5 أخرى | **حرجة** | 1.30.5+ | ✅ (عبر maatwebsite) |
| `maatwebsite/excel` | 3.1.68 | `CVE-2026-84374` كتابة خارج القرص | عالية | 3.1.70 | ✅ `^3.1` |
| `dompdf/dompdf` | v3.1.5 | `CVE-2026-56722` **قراءة ملف محلي عبر SVG** + 5 | متوسطة/منخفضة | 3.1.6 | ✅ `^3.1` |
| `guzzlehttp/guzzle` | 7.10.0 | 10 ثغرات (أعلى `CVE-2026-69246`) | عالية | 7.15.2+ / 8.0.1+ | ✅ عابر |
| `league/commonmark` | 2.8.2 | 12 ثغرة (أعلى `CVE-2026-71488` DoS) | عالية | 2.10.1+ | ✅ عابر |
| `symfony/*` (7 حزم) | v7.4.8 | 8 ثغرات (أعلى `CVE-2026-45075` تجاوز `#[IsGranted]`) | عالية | 7.4.12+ | ✅ عابر |
| `league/flysystem` | 3.33.0 | `CVE-2026-102601` | منخفضة | 3.36.0 | ✅ عابر |

### 2.2 npm — **تصحيح مهم: القائمة في التقرير الأول كانت ناقصة**

`npm audit --omit=dev --json` يكشف **7 ثغرات في 5 حزم** — التقرير الأول ذكر 3 فقط (`postcss`, `source-map-js`, `nanoid`) و**omit** الاثنين الأهم:

| الحزمة | المثبَّت | في `package.json`؟ | نوع الاستخدام | الثغرة | **أثر فعلي** |
|---|---|---|---|---|---|
| **`dompurify`** | **3.4.5** | **م direct** `^3.4.5` | **تشغيل (runtime)** | 15 ثغرة — `SAFE_FOR_TEMPLATES` bypass (**حرجة**)، `IN_PLACE` bypasses | **عالٍ نظرياً — لكن لا ينطبق** (انظر أدناه) |
| **`lodash-es`** | **4.17.22** | **م direct** `^4.17.22` | **تشغيل (runtime)** | `CVE` حقن شيفرة عبر `_.template` (**عالية**)، prototype pollution | **منخفض** (انظر أدناه) |
| `postcss` | 8.5.6 | `^8.4.31` | **بناء فقط** | 4 ثغرات (أعلى `CVE-2026-45067`… path traversal) | **معدوم** — لا يُشحن للمتصفح |
| `source-map-js` | 1.2.1 | عابر | **بناء فقط** | DoS | **معدوم** |
| `nanoid` | 3.3.11 | عابر من postcss | **بناء فقط** | 3 ثغرات | **معدوم** |
| `@vue/server-renderer` | 3.5.41 | عابر | SSR (غير مستخدم) | XSS | معدوهي (المشروع CSR عبر Inertia) |

**تحليل الانطباق — `dompurify`:** التطبيق يستدعي `DOMPurify.sanitize()` **فقط**:
```
resources/js/Plugins/safeHtml.js:25,28
resources/js/Components/SystemAnnouncementBanner.vue:80
resources/js/Pages/System/Announcements/Show.vue:90
```
- **لا** تستعمل `IN_PLACE` (الشروط كلها `IN_PLACE`-specific)
- **لا** تستعمل `SAFE_FOR_TEMPLATES`
- `RETURN_TRUSTED_TYPE` غير مستخدم

⇒ **الوضع الافتراضي غير متأثر عملياً** ⇒ التصنيف: **مخاطرة محتملة منخفضة** (لا مؤكَّدة). التصحيح nonetheless مُستحسن (3.4.5 قديم كثيراً).

**تحليل الانطباق — `lodash-es`:** الاستيراد الوحيد في المشروع:
```
grep -rn "from 'lodash-es'" resources/js/  ⇒  { debounce } فقط
```
- `_.template` (حقن الشيفرة) — **غير مستخدم** ⇒ غير قابل للاستغلال
- `_.unset` / `_.omit` (prototype pollution) — **غير مستخدمين** ⇒ غير قابلين للاستغلال
⇒ **الأثر الفعلي = صفر عملياً**. التصنيف: **مخاطرة محتملة منخفضة**.

**الخلاصة الصادقة**: من 7 ثغرات npm، **5 هي أدوات بناء لا تُشحن**، والاثنتان المتبقيتان (runtime) **لا يستعملان المسارات المصابة**. ⇒ **لا يوجد ثغرة npm قابلة للاستغلال في الاستخدام الحالي** — رغم أن `dompurify` 3.4.5 deserves التحديث.

### 2.3 أثر `dompdf` SVG — يحتاج تحقق

`CVE-2026-56722` (قراءة ملف محلي عبر SVG في data-URI) يحتاج استخدام SVG داخل قوالب PDF. **لم يُفحص** — يُذكر كـ **يحتاج تحقق إضافي**.

---

## 3. مسار رفع ملفات الموظفين — تثبيت versus افتراض

### 3.1 السلسلة الكاملة (كل حلقات مُتحقَّقة)

```
Controller  app/Http/Controllers/App/HR/EmployeeDocumentsController.php:21-27
   validate(['file' => 'required|file|max:10240'])     ← لا mimes
   → $request->file('file')->store('employee-documents', 'public')
        ↓
Laravel    UploadedFile::store() → hashName()
           FileHelpers.php:50 → guessExtension()         ← ★ الامتداد محفوظ
           → Str::random(40) + '.' + extension
        ↓
Storage    storage/app/public/employee-documents/<hash>.<ext>
        ↓
Symlink    public/storage → /…/storage/app/public      ← مُتحقَّق بـ readlink -f
        ↓
Serving    Apache/nginx يخدم /storage/... مباشرة
           public/.htaccess: RewriteCond %{REQUEST_FILENAME} !-f
                             RewriteRule ^ index.php [L]
           ★ ⇒ الملف الموجود (الموجود فعلاً) لا يُعاد توجيهه ⇒ يُخدَم كما هو
        ↓
Display    resources/js/Components/HR/Tabs/DocumentsTab.vue:27
           <a :href="`/storage/${doc.file_path}`" target="_blank">
           ★ رابط مباشر — لا متحكم يمرّ عبر الصلاحيات
```

**لا يوجد متحكم تقديم/تنزيل** — `grep -rn "documents.*download" routes/ app/` ⇒ **لا نتائج**.

### 3.2 تصنيف المخاطر — **مؤكَّد** مقابل **يعتمد على الخادم**

| الخطر | التصنيف | الدليل |
|---|---|---|
| **الامتداد يُحفظ** ⇒ `.php`/`.html`/`.svg` متاح | ✅ **مؤكَّد** | `FileHelpers.php:50` `guessExtension()` |
| **الوصول عبر `/storage/...` بلا مصادقة** | ✅ **مؤكَّد** | رابط مباشر + لا متحكم + لا فحص صلاحية عند الخدمة |
| **XSS عبر `.html`/`.svg`** | ✅ **مؤكَّد** (لا يعتمد على إعداد الخادم) | المحتوى يُخدم بنفس أصل التطبيق ⇒ ينفّذ سكربت |
| **تنفيذ PHP** | ⚠️ **يعتمد على إعداد الخادم** | `.htaccess` لا يعيد التوجيه للملف الموجود؛ لكن التنفيذ يتوقف على: ① معالج PHP في Apache (مثلاً `AddHandler` يطابق `.php` في كل المسارات) ② `Options FollowSymLinks` ③ إعداد `Hostinger` المحدد. **لم يُفحص** |
| **تسريب عبر رابط guessable** | ✅ **مؤكَّد** (خصوصية لا تنفيذ) | `Str::random(40)` ⇒ غير قابل للتخمين، لكن الرابط قابل للمشاركة |
| **غياب `authorize()`** | ✅ **مؤكَّد** | `grep -c "authorize" EmployeeDocumentsController.php` ⇒ **0** |

### 3.3 تقييم النافذة الفعلية — **محدودة**

| العامل | القيمة | الأثر |
|---|---|---|
| مستخدمون بصفة `employee` | يصلون `/app/employees/{id}/documents` | لرفع ملف **على ملف موظف آخر** يحتاجون معرفة مُعرِّف الموظف |
| `Employee` model | `TenantScoped` + `CenterScoped`؟ | `Employee` يرث `CenterScoped` (حسب `check-tenant-scope.sh`) ⇒ المستأجر/الفرع مُقيَّدان |
| `store()` | **بلا `authorize()`** ⇒ أي مستخدم مُصنَّف بـ `employee` يستطيع الرفع على أي `employee_id` داخل نطاقه | مخاطرة **محدودة النطاق** (لا عبور مستأجرين) |
| **الملفات المرفوعة حالياً** | `ls storage/app/public/employee-documents/` ⇒ **المجلد فارغ** | ⇒ **لا يوجد ملف خبيث في الإنتاج حالياً** — الخطر **مستقبلي** |
| `destroy()` | `Storage::disk('public')->delete($document->file_path)` | `EmployeeDocument` يرث `TenantScoped` ⇒ `find` مقيّد بالمستأجر |

### 3.4 المقارنة —Sibling controllers تُقيّد النوع

| المتحكم | القاعدة |
|---|---|
| `WorkOrderMediaController:27` | `image|mimes:jpeg,png,jpg,gif|max:5120` ✅ |
| `WorkOrderMediaController:77` | `mimes:pdf,jpg,png|max:1024` ✅ |
| `UploadSignatureRequest:51` | `mimes:png,jpg,jpeg,svg,webp` + **فحص `<script>` في SVG** ✅ |
| `LeaveStoreRequest:32` | `mimes:pdf,jpg,jpeg,png|max:5120` ✅ |
| **`EmployeeDocumentsController:22`** | **`file|max:10240` — بلا `mimes`** ❌ **الاستثناء الوحيد** |

**التصنيف النهائي: `مؤكَّد`** (XSS + وصول بلا مصادقة) + `يحتاج تحقق إضافي` (تنفيذ PHP — يعتمد على إعداد Apache).

---

## 4. أسباب الفشل الأربعة وأقل إصلاح ممكن

### 4.1 PHPStan — السبب مؤكد، وأقل إصلاح **غير كافٍ**

**السبب**: `phpstan-baseline.neon` يشير إلى ملفين محذوفين:
- `app/Models/Billing/Installment.php` — **2** إدخالة (أسطر 2257, 2263)
- `app/Services/Billing/InstallmentService.php` — **5** إدخالات (أسطر 3805-3829)

الملفات محذوفة فعلاً (`ls` ⇒ لا وجود). وهي были **feature أُزيل في Phase 0** — الترحيل `database/migrations/2026_07_26_200000_remove_installments_references.php` يؤكّد ذلك.

**النتيجة**: PHPStan يخرج `exit 1` برسالة "Invalid entries in ignoreErrors" و**لا يحلّل شيئاً**.

**اختبار فعلي (بملف baseline مُعدَّل في `/tmp` — لم يُلمس المشروع):**
- حذف الـ 7 كتل ⇒ `exit 1` اختفى وPHPStan **بدأ التحليل**
- **لكن**: ظهر **469 خطأ** في **80 ملفاً**

**تحليل الـ 469:**

| المعرّف | العدد | التفسير |
|---|---|---|
| `property.notFound` | **250** | منها **213** على `Illuminate\Database\Eloquent\Model::$…` ⇒ **خاصية Eloquent سحرية** ⇒ مسألة **تهيئة Larastan** (تعميم `Model`)، **لا 213 خطأ حقيقياً** |
| `nullsafe.neverNull` | 77 | أنماط `?->` على أنواع غير nullable — أسلوب، غير سلوكي |
| `method.notFound` / `method.nonObject` / `argument.type` | 82 | **أخطاء حقيقية محتملة** |
| `ignore.unmatched` | 18 | إدخالات baseline لم تعد تطابق الواقع ⇒ الـ baseline قديم |
| `property.notFound` (غير Eloquent) | 37 | أمثلة: `Customer::$balance`، `Customer::$is_credit_exceeded`، `Quote::$total_tax` — **خصائص تُضَف وقت التشغيل** (كما في `VehicleController.php:92`) ⇒ properties ديناميكية |

**⇒ أقل إصلاح ممكن (مرحلتان):**

| # | الإصلاح | الحجم |
|---|---|---|
| **1** | حذف الـ 7 كتل من الـ baseline | **7 كتل** (734 → 727 إدخالة) |
| **2** | بعد ذلك: إما (أ) توليد baseline جديد بالأخطاء الـ 469 و**تعهد** بإصلاحها تدريجياً، أو (ب) تعميم `Model` في Larastan لتقليل الـ 213 | قرار |

**⚠️ تنبيه مُهم**: التقرير الأول (§17) وصف إصلاح PHPStan كـ "حذف 4 أسطر". **الرقم الصحيح = 7 كتل**. والأهم: **الإصلاح وحده يفتح باب 469 خطأ** ⇒ **ليس مهمة يوهي واحد**.

### 4.2 الاختبارات العشرة — الأسباب الثلاثة مؤكدة

#### (أ) `QuotesTest` — 7 فشل

**السبب**: `tests/Feature/QuotesTest.php:70` ينفّذ `Permission::create(['name' => 'quotes.view', ...])` **دون شرط**، بينما الترحيل `2026_10_22_190500_seed_missing_crm_and_website_permissions.php` يبذر **كل** صلاحيات `Permissions::all()` عبر `Permission::firstOrCreate()`.

**إثبات**: `php artisan tinker` ⇒ `quotes.view in Permissions::all(): YES`

**أقل إصلاح ممكن**: تغيير **5 أسطر** من `Permission::create(...)` ⇒ `Permission::firstOrCreate(...)`.
**الحجم**: ملف اختبار واحد — **بلا مساس بكود الإنتاج** ⇒ **مخاطرة ≈ صفر**.

#### (ب) `I18nCoverageTest` — 2 فشل

**السبب**: الأساس `708` (رُفع 5 مرات: 0 → 94 → 700 → 708) مقابل الحالي **713**.

**⚠️ السبب الجذري ليسNumerical**: `app/Console/Commands/TranslationsCheck.php:188`:
```php
if (! isset($definedKeys[$locale][$key])) {   // $definedKeys = lang/*.php فقط
```
المتغيّر **`$vueDefined`** (الأسطر 95-106، يحمل مفاتيح `resources/js/i18n/lang/*.json`) **لا يُستعمل في تحديد النقص** — يُستعمل فقط في فحص تطابق ar/en (السطر 199).

**اختبار فعلي للحساب بعد الإصلاح** (بمحاكاة منطقية على المخرجات الفعلية):

| القياس | القيمة |
|---|---|
| المفتاح المُبلَّغ ناقصاً حالياً | **713** |
| **بعد الإصلاح (سطر واحد)** | **13** |
| نسبة النقص الحقيقي | **1.8%** |

**أقل إصلاح ممكن**: **سطر واحد** في السطر 188:
```php
if (! isset($definedKeys[$locale][$key]) && ! isset($vueDefined[$locale][$key])) {
```
+ إعادة ضبط `MISSING_KEYS_EXPECTED` من `708` ⇒ `13` (سطران).

**التأكيد**: القائمة الـ 13 المكتشفة بـ Python تطابق **تماماً** القائمة في `PROJECT_TRANSLATION_AUDIT.md` §3 ⇒ **البيانات المُتحقَّقة من التقارير متّسقة**.

#### (ج) `PerformanceBaselineTest` — 1 فشل

**السبب**: `tests/Feature/PerformanceBaselineTest.php:150` — `assertLessThan(25, $queries)` والمقياس = **25** بالضبط. السبب: N+1 في `app/Http/Controllers/App/VehicleController.php:88-99` (`getOutstandingBalance()` داخل `each()`).

**أقل إصلاح ممكن**: **ليس في ملف الاختبار** — بل في المتحكم (حساب الأرصدة دفعة واحدة). ⇒ **مهمة كود إنتاج** ⇒ تحتاج اختبار تكافؤ رقمي.

### 4.3 فاحص الترجمات — (§4.2b أعلاه — نفس الجذر)

**التصنيف**: `مؤكَّد` (سطر واحد + سطران في الاختبار). **أقل إصلاح ممكن معروف بدقة**.

---

## 5. هل التحديثات تتطلب تغيير `composer.json` / `package.json`؟

### 5.1 Composer — **لا، ولا تغيير واسع** — مُثبت بـ dry-run

**اختبار فعلي (`composer update --dry-run` — لم يُثبَّت شيء):**

**أولاً: التحديث الجزئي يفشل** — وهذااكتشاف مهم:
```
$ composer update --dry-run laravel/framework maatwebsite/excel dompdf/dompdf
Problem 1
  - maatwebsite/excel 3.1.70 requires phpoffice/phpspreadsheet ^1.30.5
    but the package is fixed to 1.30.3 (lock file version) by a partial update
  Use the option --with-all-dependencies (-W)
```
⇒ **يجب استخدام `-W`**. (التقرير الأول ذكر الأمر بدون `-W` ⇒ **يخطئ**.)

**ثانياً: مع `-W` ينجح:**
```
$ composer update --dry-run -W laravel/framework maatwebsite/excel dompdf/dompdf
Package operations: 1 install, 68 updates, 1 removal
```

| القياس | النتيجة |
|---|---|
| **`composer.json`** | **لا يُعدَّل** — القيود الحالية `^12.0` / `^3.1` تسمح بالإصدارات المصحّحة |
| **عمليات** | **1 تثبيت + 68 تحديث + 1 إزالة** — لا حزم جديدة كبرى |
| **الإزالة الوحيدة** | `thecodingmachine/safe` v3.4.0 — عابر من `sabberworm/php-css-parser`؛ **لا يستعمله كود التطبيق** (`grep` ⇒ لا نتائج)؛ و`php-css-parser` v9.5.0 (المحدَّث) لم يعد يحتاجه |
| **التثبيت الوحيد** | `symfony/polyfill-time` v1.42.0 — polyfill لـ `time()` (PHP 8.4+). **ملاحظة**: ظهر لأن `symfony/*` يرتقّي إلى v7.4.21 |
| **الترقية الرئيسية** | `laravel/framework` v12.56.0 ⇒ **v12.70.1** (يعبر 14 إصداراً_patch) |
| **حزم أخرى** | `guzzle` 7.10.0 ⇒ 7.16.0 · `commonmark` 2.8.2 ⇒ 2.10.4 · `symfony/*` v7.4.8 ⇒ v7.4.21 · `phpspreadsheet` 1.30.3 ⇒ 1.30.7 |
| **`composer.lock`** | **يتغيّر** — هذا هو ملف التوصيف (lockfile) — متوقّع |

### 5.2 npm — **لا تعديل على `package.json` متوقع**

`npm audit fix` يعمل ضمن النطاقات المعلَنة (`^3.4.5` لـ dompurify ⇒ ≥ 3.4.16 متاح؛ `^4.17.22` لـ lodash-es ⇒ ≥ 4.17.23 متاح).
⚠️ **يحتاج تحقق**: `npm audit fix` قد يعدّل `package-lock.json` فقط، أو يرفع `package.json` إن كانت النطاقات مقيّدة. **لم يُنفَّذ** (ممنوع) — يُنصح بـ `npm audit fix --dry-run` أولاً.

### 5.3 خلاصة §5

| السؤال | الجواب |
|---|---|
| تعديل `composer.json`؟ | **لا** — مُثبت بالـ dry-run |
| تعديل `package.json`؟ | **لا يُتوقع** — يحتاج `npm audit fix --dry-run` للتأكيد |
| تغيير واسع في الاعتماديات؟ | **لا** — 68 تحديثاً (ترقيات patch/minor داخل نفس السلاسل الكبرى) · صفر حزمة جديدة كبرى · صفر Major |
| الترقيات المُهدِّدة | `laravel/framework` (+14 patch) و `symfony/*` (+13 patch) — **سلوك مستقر، لا Major** |

---

## 6. جدول التصنيف النهائي

| # | النتيجة | التصنيف | الدليل | أقل إصلاح |
|---|---|---|---|---|
| V-01 | Laravel v12.56.0 مثبَّت فعلاً | **مؤكَّد** | lock + vendor + `artisan --version` | — |
| V-02 | CVE-2026-114509 موجودة في الحزمة | **مؤكَّد** | `composer audit` + advisory | `composer update` |
| V-03 | **CVE-2026-114509 لا تنطبّق على المشروع** | **مؤكَّد** | لا سائق `token` · لا `TokenGuard` · لا PAT · Sanctum سائق مستقل | **لا إصلاح عاجل** |
| V-04 | بيئة الإنتاج قد تعرّف سائق `token` | **يحتاج تحقق** | `config/auth.php` غير مرحَّل | فحص `config/auth.php` على الخادم |
| V-05 | 60 ثغرة Composer قابلة للإصلاح داخل القيود | **مؤكَّد** | `composer update --dry-run -W` ⇒ 1+68+1 | `composer update -W …` |
| V-06 | التحديث الجزئي **يفشل** — يلزم `-W` | **مؤكَّد** | رسالة Composer | استخدام `-W` |
| V-07 | `dompurify` 3.4.5 runtime مع 15 ثغرة | **مؤكَّد** (الثغرة) | `npm audit --json` | `npm audit fix` |
| V-08 | ثغرات dompurify **لا تنطبّق** (لا IN_PLACE ولا SAFE_FOR_TEMPLATES) | **مؤكَّد** | 3 استدعاءات `sanitize()` فقط | تحديث توصوي |
| V-09 | ثغرات lodash-es **لا تنطبّق** (`debounce` فقط) | **مؤكَّد** | `grep "from 'lodash-es'"` | تحديث توصوي |
| V-10 | 5 ثغرات npm هي أدوات بناء غير مشحونة | **مؤكَّد** | `npm ls` + سلسلة التبع | لا إجراء |
| V-11 | امتداد الملف المرفوع **محفوظ** | **مؤكَّد** | `FileHelpers.php:50` | تقييد `mimes` |
| V-12 | وصول `/storage/...` بلا مصادقة | **مؤكَّد** | رابط مباشر + لا متحكم | قرص خاص + متحكم مفوَّز |
| V-13 | XSS عبر `.html`/`.svg` | **مؤكَّد** | لا يعتمد على إعداد الخادم | تقييد `mimes` |
| V-14 | تنفيذ PHP | **يعتمد على الخادم** | `.htaccess` لا يعيد توجيه الملف الموجود | فحص إعداد Apache |
| V-15 | `EmployeeDocumentsController` بلا `authorize()` | **مؤكَّد** | `grep -c authorize` ⇒ 0 | `authorize()` |
| V-16 | مجلد `employee-documents` **فارغ** | **مؤكَّد** | `ls` ⇒ فارغ | لا ترحيل مطلوب |
| V-17 | PHPStan: 7 كتل baseline تشير لملفات محذوفة | **مؤكَّد** | `grep` + `ls` | حذف 7 كتل |
| V-18 | **التصحيح وحده يفتح 469 خطأ** (213 منها Eloquent سحري) | **مؤكَّد** | تشغيل فعلي بـ baseline مُعدَّل | baseline جديد + تعميم Larastan |
| V-19 | `QuotesTest`: `Permission::create` بلا شرط | **مؤكَّد** | `PermissionAlreadyExists` + tinker | `firstOrCreate` (5 أسطر) |
| V-20 | فاحص الترجمات: `$vueDefined` غير مستعمل في فحص النقص | **مؤكَّد** | السطر 188 مقابل 199 | سطر واحد + سطران |
| V-21 | الرقم الحقيقي للنواقص = 13 | **مؤكَّد** | حساب مستقل — يطابق التقرير | — |
| V-22 | N+1 في `VehicleController` (25 استعلام) | **مؤكَّد** | `PerformanceBaselineTest` | حساب دفعة واحدة |
| V-23 | `composer.json` لا يحتاج تعديلاً | **مؤكَّد** | dry-run | — |

---

## 7. التوصية — أول إصلاح آمن

### ⭐ المهمة الموصى بها: تحديث الحزم (0.1 + 0.2)

**الأمر بالضبط:**
```bash
composer update -W laravel/framework maatwebsite/excel dompdf/dompdf
npm audit fix
```

**لماذا هي أول إصلاح آمن:**

| المعيار | التقييم |
|---|---|
| **لا يمسّ منطقاً تجارياً** | ترقية حزم فقط — لا تغيير في كود التطبيق |
| **لا يحتاج تعديل `composer.json`** | مُثبت بالـ dry-run |
| **لا تغيّر واسع** | 1 تثبيت + 68 تحديث (patch/minor) + 1 إزالة (عابر، غير مستعمل) |
| **عائد فوري** | يغلق **2 ثغرة حرجة** + 9 عالية + 6 متوسطة/منخفضة، ويشحن `dompurify`/`lodash-es` مُحدَّثَين |
| **قابل للتراجع** | `git checkout composer.lock package-lock.json && composer install && npm ci` |
| **الاحتمال الوحيد الفعلي** | تغيير سلوك **توليد PDF** (dompdf) ⇒ **مراجعة بصرية إلزامية** للفاتورة الطرفية |

### متطلبات الاختبار

| # | المتطلب | الأمر | المعيار |
|---|---|---|---|
| 1 | التثبيت ينجح | `composer update -W …` | بلا خطأ؛ `composer.json` **غير مُعدَّل** |
| 2 | الإصدار الصحيح | `composer show laravel/framework` | **≥ 12.68.0** (المتوقّع 12.70.1) |
| 3 | الثغرات مُغلقة | `composer audit` | **لا يُبلّغ** عن `CVE-2026-114509` ولا `CVE-2026-45034` |
| 4 | npm | `npm audit --omit=dev` | **0 ثغرة** (أو ≤ ثغرتين runtime غير قابلتين للاستغلال موثّقتين) |
| 5 | **اختبارات Backend** | `php artisan test` | **349 ناجح / 10 فاشل** (نفس الأرقام قبل التحديث — لا زيادة) |
| 6 | **اختبارات Frontend** | `npx vitest run` | **29/29 ناجح** |
| 7 | **البناء** | `npm run build` | ناجح (≈ 34 ثانية) |
| 8 | **التنسيق** | `vendor/bin/pint --test` | ≤ 2 مخالفة (لا زيادة) |
| 9 | **عزل المستأجرين** | `bash scripts/check-tenant-scope.sh` | **0 مخالفة** |
| 10 | **PDF بصري** | فتح فاتورة مُولَّدة | **مطابقة تامة** مع ما قبل التحديث |
| 11 | **XLSX بصري** | فتح تقرير مُصدَّر | مطابقة البنية والأعمدة |
| 12 | **التراجع جاهز** | `git diff composer.json package.json` | **يجب أن يكون فارغاً** |

### معايير القبول (ملخص)

> ① `laravel/framework` ≥ 12.68.0 · ② `composer audit` بلا ثغرات high/critical · ③ `npm audit` بلا high/critical · ④ `composer.json` و `package.json` **غير مُعدَّلين** · ⑤ 349 اختباراً ناجحاً بلا زيادة في الفشل · ⑥ `vitest` 29/29 · ⑦ بناء ناجح · ⑧ `check-tenant-scope.sh` بـ 0 مخالفة · ⑨ مراجعة بصرية للفاتورة PDF.

### لماذا **ليس** PHPStan هو الأول

إصلاح PHPStan (حذف 7 كتل) **مؤكَّد أنه يفتح 469 خطأ** ⇒ هو **مهمة مفتوحة** لا إصلاح مختوهي. Whereas تحديث الحزم **مغلق**: أمر واحد + تحقّق + تراجع.

**الترتيب المقترح**: تحديث الحزم ⇒ `QuotesTest` (5 أسطر، مخاطرة ≈ صفر) ⇒ `TranslationsCheck` (سطر واحد) ⇒ **ثم** PHPStan مع قرار صريح بشأن الـ 469.

### ما الذي يبقى معلّقاً (يحتاج قرارك)

| القرار | الخيارات |
|---|---|
| **الـ 469 خطأ PHPStan** | (أ) baseline جديد + تعهد بإصلاح تدريجي **—توصيتي** (ب) إصلاح فوري كامل (عمل كبير) (ج) تعميم `Model` في Larastan لتقليل الـ 213 أولاً |
| **تأمين ملفات الموظفين** | (أ) تقييد `mimes` فقط — **5 دقائق، يقطع XSS** (ب) + قرص خاص + متحكم مفوَّز — عمل أكبر، يلزم ترحيل (المجلد فارغ حالياً ⇒ **ترحيل غير مطلوب**) |
| **بيئات الإنتاج** | هل `config/auth.php` فيها سائق `token`؟ يلزم فحص الخادم لتأكيد V-04 |

---

## 8. حدود هذا التحقق

| البند | الحالة |
|---|---|
| إعدادات Apache/Nginx في الإنتاج | **لم تُفحص** ⇒ تنفيذ PHP غير محسوهي |
| `config/auth.php` على الخادم | **لم يُفحص** ⇒ احتمال سائق `token` غير محسوهي |
| سلوك `dompdf` بعد التحديث | **لم يُختبر** (لم أُثبّت) |
| `npm audit fix --dry-run` | **لم يُنفَّذ** ⇒ أثره على `package.json` غير محسوهي بدقة |
| قابلية استغلال ثغرات dompurify/lodash في **سيناريو غير مغطّى** | تحليل ساكن — احتمال متبقٍّ منخفض |
| استخدام SVG داخل قوالب PDF | **لم يُفحص** ⇒ أثر `CVE-2026-56722` غير محسوهي |

**إجمالي الأوامر المُنفَّذة**: 40+ أمراً — **كلها read-only**. `composer update --dry-run` (لا تثبيت) · `npm audit` (لا تثبيت) · `php artisan tinker --execute` (قراءة) · `php artisan translations:check` (قراءة) · `route:list` (قراءة) · فحص ملفات. **لم يُشغَّل أي migration أو seed أو اختبار يعدّل البيانات.**

---

*نهاية تقرير التحقق. **لم يُنفَّذ أي إصلاح.** بانتظار موافقتك الصريحة.*
