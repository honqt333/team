# دليل استخدام مكونات الواجهة ونظام التصميم (Design System & Component Usage Guide)
## خدمة برو / Khidmh Pro

> **المرجع الرسمي:** هذا الدليل يوضح المعايير القياسية لاستخدام مكونات الواجهة وحقول النماذج الموحدة المعتمدة على **Design Tokens** في `resources/js/Design/tokens.css` والمربوطة بـ `tailwind.config.js`.

---

## 1. فلسفة الألوان والتصميم (Design Tokens Architecture)

يمنع استخدام الألوان الثابتة (Hardcoded Colors مثل `indigo-600`, `violet-500`, `purple-700`) في مكونات التحكم والإدخال. يتم استدعاء الألوان حصراً عبر أسماء الكلاسات المعتمدة في Tailwind المشتقة من المتغيرات:

| الغرض / الوظيفة | كلاس Tailwind المعتمد | المتغير في `tokens.css` |
| :--- | :--- | :--- |
| **إجراءات أساسية (Action Primary)** | `bg-action-primary` / `text-action-primary` | `--color-action-primary` |
| **تحويم الإجراء الأساسي** | `hover:bg-action-primary-hover` | `--color-action-primary-hover` |
| **خلفية التحديد / الاختيار الناعمة** | `bg-selection-bg` / `text-selection-text` | `--color-selection-bg` |
| **حلقات التركيز الموحدة (Focus Ring)** | `focus:ring-focus-ring` | `--color-focus-ring` |
| **إطار الحدود الموحد** | `border-border` / `border-border-strong` | `--color-border` / `--color-border-strong` |
| **خلفيات البطاقات والأسطح** | `bg-surface` / `bg-surface-muted` | `--color-surface` / `--color-surface-muted` |
| **النصوص الأساسية والثانوية** | `text-text-primary` / `text-text-secondary` | `--color-text-primary` / `--color-text-secondary` |
| **مفتاح التبديل غير المفعل** | `bg-toggle-off-bg` | `--color-toggle-off-bg` |
| **مقبض مفتاح التبديل** | `bg-toggle-thumb` | `--color-toggle-thumb` |

---

## 2. دليل المكونات القياسية ونماذج الاستخدام

### 2.1 غلاف الحقول الموحد (`FormField.vue`)
يجمع كل متطلبات الحقل (العنوان، التلميح، الخطأ، نجمة الإلزام) دون تكرار.
```vue
<script setup>
import FormField from '@/Components/Common/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
</script>

<template>
    <FormField
        :label="$t('customers.name')"
        :hint="$t('customers.name_hint')"
        :error="form.errors.name"
        required
    >
        <TextInput v-model="form.name" class="w-full" />
    </FormField>
</template>
```

---

### 2.2 قسم النماذج الموحد (`FormSection.vue`)
لتجميع الحقول ذات الصلة في بطاقة قياسية مع عنوان ووصف وأزرار إجراءات فرعية.
```vue
<script setup>
import FormSection from '@/Components/Common/FormSection.vue';
import FormField from '@/Components/Common/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
</script>

<template>
    <FormSection
        :title="$t('settings.company_info')"
        :description="$t('settings.company_info_desc')"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <FormField :label="$t('settings.name_ar')" required>
                <TextInput v-model="form.name_ar" class="w-full" />
            </FormField>
            <FormField :label="$t('settings.name_en')">
                <TextInput v-model="form.name_en" class="w-full" />
            </FormField>
        </div>
    </FormSection>
</template>
```

---

### 2.3 مفتاح التبديل الموحد (`AppToggle.vue`)
مفتاح التبديل القياسي المعتمد لكافة حالات On/Off:
```vue
<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
</script>

<template>
    <!-- مفرد داخل سطر جدول أو بطاقة -->
    <AppToggle v-model="form.is_active" />

    <!-- مع عنوان ووصف ولون مخصص -->
    <AppToggle
        v-model="form.notifications"
        :label="$t('common.notifications')"
        :description="$t('common.notifications_hint')"
        color="success"
    />
</template>
```

---

### 2.4 البطاقة القابلة للتحديد (`SelectableCard.vue`)
بديل عصري لاختيارات Checkbox و Radio في القوائم وخطط الأسعار والخدمات:
```vue
<script setup>
import SelectableCard from '@/Components/Common/SelectableCard.vue';
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <SelectableCard
            v-for="plan in plans"
            :key="plan.id"
            v-model="form.plan_id"
            :value="plan.id"
            type="radio"
            :title="plan.name"
            :description="plan.price"
        />
    </div>
</template>
```

---

### 2.5 مجموعة خيارات الراديو (`RadioGroup.vue`)
مجموعة أزرار راديو متوافقة مع إمكانية الوصول و Design Tokens:
```vue
<script setup>
import RadioGroup from '@/Components/Common/RadioGroup.vue';

const paymentOptions = [
    { value: 'cash', label: 'نقداً', description: 'دفع مباشر في المركز' },
    { value: 'card', label: 'بطاقة مدى / ائتمان', description: 'عبر أجهزة نقاط البيع' },
];
</script>

<template>
    <RadioGroup
        v-model="form.payment_method"
        :options="paymentOptions"
        direction="horizontal"
    />
</template>
```

---

### 2.6 تأخير البحث والإدخال الموحد (`useDebounce.ts`)
Composable موحد ومحمي من تسريب الذاكرة (Memory Leaks) يلغي المؤقتات تلقائياً عند مغادرة الصفحة (`onUnmounted`):
```vue
<script setup>
import { useDebounce } from '@/Composables/useDebounce';

const debouncedSearch = useDebounce((query) => {
    fetchResults(query);
}, 300);
</script>

<template>
    <input @input="debouncedSearch($event.target.value)" />
</template>
```

---

### 2.7 محرك الجداول وقوائم البيانات الموحد (`DataTable.vue` + `useServerTable.ts`)
إدارة متكاملة للجداول والخوادم تشمل الترتيب (Sorting)، التصفية (Filtering)، البحث الفوري مع debounce، وحالات التحميل والتفريغ والتنقل بين الصفحات:
```vue
<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { useServerTable } from '@/Composables/useServerTable';

const props = defineProps({
    parts: Object,
    filters: Object,
});

const { filters, sortColumn, sortDirection, isLoading, toggleSort } = useServerTable({
    routeUrl: () => route('app.inventory.parts.index'),
    initialFilters: { search: props.filters?.search || '' },
    initialSort: { column: 'sku', direction: 'asc' },
});

const columns = [
    { key: 'sku', label: 'الكود / الباركود', sortable: true },
    { key: 'name', label: 'اسم الصنف', sortable: true },
    { key: 'price', label: 'السعر', align: 'end' },
    { key: 'actions', label: 'الإجراءات', align: 'center', width: 'w-24' },
];
</script>

<template>
    <DataTable
        :columns="columns"
        :data="parts"
        :loading="isLoading"
        :sort-column="sortColumn"
        :sort-direction="sortDirection"
        @sort="toggleSort"
    >
        <!-- تخصيص خلية محددة -->
        <template #cell-actions="{ item }">
            <button @click="edit(item)">تعديل</button>
        </template>
    </DataTable>
</template>
```

---

## 3. قواعد الحظر الصارمة (Strict Banned Patterns)

1. ❌ **ممنوع** استخدام قيم لونية عشوائية مثل `focus:border-indigo-500` أو `focus:ring-violet-600`.
   - ✅ **البديل:** `focus:border-action-primary` و `focus:ring-focus-ring`.
2. ❌ **ممنوع** استخدام وسوم HTML الخام للـ Checkbox ومحاكاة التبديل يدويًا.
   - ✅ **البديل:** استدعاء `AppToggle`.
3. ❌ **ممنوع** كتابة هيكل الحقول المكرر (Label + Asterisk + Input + Error) يدوياً في الشاشات الجديدة.
   - ✅ **البديل:** استخدام `FormField`.
4. ❌ **ممنوع** كتابة مؤقتات تأخير يدوية (`setTimeout` / `clearTimeout`) أو نسخ دوال debounce محلية في النماذج.
   - ✅ **البديل:** استخدام `useDebounce` الموحد من `@/Composables/useDebounce`.
5. ❌ **ممنوع** تكرار شيفرة أزرار التنقل بين الصفحات وتنسيقات الألوان الصلبة (مثل `bg-blue-600`).
   - ✅ **البديل:** استخدام `AppPagination` أو محرك `DataTable` المعتمد.
