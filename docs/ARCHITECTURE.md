# معمارية النظام ومصدر الحقيقة الواحد — خدمة برو (Khidmah Pro)

> **الحالة:** `Active`
> **المرجع الأعلى:** [AGENTS.md](../AGENTS.md)

---

## 1. طبقات الخلفية (Backend Layers)

| الطبقة | المسار | المسؤولية الحصرية |
| :--- | :--- | :--- |
| **Controllers** | `app/Http/Controllers/{App,System,CustomerPortal,Public}/` | استقبال الطلب، استدعاء Form Request، فرض التفويض (`$this->authorize(...)`)، التفويض إلى `Action` أو `Service`، وإرجاع Inertia أو JSON أو `RedirectResponse` مع رسالة Flash مترجمة (`->with('success', __('...'))`). |
| **Form Requests** | `app/Http/Requests/` | قواعد التحقق من المدخلات وحماية الحقول الممنوعة (مثل `'status' => ['prohibited']` في `WorkOrderUpdateRequest`). |
| **Actions** | `app/Actions/{Domain}/` | حالة استخدام واحدة محددة (Single Use-Case) مثل `UpdateWorkOrderAction`, `MergeCustomerAction`. |
| **Services** | `app/Services/` | منطق نطاق مشترك أو محركات حسابية وتكاملية متعددة الاستخدامات. |
| **Policies** | `app/Policies/` | قرارات التفويض على مستوى السجل والمستخدم (`view`, `create`, `update`, `delete`, `sign`, ...). |
| **Middleware** | `app/Http/Middleware/` | حماية السياق (`EnsureTenantActive`, `EnsureCenterContext`, `EnsureTwoFactorEnabled`, `EnsureSystemAdmin`, `HandleInertiaRequests`). |
| **Models** | `app/Models/` | تعريف Eloquent، العلاقات، `$fillable`, `$casts`، واستخدام `TenantScoped` / `CenterScoped` عند اللزوم. |
| **Observers & Events** | `app/Observers/`, `app/Events/`, `app/Listeners/` | الآثار الجانبية المفككة بين الوحدات (`InvoiceObserver`, `CenterObserver`, ...). |
| **Jobs** | `app/Jobs/` | المهام المؤجلة مع تمرير واستعادة سياق المستأجر والمركز عند التنفيذ. |

### قاعدة Service مقابل Action وعدد الأسطر
- استخدم **Action** عندما يمثل التدفق عملية مفردة واضحة (مثل تحديث كرت عمل أو دمج عميل).
- استخدم **Service** عندما يشترك أكثر من مسار أو متحكم في منطق حسابي/تخزيني/تكاملي (مثل `InvoiceService`, `PaymentManager`, `TaxCalculator`, `InventoryService`, `WorkOrderLaborService`, `QuoteSignatureStorage`).
- **عدد الأسطر** يُستخدم كمؤشر للمراجعة فقط، وليس معياراً وحيداً لجودة الكود.

---

## 2. المحركات المركزية المعتمدة (Canonical Domain Engines)

- **الفواتير والضريبة والمدفوعات:** `app/Services/InvoiceService.php`, `app/Services/Payment/PaymentManager.php`, `app/Services/Optimization/TaxCalculator.php`, `app/Services/CreditNoteService.php`.
- **ساعات العمل والفنيين:** `app/Services/WorkOrderLaborService.php`, `app/Models/WorkOrderLaborEntry.php`.
- **المخزون وحركات القطع:** `app/Services/Inventory/InventoryService.php`, `app/Models/InventoryMove.php`.
- **التوقيعات والصور:** `app/Services/QuoteSignatureStorage.php`, `app/Support/Media/ImageType.php`.
- **الطباعة:** `resources/js/Composables/usePrint.js`, `resources/js/Components/Print/PrintEngine.vue`.

---

## 3. المعاملات وقواعد البيانات (Transactions, Locking & Migrations)

- **Transactions & Locking:** أي عملية تمس بنود كرت العمل أو الفواتير أو المدفوعات أو المخزون يجب أن تتم داخل `DB::transaction()` مع `lockForUpdate()` على السجلات الأب/المتأثرة لمنع التداخل (Race Conditions).
- **حماية البيانات المالية والتشغيلية:** يُمنع حذف السجلات المرتبطة بحركات مالية أو مخزنية أو عمالة؛ استخدم Soft Deletes حيثما يعتمد النطاق الاسترجاع، وتحقق من العلاقات المحمية قبل الحذف أو الإلغاء.
- **المهاجرات (Migrations):**
  - **ممنوع** تعديل أي ملف Migration تم تشغيله؛ أنشئ دائماً ملف Migration جديداً يحتوي على `up()` و `down()`.
  - أضف فهارس (`index`) للأعمدة المتكررة في الاستعلامات (`tenant_id`, `center_id`, `status`, `created_at`).
  - انتبه لقيود MySQL الصارمة (مثل `unsignedInteger` التي لا تقبل قيماً سالبة).

---

## 4. مصدر الحقيقة الواحد للإعدادات والبيانات (Single Source of Truth)

| البيانات / الإعداد | مصدر التخزين | النطاق | القراءة (Backend) | التحديث وإبطال Cache | التمرير إلى Vue (`HandleInertiaRequests`) | الصلاحية |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **اسم النظام (عربي / إنجليزي)** | `config/app.php` (`.env`: `APP_NAME_AR`, `APP_NAME`) | System | `config('app.name_ar')` / `config('app.name')` | تعديل `.env` + `php artisan config:clear` | `page.props.app_name` (مع fallback `$t('common.app_name')`) | مدير الخادم / المنصة |
| **شعار النظام وأيقونته** | `settings` (`system.brand_logo`, `system.brand_favicon`, fallback `website.logo`/`favicon`) | System | `Setting::get(...)` | `WebsiteSettingsController` / `SystemSettingsHubController` مع حذف الملف القديم من `public` disk | `page.props.app_logo`, `page.props.app_favicon` | System Admin |
| **بيانات التواصل والدعم** | `settings` (`help.contact.*`) | System | `Setting::get('help.contact.*')` | `System\HelpSettingsController` | Props في `App\HelpController` | System Admin |
| **بيانات المنشأة والضريبة** | `tenants` + `tenant_tax_settings` + `tenant_zatca_settings` | Tenant | `$tenant`, `$tenant->taxSettings`, `$tenant->zatcaSettings` | `App\CompanyProfileController` + تنظيف الشعار القديم | `page.props.tenant.*` (`name`, `legal_name`, `trade_name`, `vat_number`, `cr_number`, `iban`, `logo_url`, `tax_settings`) | صلاحية إعدادات المنشأة |
| **الدولة، العملة، المنطقة الزمنية** | `tenants` + `App\Support\CountryCurrencyRegistry` | Tenant | `CountryCurrencyRegistry::tenantCountry/Currency($tenant)` | عند تحديث ملف المنشأة | `page.props.tenant.country`, `currency`, `currency_meta`, `timezone` | إعدادات المنشأة / System Admin |
| **الاشتراكات وبوابات الدفع** | `plans`, `subscriptions` + `payment_settings` (`PaymentSettings`) | System / Tenant | `PaymentSettings` (`Cache::remember('payment_settings', 3600)`), `$tenant->getSubscriptionLifecycle()` | `Cache::forget('payment_settings')` داخل `PaymentSettings` | `page.props.tenant.has_paid_subscription`, `subscription_lifecycle`, `trial_ends_at`, `subscription_ends_at` | System Admin |
| **إعدادات الفواتير والطباعة** | `tenants` (`quote_title`, `work_order_title`, `invoice_title`, `*_terms`, `print_settings`) | Tenant | `$tenant->print_settings` | عبر متحكمات إعدادات الشركة/الطباعة | `page.props.tenant.print_settings` والعناوين | صلاحية إعدادات الشركة/الطباعة |
| **إعدادات المستأجر الإضافية** | `settings` (`tenant_id` غير فارغ) | Tenant | `Setting::forTenant($tenantId, $key, $default)` | `Setting::setForTenant(...)` | حسب الصفحة المعنية | صلاحية الوحدة المعنية |
| **بيانات وإعدادات الفرع** | `centers` (`name`, `name_ar`, `name_en`, `vat_number`, `logo_invoice_url`, `logo_light_url`, `stamp_url`) | Center | `$user->currentCenter` | متحكمات إدارة الفروع | `page.props.center.*` | صلاحية إدارة الفروع |
| **تفضيلات المستخدم** | `settings` (`user_preferences.{$user->id}`) | User (ضمن Tenant) | `Setting::forTenant($user->tenant_id, ...)` | `App\ProfileController` | `page.props.preferences` | المستخدم نفسه |

### عقد اسم التطبيق في Inertia
- العقد الفعلي المعتمد هو **`page.props.app_name`** (يُشارك في `HandleInertiaRequests.php` ويُقرأ في `resources/js/app.js`).
- يُمنع استخدام `page.props.app?.name` لأنه غير معرّف في الاستجابة المشتركة.
