# 🎨 دليل المكونات المعتمدة في الواجهة (Reusable UI Components)
# Khidmah Pro / Carag V2 Component Registry

> **تنبيه إلزامي لجميع الوكلاء والمطورين:**
> يمنع منعاً باتاً إنشاء قوائم منسدلة عادية `<select>` أو مكونات إدخال موازية عند وجود المكون المعتمد في هذا الدليل.
> راجع هذا الملف واستخدم المكونات المحددة أدناه لضمان توحيد تجربة المستخدم والتوافق البصري.

---

## فهرس المكونات السريع

| المكون | الموقع البرمجي | الوظيفة الأساسية |
| :--- | :--- | :--- |
| **SearchableSelect** | `@/Components/SearchableSelect.vue` | قائمة منسدلة ذكية قابلة للبحث والفلترة الفورية |
| **SaudiPlateDisplay** | `@/Components/Vehicles/SaudiPlateDisplay.vue` | عرض لوحة المركبة السعودية بالتنسيق الرسمي |
| **SaudiPlateInput** | `@/Components/Vehicles/SaudiPlateInput.vue` | حقل إدخال واختيار حروف وأرقام اللوحة السعودية |
| **SignaturePad** | `@/Components/Common/SignaturePad.vue` | لوحة توقيع رقمية باللمس/الماوس للعملاء والفنيين |
| **CustomDatePicker** | `@/Components/CustomDatePicker.vue` | منتقي تواريخ مخصص يدعم الوضعين الداكن والفاتح |
| **ColorField** | `@/Components/ColorField.vue` | منتقي ألوان مخصص للمركبات والوسوم مع درجات جاهزة |
| **ConfirmModal** | `@/Components/ConfirmModal.vue` | نافذة تأكيد الإجراءات الحرجة (حذف، إلغاء، استرجاع) |
| **BaseModal** | `@/Components/BaseModal.vue` | النافذة المنبثقة الأساسية لكافة النماذج (`docs/MODALS.md`) |
| **PrintEngine** | `@/Components/Print/PrintEngine.vue` | محرك الطباعة الموحد (A4 وحراري 80mm) عبر `usePrint` |
| **DataTable** | `@/Components/Common/DataTable.vue` | محرك عرض الجداول والبيانات الموحد مع الفرز والحالات الفارغة |
| **AppPagination** | `@/Components/Common/AppPagination.vue` | شريط التنقل بين الصفحات الموحد بالاعتماد على Design Tokens |
| **AppToggle** | `@/Components/Common/AppToggle.vue` | مفتاح التبديل الموحد (On/Off) لجميع الحالات المنطقية |
| **AppViewModeToggle** | `@/Components/Common/AppViewModeToggle.vue` | مبدل العرض الشبكي/السطري الموحد مع حفظ تلقائي |
| **AppAttachmentManager** | `@/Components/Common/AppAttachmentManager.vue` | مدير المرفقات الموحد للسحب والإفلات والعرض والحذف |
| **useServerTable** | `@/Composables/useServerTable.ts` | Composable موحد لإدارة فرز وبحث وجدول بيانات السيرفر |

---

## 1. القائمة المنسدلة القابلة للبحث (SearchableSelect / Searchable Dropdown)

**الموقع:** `resources/js/Components/SearchableSelect.vue`  
**الاستيراد:** `import SearchableSelect from '@/Components/SearchableSelect.vue';`

### متى يُستخدم؟
- **إلزامي في أي اختيار يحتوي على بيانات أو خيارات متعددة** مثل: اختيار العميل، المركبة، القسم، الخدمة، الموظف، المورد، القطعة، الحساب المالي، الجنسية، إلخ.
- يمنع استخدام `<select>` التقليدي للخيارات الديناميكية لما يسببه من ضعف في تجربة المستخدم على الشاشات الكبيرة والموبايل.

### الـ Props المدعومة:
| الخاصية (Prop) | النوع | الافتراضي | الوصف |
| :--- | :--- | :--- | :--- |
| `modelValue` / `v-model` | `String \| Number \| Boolean \| null` | `null` | القيمة المحددة حالياً |
| `options` | `Array` | `[]` | مصفوفة العناصر أو الكائنات |
| `optionLabel` | `String \| Function` | `'name'` | اسم الحقل النصي المعروض أو دالة تنسيق |
| `optionValue` | `String \| Function` | `'id'` | القيمة المعادة عند الاختيار |
| `label` | `String` | `''` | التسمية التوضيحية أعلى الحقل |
| `placeholder` | `String` | `''` | النص البديل عند عدم التحديد |
| `searchPlaceholder` | `String` | `''` | النص داخل مربع البحث الداخلي |
| `error` | `String` | `null` | رسالة الخطأ للتحقق من الصحة |
| `required` | `Boolean` | `false` | هل الحقل إلزامي (يضع علامة *) |
| `compact` | `Boolean` | `false` | نمط مصغر ومضغوط يناسب الجداول والصفوف الضيقة |
| `asyncSearch` | `Boolean` | `false` | تفعيل البحث عبر السيرفر بدلاً من الفلترة المحلية |
| `disabled` | `Boolean` | `false` | تعطيل الحقل ومنع الفتح |

### الـ Slots المتاحة:
- `#prefix`: لوضع أيقونة أو نص بادئ داخل الزر.
- `#option="{ option }"`: لتخصيص قالب ظهور كل عنصر في القائمة (مثلاً إظهار السعر أو الكود بجانب الاسم).

### أمثلة الاستخدام:

#### أ. استخدام قياسي مع مصفوفة كائنات:
```vue
<SearchableSelect
    v-model="form.customer_id"
    :options="customers"
    option-label="name"
    option-value="id"
    :label="$t('customers.select_customer')"
    :placeholder="$t('common.choose')"
    :search-placeholder="$t('common.search')"
    :error="form.errors.customer_id"
    required
/>
```

#### ب. استخدام دالة لتخصيص التسمية المعروضة (`optionLabel`):
```vue
<SearchableSelect
    v-model="form.vehicle_id"
    :options="vehicles"
    :option-label="(v) => `${v.plate_number} - ${v.make?.name || ''} ${v.model?.name || ''}`"
    option-value="id"
    :label="$t('vehicles.vehicle')"
    :error="form.errors.vehicle_id"
/>
```

#### ج. نمط مضغوط داخل صفوف الجداول (`compact`):
```vue
<SearchableSelect
    v-model="item.part_id"
    :options="parts"
    option-label="name"
    option-value="id"
    compact
    :placeholder="$t('inventory.select_part')"
    @change="handlePartSelected(item)"
/>
```

---

## 2. لوحة المركبة السعودية (SaudiPlateDisplay & SaudiPlateInput)

### أ. عرض اللوحة الرسمية (SaudiPlateDisplay)
**الموقع:** `resources/js/Components/Vehicles/SaudiPlateDisplay.vue`  
**الاستيراد:** `import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';`

يُستخدم لعرض لوحة المركبة بتصميمها المعتمد في المرور السعودي (أحرف عربية ولاتينية، أرقام عربية وإنجليزية، وشعار KSA مع النخلة والسيفين):
```vue
<SaudiPlateDisplay :plate-number="vehicle.plate_number" size="md" />
```
- الأحجام المتاحة في `size`: `'sm'`, `'md'`, `'lg'`.
- في حال كان رقم اللوحة غير مكتمل أو بصيغة قديمة، يوفر المكون تلقائياً بديلاً أنيقاً دون كسر الواجهة.

### ب. إدخال وتعديل اللوحة (SaudiPlateInput)
**الموقع:** `resources/js/Components/Vehicles/SaudiPlateInput.vue`  
**الاستيراد:** `import SaudiPlateInput from '@/Components/Vehicles/SaudiPlateInput.vue';`

يُستخدم في نماذج إنشاء وتعديل المركبات لتفكيك اللوحة إلى حروف وأرقام مع الفحص الفوري:
```vue
<SaudiPlateInput
    v-model="form.plate_number"
    :label="$t('vehicles.plate_number')"
    :error="form.errors.plate_number"
    required
/>
```

---

## 3. لوحة التوقيع الرقمي (SignaturePad)

**الموقع:** `resources/js/Components/Common/SignaturePad.vue`  
**الاستيراد:** `import SignaturePad from '@/Components/Common/SignaturePad.vue';`

يُستخدم في كروت العمل وعروض الأسعار وسندات الاستلام لتسجيل توقيع العميل أو المهندس إلكترونياً عبر اللمس أو الماوس:
```vue
<SignaturePad
    ref="signaturePadRef"
    v-model="form.signature"
    :title="$t('work_orders.customer_signature')"
    @signed="onSignatureCaptured"
    @cleared="onSignatureCleared"
/>
```

---

## 4. منتقي التواريخ المخصص (CustomDatePicker)

**الموقع:** `resources/js/Components/CustomDatePicker.vue`  
**الاستيراد:** `import CustomDatePicker from '@/Components/CustomDatePicker.vue';`

يُستخدم لتوحيد حقول إدخال التواريخ وتوافقها مع الوضع الليلي (Dark Mode) واللغتين:
```vue
<CustomDatePicker
    v-model="form.delivery_date"
    :placeholder="$t('work_orders.expected_delivery')"
    :disabled="isReadOnly"
/>
```

---

## 5. منتقي الألوان (ColorField)

**الموقع:** `resources/js/Components/ColorField.vue`  
**الاستيراد:** `import ColorField from '@/Components/ColorField.vue';`

يُستخدم في نماذج المركبات وتصنيفات الخدمات لتمكين المستخدم من اختيار اللون بسهولة مع وجود ألوان السيارات الشائعة كخيارات جاهزة:
```vue
<ColorField
    v-model="form.color"
    :label="$t('vehicles.color')"
    :error="form.errors.color"
/>
```

---

## 6. نافذة تأكيد الإجراءات (ConfirmModal)

**الموقع:** `resources/js/Components/ConfirmModal.vue`  
**الاستيراد:** `import ConfirmModal from '@/Components/ConfirmModal.vue';`

تُستخدم قبل تنفيذ أي إجراء تدميري أو حرج (حذف سجل، إلغاء فاتورة، إغلاق كرت عمل):
```vue
<ConfirmModal
    :show="confirmDeleteOpen"
    type="danger"
    :title="$t('common.confirm_deletion')"
    :message="$t('customers.delete_warning_message')"
    :confirm-text="$t('common.delete')"
    :cancel-text="$t('common.cancel')"
    @confirm="executeDelete"
    @cancel="confirmDeleteOpen = false"
/>
```
- الأنواع المتاحة (`type`): `'danger'`, `'warning'`, `'success'`, `'info'`.

---

## 7. النماذج المنبثقة الأساسية (BaseModal)

**الموقع:** `resources/js/Components/BaseModal.vue`  
يُعتبر هذا المكون هو الأساس لكل النماذج في النظام. لمزيد من التفاصيل حول جميع النماذج المشتقة منه في النظام، راجع:
👉 **[دليل النماذج المنبثقة الكامل (docs/MODALS.md)](MODALS.md)**.

---

## 8. منظومة الطباعة الموحدة (Unified Printing Architecture)

ينقسم نظام الطباعة في **Khidmah Pro** إلى مسارين معتمدين حصراً:

### أ. المسار الأول: طباعة المستندات والمعاملات الرسمية والمالية (Transactional Documents)
**المكون الغلاف:** `resources/js/Components/Print/PrintLayout.vue`  
**المحرك الداخلي:** `resources/js/Components/Print/PrintEngine.vue`

يُستخدم لجميع الوثائق الرسمية والمالية: (الفواتير الضريبية، عروض الأسعار، كروت الصيانة، تقارير فحص المركبة، سندات القبض والدفع، فواتير المشتريات).  
يتكفل `PrintLayout.vue` تلقائياً بـ:
- شريط التحكم العائم (زر الطباعة وزر الرجوع الذكي مع دعم اختصارات لوحة المفاتيح `Esc` و `Ctrl+P`).
- سحب إعدادات المستأجر البصرية (`visualSettings`) وإعدادات الوثيقة (`documentSettings`) وبيانات المركز (`centerData`) تلقائياً.
- التبديل الآلي بين قوالب A4 والقوالب الحرارية (80mm) بناءً على إعدادات المستأجر.

```vue
<template>
    <PrintLayout
        document-type="invoice"
        :data="mappedData"
        :back-url="route('app.invoices.show', invoice.id)"
    />
</template>

<script setup>
import PrintLayout from '@/Components/Print/PrintLayout.vue';
</script>
```

### ب. المسار الثاني: طباعة التقارير الإدارية والجداول المجمعة (Tabular & Operational Reports)
**المكون الغلاف:** `resources/js/Components/Print/StandardReportLayout.vue`

يُستخدم للتقارير المجدولة: (قائمة العملاء، كشوف الحسابات، تقارير الحضور والانصراف، مسير الرواتب المجمع، قائمة الموظفين).  
يوفر:
- رأس تقرير رسمي من 3 أعمدة (شعار وبيانات المنشأة، عنوان التقرير، إحصائيات وعدد السجلات وتاريخ ووقت الطباعة).
- حاوية جدولية متناسقة تدعم التقسيم التلقائي للصفحات بالورق الرسمي.
- تذييل رسمي يتضمن اسم مُعِد التقرير والاعتماد.

```vue
<template>
    <StandardReportLayout
        :title="$t('customers.print.title')"
        :total-count="customers.length"
        :total-count-label="$t('customers.print.total_customers')"
        :on-print="handlePrint"
        :on-close="handleBack"
    >
        <table class="w-full text-xs text-center border-collapse">
            <!-- Table content -->
        </table>
    </StandardReportLayout>
</template>

<script setup>
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';
</script>
```

---

## ملخص القواعد الملزمة للوكلاء بخصوص الواجهة:
1. ❌ **ممنوع** استخدام `<select>` عادية للقوائم الديناميكية أو الخيارات المتعددة؛ **استخدم دائماً `SearchableSelect`**.
2. ❌ **ممنوع** تمثيل لوحات السيارات كنصوص عادية مبعثرة؛ **استخدم دائماً `SaudiPlateDisplay`**.
3. ❌ **ممنوع** إنشاء مكتبات Canvas جديدة للتوقيع؛ **استخدم `SignaturePad`**.
4. ❌ **ممنوع** استخدام مكتبات طباعة خارجية أو نوافذ منبثقة للطباعة؛ **اعتمد على `usePrint` و `PrintEngine.vue`**.
5. ❌ **ممنوع** استخدام وسوم HTML الخام `<table>` أو `<input type="checkbox">` ومفاتيح تبديل يدوية؛ **استخدم دائماً `DataTable.vue` و `AppToggle.vue`**.
6. ❌ **ممنوع** الفرز اليدوي في الذاكرة (Client-side sorting) على الصفحات المقسمة مجزأة؛ **استخدم دائماً `useServerTable.ts` مع Whitelist backend sorting**.
7. ✅ **دائماً** احرص على تمرير الترجمات عبر `$t('...')` في نصوص الـ labels والـ placeholders.
8. ✅ **دائماً** استخدم Design Tokens للألوان والخلفيات وتجنب الألوان الثابتة (`blue-600`, `indigo-500`, إلخ).
