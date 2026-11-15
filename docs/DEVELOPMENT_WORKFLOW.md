# بروتوكول التطوير الموحّد الإلزامي — خدمة برو (Khidmah Pro)

> **الحالة:** `Active`
> **المرجع الأعلى:** [AGENTS.md](../AGENTS.md)
> **الوثائق المرتبطة:** [COMPONENT_REGISTRY.md](COMPONENT_REGISTRY.md) · [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) · [I18N_RULES.md](I18N_RULES.md) · [ARCHITECTURE.md](ARCHITECTURE.md) · [SECURITY.md](SECURITY.md) · [TESTING.md](TESTING.md) · [TASK_DELIVERY_TEMPLATE.md](TASK_DELIVERY_TEMPLATE.md)

---

## المرحلة 1: قبل التنفيذ (بوابة التخطيط والاكتشاف الإلزامية)

يُمنع كتابة أي سطر كود قبل إكمال جميع الخطوات التالية:

1. **فهم المتطلبات ونطاق المهمة:** تحديد المدخلات والمخرجات وحالات الخطأ والقيود التشغيلية دون توسيع النطاق بلا مبرر.
2. **تحديد اللوحة المستهدفة (Target Layout):** اختيار اللوحة الصحيحة (`AppLayout`, `SystemLayout`, `CustomerPortalLayout`, `EmployeePortalLayout`, `AuthLayout`).
3. **مراجعة الدستور:** التحقق من القواعد المنطبقة في [AGENTS.md](../AGENTS.md) ووثائق `docs/`.
4. **البحث عن المكونات والخدمات الموجودة:** فحص [COMPONENT_REGISTRY.md](COMPONENT_REGISTRY.md)، `app/Services/`، `app/Actions/`، و`resources/js/Composables/`؛ **يُمنع إنشاء مكون أو خدمة جديدة قبل إثبات عدم وجود بديل قابل لإعادة الاستخدام**.
5. **البحث عن الصفحات المشابهة:** فحص وحدة مشابهة قائمة في `resources/js/Pages/` و`app/Http/Controllers/` للاسترشاد بالنمط المعتمد في الجداول والفلاتر والنوافذ والطباعة.
6. **مراجعة سجل التكرارات:** الاطلاع على [DUPLICATE_COMPONENTS_REGISTER.md](../audit_reports/ui_compliance/DUPLICATE_COMPONENTS_REGISTER.md) لتجنب تكرار `DUP-001`..`DUP-008` (مثل خريطة ألوان المركبات أو تثبيت `'SAR'` أو الترجمة المضمنة `isRtl ? ... : ...`).
7. **تحديد مصادر البيانات المركزية:** الالتزام بجدول مصدر الحقيقة الواحد في [ARCHITECTURE.md](ARCHITECTURE.md) (مثل `page.props.app_name`, `page.props.tenant.currency`, `Setting::get()`, `Setting::forTenant()`).
8. **تحديد الصلاحيات وحدود المستأجر:** تحديد Policy/Gate، `TenantScoped`/`CenterScoped`، ومفاتيح `app/Support/Permissions.php`.
9. **إعداد خطة التنفيذ:** توثيق الملفات المستهدفة، المكونات المعاد استخدامها، وحالات الاختبار قبل بدء التعديل.

---

## المرحلة 2: أثناء التنفيذ (ضوابط البناء)

1. **إعادة استخدام المكونات الرسمية:** استخدام مكونات `App*` و`SearchableSelect` و`AppToggle` و`AppFileUpload` و`BaseModal` و`ConfirmModal` و`DataTable` و`PageHeader` من [COMPONENT_REGISTRY.md](COMPONENT_REGISTRY.md) فقط.
2. **منع التكرار والمكونات الموازية:** عدم استيراد المكونات `Deprecated` أو المكونات الموازية (`DashboardV2/common/*`)، وعدم نسخ منطق مكرر بين الصفحات.
3. **استخدام Design Tokens:** الاعتماد على متغيرات `resources/js/Design/tokens.css` وأصنافها الدلالية (`bg-surface`, `text-text-primary`, `bg-action-primary`, ...) وتجنب الألوان الصلبة (`gray-*`/`slate-*`/`indigo-*`/`violet-*`).
4. **دعم العربية والإنجليزية (`i18n`):** صفر نصوص ثابتة؛ جميع النصوص عبر `$t('...')` / `t('...')` في الواجهة و`__('...')` في الخلفية، مع تطابق `ar.json` و`en.json` وتجنب `isRtl ? '...' : '...'`.
5. **دعم الاتجاهين (`RTL / LTR`):** استخدام الخصائص المنطقية (`ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`, `text-start`, `text-end`) بدلاً من `ml-*`/`mr-*`/`left-*`/`right-*`، باستثناء الحقول التقنية الصريحة بـ`dir="ltr"`.
6. **دعم الوضعين (`Light / Dark Mode`):** الاعتماد على التوكنز الدلالية وضمان تباين واضح في كلا الوضعين.
7. **الالتزام بالمعمارية والصلاحيات:** فرض `$this->authorize(...)` في الخادم، وحفظ العمليات المالية/المخزنية داخل `DB::transaction()` مع `lockForUpdate()` وتسجيل `logActivity()`.
8. **عزل النطاق:** تجنب تعديل الملفات القديمة غير المرتبطة بالمهمة؛ وإذا اضطررت لتعديل ملف قديم، فلا تُدخل فيه مخالفات جديدة.

---

## المرحلة 3: بعد التنفيذ (بوابة الجودة والتسليم)

لا يجوز إعلان اكتمال أي صفحة أو ميزة قبل تنفيذ الفحوصات المنطبقة وتوثيق حالتها بصدق:

1. **البناء (`Build`):** `npm run build` للتحقق من تجميع Vite وتوليد `page-manifest.js` (إلا إذا طلب المستخدم التأجيل أثناء المراجعة الحية عبر HMR).
2. **الفحص اللغوي/البرمجي (`Lint`):** `npm run lint` و`composer lint` (`pint --test`) و`composer stan` عند تعديل PHP.
3. **فحص الأنواع (`Type Check`):** `npm run type-check` (`vue-tsc --noEmit`).
4. **فحص دستور الواجهة الآلي:** `npm run check:ui` (يفحص الملفات الجديدة والمعدلة مع عزل المخالفات القديمة، ولا يمنع `PASS` من الإبلاغ عن التحذيرات الجديدة).
5. **فحص الترجمة:** `node scripts/check-i18n-parity.mjs`.
6. **الاختبارات المرتبطة:** `php artisan test <TargetTest>` و`npm run test` (Vitest)، مع `bash scripts/check-tenant-scope.sh` عند المساس بالنماذج أو العزل.
7. **فحص التكرارات والصلاحيات:** مراجعة `git diff` للتأكد من عدم تكرار مكونات/دوال، والتحقق من تغطية `Policy`/Middleware.
8. **اختبار المتصفح (عند توفره):** فحص بصري وتفاعلي فعلي للوضع الداكن/الفاتح وRTL/LTR والنوافذ؛ **الفحوص الآلية لا تثبت الجودة البصرية**.
9. **تقرير تسليم موثق:** تعبئة [TASK_DELIVERY_TEMPLATE.md](TASK_DELIVERY_TEMPLATE.md).

---

## معيار تصنيف النتائج الإلزامي (Verification Status Rules)

يجب تصنيف كل بند في تقرير التسليم بدقة تحت إحدى الحالات الأربع فقط:

| الحالة | المعنى الملزم |
| :--- | :--- |
| **`PASS`** | تم تشغيل الفحص أو التحقق فعلياً بعد آخر تعديل ونجح بالكامل دون أخطاء. |
| **`FAIL`** | تم تشغيل الفحص وكشف فشلاً أو مخالفة؛ يجب توثيق السبب وموضعه دون إخفائه. |
| **`NOT VERIFIED`** | لم يتم تشغيل الفحص أو لم يُتحقق منه فعلياً (مثل الفحص البصري في المتصفح عند عدم تشغيله). |
| **`NOT APPLICABLE`** | لا ينطبق الفحص على نوع التغيير (مثل `composer stan` عند تعديل توثيق أو CSS فقط). |
