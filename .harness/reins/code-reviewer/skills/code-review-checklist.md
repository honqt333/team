# Skill: Code Review Checklist - Carag V2

Checklist مفصّل لمراجعة أي PR في Carag V2. اقرأ قبل ما تبدأ review.

## كيف تبدأ مراجعة

```bash
cd /Users/ahmad/Herd/carag-v2
git fetch origin
git diff main...HEAD --stat           # قائمة الملفات
git diff main...HEAD -- path/to/file  # تفاصيل ملف معيّن
```

## Section 1: Multi-tenant (الأهم)

هذا المشروع **multi-tenant** — أي خرق = كارثة.

- [ ] **كل** Eloquent query على tenant-scoped model فيه `tenant_id` filter
- [ ] **كل** query فيه `center_id` filter (لو الـ model مفصول حسب المركز)
- [ ] **كل** route محمي بـ `auth` و `2fa` middleware
- [ ] **كل** `$workOrder->load(...)` يأخذ من tenant الصحيح
- [ ] لا يوجد `Model::all()` بدون scope
- [ ] لا يوجد `WorkOrder::find($id)` بدون تحقق tenant

```php
// ❌ كارثي
$wo = WorkOrder::find($id);

// ✅ صح
$wo = WorkOrder::where('tenant_id', auth()->user()->tenant_id)
    ->where('id', $id)
    ->firstOrFail();
```

## Section 2: Authorization

- [ ] كل controller method (غير `index`/`show` للـ public) عنده `$this->authorize(...)` أو Policy
- [ ] Policies مسجّلة في `AuthServiceProvider`
- [ ] لا authorization gaps على routes جديدة
- [ ] `destroy`/`update` على Model واحد مو collection بدون check

## Section 3: Validation

- [ ] **كل** input من المستخدم يمر بـ `FormRequest`
- [ ] `authorize()` يرجع `true` فقط لما الـ FormRequest ما يحتاج policy منفصل
- [ ] Custom rules موصوفة بشكل واضح
- [ ] No `$request->all()` في الموديلات
- [ ] File upload محدّد بـ `mimes:` و `max:`

## Section 4: Database

- [ ] Foreign keys مع indexes
- [ ] Composite indexes للأعمدة اللي تُفلتر معاً
- [ ] لا `LIKE '%...'` على indexed column (يقتل الـ index)
- [ ] لا `select *` في hot path
- [ ] `chunk()` أو `cursor()` للـ exports الضخمة
- [ ] Migration فيها `down()` صحيح
- [ ] Migration آمنة على production (لا `drop column` بدون data migration)

## Section 5: Performance (N+1)

- [ ] Eager loading للـ relationships المستخدمة في الـ view
- [ ] لا loop على collection مع query داخله
- [ ] لا `count()` في template بدون withCount
- [ ] Pagination على كل listing (`->paginate(20)`)
- [ ] لا `dd()` أو `dump()` في الكود الملتزم

```php
// ❌ N+1
$workOrders = WorkOrder::all();
return view('...', compact('workOrders'));
// في الـ view: {{ $wo->customer->name }}  // query per row

// ✅ Eager load
$workOrders = WorkOrder::with('customer')->get();
```

## Section 6: Logging & Audit

- [ ] العمليات المهمة تستخدم `$model->logActivity('action', 'description')`
- [ ] لا sensitive data في الـ logs (passwords, tokens)
- [ ] Log levels مناسبة (`info` للـ business, `error` للـ failures)

## Section 7: Print Engine (Invoices)

- [ ] يستخدم `Inertia::render('Invoices/Print/TemplateName', [...])`
- [ ] لا طباعة inline في الـ controller
- [ ] ZATCA fields محفوظة (hash, counter, signature)
- [ ] QR code موجود (TLV format)

## Section 8: Frontend (Vue/Inertia)

### Inertia patterns
- [ ] يستخدم `<Link>` بدل `<router-link>`
- [ ] يستخدم `useForm()` بدل axios للـ mutations
- [ ] يستخدم `route()` helper من Ziggy
- [ ] لا `$fetch` على routes خاصة بـ Inertia

### RTL
- [ ] يستخدم logical properties: `ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`
- [ ] لا `ml-*` / `mr-*` / `pl-*` / `pr-*` / `text-left` / `text-right`
- [ ] `<html dir="rtl" lang="ar">` أو على container

### Vue idioms
- [ ] Composition API مع `<script setup>`
- [ ] `defineProps` و `defineEmits` واضحة
- [ ] لا `ref()` و `reactive()` مختلطين بشكل غامض
- [ ] Composable للاستخراج لو المنطق مستخدم في أكثر من component

## Section 9: Security (OWASP)

- [ ] CSRF: Inertia token يمرّ تلقائياً — تأكد من وجود meta tag
- [ ] XSS: لا `v-html` على user input بدون sanitization
- [ ] SQL injection: لا raw queries بدون bindings
- [ ] Mass assignment: `$fillable` أو `$guarded` مضبوطة
- [ ] File upload: `mimes:` validation + storage path آمن
- [ ] Rate limiting على endpoints حساسة (login, password reset)
- [ ] 2FA: ما يعطّلها أحد

## Section 10: Testing

- [ ] كل feature جديد عنده Feature test
- [ ] Test يعزل DB (RefreshDatabase / DatabaseTransactions)
- [ ] Test يختبر auth (لا anonymous access)
- [ ] Test يختبر tenant isolation
- [ ] لا hardcoded IDs (use factories)

## Section 11: Code Style

- [ ] Pint يمر (`./vendor/bin/pint`)
- [ ] أسماء methods/dirs تتبع convention المشروع
- [ ] لا magic numbers
- [ ] Comments تشرح **لماذا** مو **ماذا**
- [ ] PHPDoc للـ public methods المعقدة
- [ ] لا dead code (تعليقات عليها كود، functions ما تنستخدم)

## كيف تكتب الـ verdict

```
✅ APPROVE      — لا CRITICAL/MAJOR issues، الكود جاهز للـ merge
⚠️ REQUEST_CHANGES — فيه MAJOR issues تحتاج fix
❌ BLOCK        — فيه CRITICAL issues (security, tenant leak, data loss)
```

## Templates للـ Critical Issue

```markdown
### [SECURITY] User can access another tenant's data
**File:** `app/Http/Controllers/WorkOrderController.php:42`
**Code:**
\`\`\`php
$wo = WorkOrder::find($id);  // ❌ no tenant scope
\`\`\`
**Why critical:** أي user يقدر يشوف WorkOrder من tenant ثاني بـ ID.
**Fix:**
\`\`\`php
$wo = WorkOrder::where('tenant_id', auth()->user()->tenant_id)
    ->where('id', $id)
    ->firstOrFail();
\`\`\`
```

---

*Built for the full-stack team rollout — 2026-06-06*
