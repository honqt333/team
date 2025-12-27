---
description: Project standards and development guidelines for Carag - MANDATORY for all agents
---

# 🔒 قواعد المشروع الملزمة (MANDATORY PROJECT RULES)

> ⚠️ **تحذير**: هذه القواعد إلزامية. أي انحراف عنها يُعتبر خطأ.

---

## 📋 قبل أي عمل - اقرأ هذا أولاً

1. **اقرأ** `/Partner Reference.md` لفهم رؤية المنتج
2. **اقرأ** هذا الملف كاملاً
3. **لا تبدأ** بدون فهم السياق

---

## 🏗️ البنية المعمارية (Architecture)

### Stack التقني الإلزامي

| التقنية | الإصدار | الاستخدام |
|---------|---------|----------|
| Laravel | 12.x | Backend |
| Vue 3 | Composition API | Frontend |
| Inertia.js | 2.x | SPA Bridge |
| Tailwind CSS | 4.x | Styling |
| MySQL | 8.x | Database |

### ❌ ممنوع استخدام:
- React
- jQuery
- Bootstrap
- Options API في Vue
- Controllers بدون Policies

---

## 📁 هيكل الملفات (File Structure)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── App/           # App controllers (authenticated)
│   │   └── Api/           # API controllers
│   ├── Requests/          # Form Requests
│   └── Middleware/
├── Models/                # Eloquent Models
├── Policies/              # Authorization
└── Support/               # Helpers (TenancyContext)

resources/js/
├── Components/            # Reusable components
│   ├── Common/            # Shared components
│   └── [Module]/          # Module-specific
├── Pages/                 # Inertia pages
│   └── [Module]/
│       ├── Index.vue
│       └── Show.vue
├── Layouts/
│   └── AppLayout.vue
├── Composables/           # Vue composables
└── i18n/lang/
    ├── ar.json
    └── en.json
```

---

## 🎨 قواعد التصميم (Design Rules)

### الألوان المعتمدة

```css
/* Primary - للأزرار الرئيسية */
indigo-600 / indigo-500

/* Accent - لكل Module */
Work Orders: indigo
Quotes: amber/orange
Customers: emerald
Vehicles: blue
Services: purple

/* Neutrals */
gray-50 to gray-900

/* Status Colors */
success: emerald-500
warning: amber-500
danger: red-500
info: blue-500
```

### المكونات الأساسية

```vue
<!-- الكروت -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">

<!-- الأزرار الرئيسية -->
<button class="px-5 py-2.5 bg-gradient-to-r from-[color]-600 to-[color]-600 text-white rounded-xl font-medium shadow-lg">

<!-- الحقول -->
<input class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900">

<!-- التابات الحديثة (نموذج) -->
<button class="relative flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200">
```

### ✅ إلزامي في كل صفحة:
- دعم Dark Mode
- دعم RTL
- Responsive (Mobile-first)
- الأرقام بالإنجليزية (toEnglish)

---

## 🗂️ قواعد قاعدة البيانات

### كل جدول يجب أن يحتوي على:

```php
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
$table->timestamps();
$table->softDeletes(); // للجداول الحساسة
```

### Naming Conventions:

```
Tables: snake_case, plural (work_orders, quote_lines)
Models: PascalCase, singular (WorkOrder, QuoteLine)
Pivot: singular_singular (department_quote)
Foreign Keys: singular_id (customer_id)
```

---

## 🔐 الأمان والصلاحيات

### كل Controller يجب أن:

```php
// 1. يستخدم Policy
$this->authorize('viewAny', Model::class);

// 2. يستخدم Form Request للتحقق
public function store(ModelStoreRequest $request)

// 3. لا يثق بأي input بدون تحقق
$validated = $request->validated();
```

### كل Model يجب أن:

```php
// 1. يحدد $fillable
protected $fillable = ['field1', 'field2'];

// 2. يستخدم $casts للتواريخ
protected $casts = [
    'date_field' => 'date:Y-m-d',
];
```

---

## 🌍 الترجمة (i18n)

### قواعد ملزمة:

1. **لا نصوص مباشرة** في الـ Vue - استخدم `$t('key')`
2. **كل مفتاح** يجب أن يكون في `ar.json` و `en.json`
3. **التسمية**: `module.section.key`

```json
// مثال
{
  "work_orders": {
    "title": "أوامر العمل",
    "status": {
      "open": "مفتوح",
      "closed": "مغلق"
    }
  }
}
```

---

## 🔄 دورة العمل (Workflow)

### عند إنشاء ميزة جديدة:

```
1. Migration → إنشاء الجداول
2. Model → مع العلاقات
3. Policy → الصلاحيات
4. Form Request → التحقق
5. Controller → المنطق
6. Routes → التوجيه
7. Vue Page → الواجهة
8. Translations → ar.json + en.json
9. Build → npm run build
10. Test → التجربة
```

### عند تعديل ميزة:

```
1. فهم الكود الحالي
2. تحديد نطاق التغيير
3. التعديل بأقل تأثير
4. الحفاظ على التوافقية
5. Build + Test
```

---

## ⚠️ الأخطاء الشائعة - تجنبها

| الخطأ | الصحيح |
|-------|--------|
| `new Date()` للتواريخ | استخدم date:Y-m-d في casts |
| نص عربي مباشر | `$t('key')` |
| `v-if` بدون `key` | أضف `:key` |
| API بدون Policy | أضف `$this->authorize()` |
| Tailwind classes طويلة | استخرجها لـ component |

---

## 📝 قواعد الكود

### Vue Components

```vue
<script setup>
// 1. Imports
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

// 2. Props
const props = defineProps({...});

// 3. Composables
const { t } = useI18n();

// 4. State
const loading = ref(false);

// 5. Computed
const filteredItems = computed(() => ...);

// 6. Functions
function handleSubmit() {...}
</script>

<template>
  <!-- 7. Template -->
</template>
```

### Laravel Controllers

```php
class ModelController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Model::class);
        
        $items = Model::query()
            ->when($request->search, fn($q, $s) => $q->where(...))
            ->paginate(20)
            ->withQueryString();
            
        return Inertia::render('Module/Index', [
            'items' => $items,
            'filters' => $request->only(['search', 'status']),
        ]);
    }
}
```

---

## 🚀 قبل كل Commit

- [ ] `npm run build` بدون أخطاء
- [ ] الترجمات موجودة
- [ ] Dark Mode يعمل
- [ ] Mobile responsive
- [ ] لا console.log أو dd()

---

## 📞 للمساعدة

إذا واجهت موقفاً غير موثق:
1. ارجع للملفات الموجودة كمرجع
2. اتبع النمط السائد
3. اسأل قبل أن تبتكر

---

> 🎯 **القاعدة الذهبية**: الاتساق أهم من الكمال
