# دستور التطوير الهندسي الموحد — خدمة برو / Khidmah Pro
# Unified Engineering Constitution & Agent Governance

> **المرجع الأعلى والوحيد (Status: Active):**
> هذا الملف هو نقطة الدخول الرئيسية والمرجع التشغيلي الأعلى لجميع الوكلاء البرمجيين والمطورين.
> تُفصَّل القواعد التخصصية في وثائق `docs/` المرتبطة أدناه دون تكرارها هنا.
> يُمنع تجاوز القواعد الأمنية أو إنشاء خدمات أو مكونات موازية أو تعديل الملفات خارج النطاق المعتمد.

---

## 1. الهوية الرسمية والتعريف المعماري

- **الاسم الرسمي والوحيد للمنظومة:** **خدمة برو (Khidmah Pro)** — يُمنع استخدام الاسم التاريخي `Carag` في أي كود أو واجهة أو وثيقة جديدة.
- **طبيعة النظام:** منصة ERP سحابية مؤسسية متعددة المستأجرين والفروع لإدارة مراكز صيانة المركبات، المستودعات، الموارد البشرية، الاشتراكات، والمحاسبة والفوترة الإلكترونية.
- **الحزمة التقنية:** Laravel 12 (PHP 8.2+) · MySQL 8.0+ · Vue 3 (`<script setup>`) · Inertia.js 2 · Tailwind CSS · Vite.
- **التوافق العكسي (Backward Compatibility):** نظام إنتاجي حي؛ أي تغيير يجب ألا يكسر البيانات السابقة أو مسارات التشغيل القائمة.

---

## 2. بروتوكول العمل الإلزامي للوكلاء

1. **بوابة التخطيط والاكتشاف قبل التنفيذ (`DEVELOPMENT_WORKFLOW.md`):** افحص الموديل (`$fillable`, `$casts`, العلاقات)، المهاجرات، الخدمات، السياسات، سجل المكونات ([docs/COMPONENT_REGISTRY.md](docs/COMPONENT_REGISTRY.md))، سجل التكرارات، والترجمات وأعدّ خطة واضحة قبل كتابة أي سطر.
2. **نطاق المهمة و Git:** التزم بنطاق المهمة فقط، ولا تتراجع عن تعديلات المستخدم أو الوكلاء الآخرين.
3. **منع التكرار وإعادة الاستخدام:** **لا يجوز إنشاء مكون جديد قبل البحث عن مكون معتمد يؤدي الوظيفة نفسها** في [docs/COMPONENT_REGISTRY.md](docs/COMPONENT_REGISTRY.md)، ولا إنشاء خدمة أو مكون موازٍ عند وجود بديل قائم.
4. **بوابة الجودة والتحقق قبل الإغلاق:** **لا يجوز إعلان اكتمال صفحة جديدة قبل التحقق من التزامها بالقواعد المنطبقة وتوثيق الاختبارات المنفذة فعلياً** (`PASS` / `FAIL` / `NOT VERIFIED` / `NOT APPLICABLE`) عبر [docs/TASK_DELIVERY_TEMPLATE.md](docs/TASK_DELIVERY_TEMPLATE.md) ومراجعة `git diff`، مع توثيق أي فشل قائم (Baseline Failure) بصدق دون إخفائه.

---

## 3. القواعد الجوهرية (ملخص تنفيذي)

- **مصدر الحقيقة الواحد:**
  - اسم النظام في الخادم من `config('app.name_ar')` / `config('app.name')`، ويُشارك للواجهة عبر `page.props.app_name` (وليس `page.props.app.name`) مع fallback `$t('common.app_name')`.
  - الإعدادات العالمية عبر `Setting::get()`، وإعدادات المستأجر عبر `Setting::forTenant()` أو جداول `tenants` / `tenant_tax_settings` / `tenant_zatca_settings`، وإعدادات الفرع عبر `centers`. التفاصيل الكاملة في [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).
- **الأمان وعزل المستأجرين:**
  - التزم بـ `TenantScoped` و`CenterScoped` ولا تفرض `tenant_id` على الجداول العالمية.
  - التفويض الحقيقي عبر Policies/Gates (`$this->authorize(...)`) في الخادم؛ إخفاء الأزرار ليس حماية.
  - العمليات المالية والمخزنية والبنود المحمية تتم داخل `DB::transaction()` مع `lockForUpdate()`، وتسجَّل عبر `logActivity()`. التفاصيل في [docs/SECURITY.md](docs/SECURITY.md).
- **المكونات ونظام التصميم:**
  - الخط الوحيد المعتمد هو **`IBM Plex Sans Arabic`**، ومصدر الألوان والمسافات الفعلي هو `resources/js/Design/tokens.css`.
  - اعتمد مكونات `App*` (`AppButton`, `AppInput`, `AppTextarea`, `AppSelect`, `AppCheckbox`) و`SearchableSelect` و`AppToggle` و`AppFileUpload` و`BaseModal` و`DataTable` و`Toast`. المكونات القديمة (`PrimaryButton`, `TextInput`, `Checkbox`, `Toggle`, `Modal`, ...) **Deprecated** ويُمنع استخدامها في الكود الجديد. التفاصيل في [docs/COMPONENTS.md](docs/COMPONENTS.md) و[docs/DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md).
  - **حوكمة مهارات التصميم الخارجية:** تخضع مهارات الواجهات الخارجية (`impeccable`, `ui-ux-pro-max`, `design`, `design-system`, `ui-styling`) لقواعد التقييد في [.agents/rules/ui-skills-governance.md](.agents/rules/ui-skills-governance.md)، ولا تتجاوز `AGENTS.md` أو `docs/DESIGN_SYSTEM.md` أو `docs/COMPONENTS.md` أو `docs/COMPONENT_REGISTRY.md` أو `resources/js/Design/tokens.css`.
- **الترجمات:**
  - صفر نصوص ثابتة جديدة؛ استخدم `$t('...')` في الواجهة و`__('...')` في الخلفية، وحافظ على تطابق `ar.json` و`en.json`. التفاصيل في [docs/I18N_RULES.md](docs/I18N_RULES.md).
- **الاختبارات وبوابات الجودة:**
  - لا تعدل ملفات Migration تم تشغيلها.
  - طبّق مصفوفة الفحوصات (`php artisan test`, `composer lint`, `composer stan`, `npm run lint`, `npm run type-check`, `npm run test`, `npm run check:ui`, `npm run build`) بحسب نوع التغيير. التفاصيل في [docs/TESTING.md](docs/TESTING.md).
- **الاستثناءات والقرارات الهندسية (ADR):**
  - أي قرار معماري مؤثر أو استثناء دستوري يُوثّق في `docs/ADR/` وفق النموذج المعتمد في [docs/TESTING.md](docs/TESTING.md).

---

## 4. فهرس الوثائق التخصصية المعتمدة

| الوثيقة | الحالة | النطاق |
| :--- | :--- | :--- |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | `Active` | طبقات Backend، المحركات المركزية، Transactions، وخريطة مصدر الحقيقة الواحد |
| [docs/COMPONENTS.md](docs/COMPONENTS.md) | `Active` | حوكمة المكونات المختصرة والمكونات المتوقفة (`Deprecated`) |
| [docs/COMPONENT_REGISTRY.md](docs/COMPONENT_REGISTRY.md) | `Active` | السجل الرسمي التفصيلي للمكونات المعتمدة والمتخصصة والموازية (Props/Events/Slots/أمثلة) |
| [docs/DEVELOPMENT_WORKFLOW.md](docs/DEVELOPMENT_WORKFLOW.md) | `Active` | بروتوكول التطوير الإلزامي (قبل / أثناء / بعد التنفيذ ومعايير التحقق) |
| [docs/TASK_DELIVERY_TEMPLATE.md](docs/TASK_DELIVERY_TEMPLATE.md) | `Active` | القالب الموحد لتسليم تقارير الصفحات والميزات |
| [docs/DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md) | `Active` | `tokens.css`، الألوان الدلالية، الخطوط، Dark/Light، RTL/LTR، والوصول |
| [docs/brand/README.md](docs/brand/README.md) | `Active` | الشعار المعتمد «الترس والسهم»، المولّد `scripts/build-brandmark.mjs`، الملفات المولّدة، القيود الهندسية، وقواعد الاستخدام |
| [.agents/rules/ui-skills-governance.md](.agents/rules/ui-skills-governance.md) | `Active` | قاعدة Antigravity لتقييد مهارات التصميم الخارجية وتثبيت مصادر الحقيقة |
| [docs/I18N_RULES.md](docs/I18N_RULES.md) | `Active` | قواعد التسمية، المفاتيح الديناميكية، الأرقام/العملات، وفحص تطابق اللغتين |
| [docs/SECURITY.md](docs/SECURITY.md) | `Active` | التفويض، العزل، رفع الملفات الآمن، الاستثناءات، والسلامة المالية |
| [docs/TESTING.md](docs/TESTING.md) | `Active` | بوابات الجودة، الفحص الآلي، Baseline Failures، وحوكمة ADR |
| [docs/ADR/](docs/ADR/) | `Active` | سجل القرارات المعمارية الموثقة |

---

## الحساب والمركز المحفوظان للتطوير المحلي

يرتبط حساب المستخدم المعتمد `admin@khidmh.pro` محلياً بالمستأجر `active-workshop` والمركز الرئيسي `active-main-branch`. استخدم حساباً ومركزاً مستقلين عند تجربة تبديل الفروع أو الصلاحيات أو الحذف. حافظ على عضويات الحساب الحالية ومركزه وبيانات دخوله عند تشغيل أدوات التعبئة. شغّل تجارب إعادة التهيئة و`migrate:fresh` حصراً على قاعدة اختبار معزولة. تُحدّد هذه الهوية بالمفاتيح المذكورة ويُتحقق من تطابق المستأجر؛ أرقام الصفوف محلية وليست مرجعاً برمجياً.
