# تقرير الميزات المفقودة — خدمة برو (Khidmh Pro)

**المشروع:** `/Users/ahmad/Herd/carag-v2`
**الفرع الحالي:** `feature/phase-2-goal-3`
**تاريخ التقرير:** 2026-11-09
**النطاق:** بحث فقط — **لم يتم تعديل أي كود**.

---

## الخلاصة التنفيذية

اكتُشفت **3 ميزات مفقودة فعلياً** من الشجرة الحالية، وجميعها **قابلة للاستعادة بالكامل**
من فروع أو stash موجودة في المستودع (لا يوجد عمل مفقود كلياً):

| # | الميزة | الحالة | مكان النسخة |
|---|--------|--------|-------------|
| 1 | **لوحة اقتراحات الذكاء الاصطناعي في كرت العمل (الواجهة)** | ❌ مفقود — الـ backend حيّ | `feature/ai-work-order-suggester-frontend-v2` @ `d8d375c2` |
| 2 | **عارض سجل الأخطاء (`/system/error-log`)** | ❌ مفقود بالكامل | `stash@{3}^3` (untracked parent) |
| 3 | **سلسلة Seeders المستقلة + مولّد شعارات المركبات** | ❌ مفقود | `fix/initial-system-seeder-complete-bootstrap` |

**النقطة الأهم في الميزة #1:** الـ backend كامل سليم (خدمة، متحكم، مسارات، ترجمات، middleware) —
المفقود هو **طبقة Vue فقط**. الـ API ينتج اقتراحات فعلياً اليوم، لكن **لا يوجد أي عميل يستدعيه**.

---

## الميزة #1 — لوحة اقتراحات الذكاء الاصطناعي في كرت العمل

### ما الذي تبقّى (الـ backend — حيّ ويعمل)

| الملف | الحالة | ملاحظة |
|-------|--------|--------|
| `app/Services/AI/WorkOrderSuggestionService.php` | ✅ موجود | 27KB — orchestrator كامل مع حماية من الهلوسة |
| `app/Services/AI/AiProvider.php` | ✅ موجود | العقد (interface) |
| `app/Services/AI/ProviderRegistry.php` | ✅ موجود | مزود OpenAI / Anthropic / Mock |
| `app/Services/AI/Providers/OpenAiProvider.php` | ✅ موجود | |
| `app/Services/AI/Providers/AnthropicProvider.php` | ✅ موجود | |
| `app/Services/AI/Providers/MockProvider.php` | ✅ موجود | |
| `app/Services/AI/MockSuggester.php` | ✅ موجود | اقتراحات محلية بدون مفتاح API |
| `app/Services/AI/CompletionRequest.php` / `CompletionResponse.php` | ✅ موجود | |
| `app/Services/AI/README.md` | ✅ موجود | |
| `app/Http/Controllers/Api/WorkOrderSuggestionController.php` | ✅ موجود | |
| `app/Http/Requests/WorkOrder/WorkOrderSuggestionRequest.php` | ✅ موجود | |
| `app/Http/Middleware/TrackAiUsage.php` | ✅ موجود | تتبّع الاستهلاك |
| `app/Exceptions/AI/WorkOrderAiInvalidResponseException.php` | ✅ موجود | |
| `app/Models/Developer/AiMemory.php` | ✅ موجود | |
| `app/Http/Controllers/Api/AiDemoController.php` | ✅ موجود | |
| `routes/api.php:53` | ✅ موجود | `POST /api/v1/work-orders/{workOrder}/suggestions` |
| `resources/js/i18n/lang/ar.json` (قسم `work_orders.suggestions`) | ✅ موجود | ترجمات جاهزة |

**الأمان مُطبَّق بشكل صحيح:** الـ controller يستدعي `$this->authorize('update', $workOrder)`،
ويفشل مغلقاً إذا انقص `tenant_id` أو `current_center_id`، والخدمة تُعيد جلب كل `item_id`
من قاعدة البيانات مُقيّدة بالمستأجر والمركز (لا يُوثق بـ `item_id` القادم من الذكاء الاصطناعي).

### ما الذي مفقود (طبقة الواجهة)

| الملف | الحالة |
|-------|--------|
| `resources/js/Components/WorkOrders/WorkOrderSuggestionsPanel.vue` | ❌ **مفقود** (415 سطر) |
| `resources/js/Composables/useWorkOrderSuggestions.js` | ❌ **مفقود** (191 سطر) |
| `resources/js/Composables/__tests__/useWorkOrderSuggestions.test.js` | ❌ **مفقود** (235 سطر، 5 اختبارات vitest) |
| `docs/features/ai-service-suggester/design.md` | ❌ **مفقود** (وثيقة العقد §1–§9) |
| `.mavis/plans/ai-service-suggester.yaml` | ❌ مفقود |
| `.harness/reviews/track-b-ai-foundation.md` | ❌ مفقود |

**التحقق من عدم وجود أي عميل:**
```bash
grep -rn "WorkOrderSuggestionsPanel\|useWorkOrderSuggestions" resources/js/ routes/ app/
# → لا نتيجة (مؤكدة)
```

**الدليل من الفرع:**
```bash
git show feature/ai-work-order-suggester-frontend-v2:resources/js/Components/WorkOrders/WorkOrderSuggestionsPanel.vue
git show feature/ai-work-order-suggester-frontend-v2:resources/js/Composables/useWorkOrderSuggestions.js
```

**ملاحظة مهمة:**-branch `feature/ai-work-order-suggester-backend` (merge-base `6d1cd07f`, 2026-07-09)
جده الأقدم بكثير من الفرع الحالي — **استرجعFrontend من `-frontend-v2` فقط**، ولا تحاولدمج الفرع
الخلفي كاملاً لأنه سيعيد نسخ آلاف الملفات القديمة.

### الأوامر للاستعادة
```bash
# 1) ملفات الواجهة (3 ملفات) — بدون دمج الفرع كاملاً
git checkout feature/ai-work-order-suggester-frontend-v2 -- \
  resources/js/Components/WorkOrders/WorkOrderSuggestionsPanel.vue \
  resources/js/Composables/useWorkOrderSuggestions.js \
  resources/js/Composables/__tests__/useWorkOrderSuggestions.test.js

# 2) وثائق العقد
git checkout feature/ai-work-order-suggester-frontend-v2 -- docs/features/ai-service-suggester/design.md
git checkout feature/ai-work-order-suggester-frontend-v2 -- .mavis/plans/ai-service-suggester.yaml

# 3)，挂靠 اللوحة في صفحة كرت العمل — تحقق أولاً من flush point:
grep -n "WorkOrderCustomerCard\|WorkOrderFinancialSummary" resources/js/Pages/WorkOrders/Show.vue
# ثم أضف: <WorkOrderSuggestionsPanel :work-order="workOrder" /> داخل <script setup>
# مع: import WorkOrderSuggestionsPanel from '@/Components/WorkOrders/WorkOrderSuggestionsPanel.vue'
```

> ⚠️ **تنبيه:** الملف `WorkOrderFormModal.vue` تغيّر كثيراً منذ July (1224 سطر اختفاء في الـ diff).
> لا تنسخ الملف من الفرع القديم — خُذ **ملف اللوحة فقط**، واربطها يدوياً بالصفحة الحالية.

---

## الميزة #2 — عارض سجل الأخطاء (Error Log Viewer)

### الدليل
الـ stash entries `stash@{3}` و `stash@{4}` تحتوي على الـ routing + logging config،
والملفات نفسها محفوظة في **الـ untracked parent** (`stash@{3}^3`):

```
app/Http/Controllers/System/ErrorLogController.php        | 119 ++
resources/js/Pages/System/ErrorLog/Index.vue               | 152 ++
tests/Feature/System/ErrorLogControllerTest.php           |  88 ++
```

بالإضافة إلى:
- `config/logging.php` → قناة `daily-detailed` (RotatingFileHandler + JSON، احتفاظ 30 يوم)
- `routes/web.php` → `/system/error-log` (admin-only)

### التحقق من الفقد
```bash
git log --all --oneline --diff-filter=AD -- "*ErrorLog*"     # → لا نتيجة: غير موجود في أي commit
find . -iname "*ErrorLog*" -not -path "*/node_modules/*"      # → لا نتيجة
grep -rn "error-log" routes/ resources/js/Pages/System/      # → لا نتيجة
```

### الأوامر للاستعادة
```bash
# أ) استرجع الملفات من untracked parent للـ stash
git checkout 'stash@{3}^3' -- \
  app/Http/Controllers/System/ErrorLogController.php \
  resources/js/Pages/System/ErrorLog/Index.vue \
  tests/Feature/System/ErrorLogControllerTest.php

# ب) طبّق تعديلات config/logging.php + routes/web.php من stash@{4}
git checkout 'stash@{4}' -- config/logging.php routes/web.php
# ⚠️ راجع routes/web.php بعد التطبيق — قد يتعارض مع المسارات الحالية
```

> ⚠️ `routes/web.php` تغيّر كثيراً منذ July. **لا تستبدل الملف كاملاً** —
> خذ سطر `use` + كتلة `Route` فقط، وادمجها يدوياً في النسخة الحالية.

---

## الميزة #3 — سلسلة Seeders المستقلة + شعارات المركبات

### الوضع الحالي (مؤكد)
`database/seeders/InitialSystemSeeder.php` في الفرع الحالي **لا يستدعي أي seeder تابع**:
```php
//amianumber 40 - 90
$this->command->info('✅ Initial System Setup Complete!');
```
المتطلبات (PermissionsSeeder, RolesSeeder, MetadataSeeder, ServiceSeeder, …)
**لا تعمل تلقائياً** عند bootstrap على قاعدة بيانات جديدة.

### ما هو مفقود
| الملف | الحالة |
|-------|--------|
| `database/seeders/VehicleMakeLogoSeeder.php` | ❌ **مفقود** (74 سطر) — يولّد SVG placeholder لكل `VehicleMake` |
| استدعاء `$this->call([...])` داخل `InitialSystemSeeder` | ❌ **مفقود** |
| `database/seeders/PerformanceBaselineSeeder.php` | ❌ مفقود (209 سطر) |
| `database/seeders/TenantScenariosSeeder.php` | ❌ مفقود (315 سطر) |
| `database/seeders/TestingEnvironmentSeeder.php` | ❌ مفقود (1,572 سطر) |

### الأوامر للاستعادة
```bash
git checkout fix/initial-system-seeder-complete-bootstrap -- \
  database/seeders/VehicleMakeLogoSeeder.php \
  database/seeders/InitialSystemSeeder.php
```
> `InitialSystemSeeder.php` كان يتغير — راجع الـ diff قبل الاستبدال:
> `git diff feature/phase-2-goal-3 fix/initial-system-seeder-complete-bootstrap -- database/seeders/InitialSystemSeeder.php`
> ملاحظة: فرع `fix/...` يضيف `is_system_admin` handling — **هذا موجود في نسختك الحالية**،
> فلا تفقده.

---

## 🚨 خطرroses: 118 ملف untracked + 276 ملف معدّل

هذا **ليس** فقداً بعد، لكنه على شفا الفقد. الملفات التالية موجودة على القرص
و**لم تُدفع إلى git أبداً**:

```
?? resources/fonts/IBMPlexSansArabic-{Regular,Bold}.ttf      ← خط الدستور المعتمد
?? resources/js/Components/Common/{AppToggle,DataTable,FormField,FormSection,RadioGroup,AppPagination}.vue
?? resources/js/Components/Common/SelectableCard.vue
?? app/Support/ArabicPdfShaper.php
?? database/migrations/2026_11_09_010000_add_single_use_employee_invitations_to_users.php
?? docs/audit/**   (30+ ملف تدقيق)
```

**المكوّنات في `Components/Common/*` هي المعتمدة في الدستور** — لو ضاعت مع `git clean -fd`
تتكسر الشاشة وتصبح مخالفة constitutional.

### الإجراء العاجل
```bash
git add -A && git commit -m "chore: commit untracked fonts, Common components and audit docs"
```
> ⚠️ **لا تستخدم `git add -A` بشكل أعمى** — عمّلتُ سابقاً على أن عملية موازية
> تستخدم `git add -A` فتبتلع عملك في commit باسم مختلف. **خصّص الـ paths**:
> ```bash
> git add resources/fonts resources/js/Components/Common app/Support/ArabicPdfShaper.php \
>         database/migrations/2026_11_09_010000_add_single_use_employee_invitations_to_users.php docs/audit
> git commit -m "chore(assets): track constitution fonts, Common components and audit docs"
> ```

---

## ✅ مزاعم تم التحقق منها على أنها **ليست** فقداً

رفضت عدة بنود بعد التحقق المباشر:

| البند | النتيجة |
|-------|---------|
| `System/AdminUsers/{id}/Edit.vue` | ❌ ليس مفقوداً — الـ controller ي.render `Index` لـ edit أصلاً (تعليق صريح في `AdminUsersController.php:81,138`) |
| `Pages/Errors/403.vue` / `404.vue` | ✅ موجود — الحذف في `374e5f23` كان لنسخة مكرّرة، Errors تُعرض عبر Blade |
| `Pages/Dashboard.vue` (v1) | ✅ استُبدل عمداً بـ DashboardV2 |
| `MyReceipts` + `Subscription` | ✅ استُبدلت عمداً بـ Billing hub في `1632d3fd` |
| Installments | ✅ استُبدلت عمداً بـ BNPL/Dunning في `d36748e5` |
| `feature/phase-2-goal-4` | ✅ ليس فقداً — فرع أقدم من الحالي (merge-base أقدم) |
| 13 `stash` entries (PaymentSettings, Dunning, Credits, Subscriptions…) | ✅ **الملفات موجودة بالفعل في الشجرة** — الـ stash قديم وم superseded |
| `PrintHeader.vue` + `TemplateReportTable.vue` | ✅ حذف صحيح — لا مراجع live (الإشارة الوحيدة تعليق في `PaySlipModal.vue`) |
| `Analytics/Reports.vue` | ✅ حذف صحيح — لا مرجع من PHP ولا Vue |

---

## 🔎 النمط الذي تسبّب في الفقد

**عملية موازية تستخدم `git add -A`** فتبتلع عملاً كبيراً في commit باسم غير ذي صلة:

| Commit | الرسالة | المحتوى الفعلي |
|--------|---------|----------------|
| `8233fc59` | `chore(cleanup): remove verified unused legacy components...` | احتوى أيضاً على حذف `PrintHeader.vue` (105 سطر) و `TemplateReportTable.vue` (237 سطر) و `Analytics/Reports.vue` (146 سطر) — 1,164 سطر محذوف لا تصفه الرسالة |

**هذا النمط هو السبب الجذري.** الفقد الذي علامة عليه:
- `git status` يظهر الملف نظيفاً
- `git log -- <file>` يُظهر commit ليس لك
- المحتوى موجود لكن في مكان لا تتوقعه

---

## 📋 خطة استعادة مقترحة (بترتيب الأولوية)

1. **🚨 احمِ الـ 118 ملف untracked أولاً** (commit بـ paths محددة) — الأعلى استعجالاً،_loss点是 هنا.
2. **لوحة AI** — استرجع 3 ملفات من `-frontend-v2` + اربطها بـ `Show.vue` يدوياً.
3. **عارض سجل الأخطاء** — استرجع من `stash@{3}^3` + ادمج الـ route يدوياً.
4. **سلسلة Seeders** — استرجع `VehicleMakeLogoSeeder` + استدعاءات `$this->call`.
5. **وثائق العقد** (`design.md` §9) — الـ backend يحتاجها كمرجع عند أي تعديل.

**قبل أي استعادة، خذ نسخة احتياطية:**
```bash
git branch backup/pre-restore-2026-11-09
```

---

## ملاحظة ختامية

لم يُعدَّل أي كود أثناء هذا التقرير. جميع الأوامر المذكورة أعلاه **مقترحة ولم تُنفَّذ**،
nach تثبيت نسخة احتياطية والتأكد من عدم وجود عملية موازية نشطة على المستودع.
