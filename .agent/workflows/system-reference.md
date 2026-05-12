# 📖 المرجع الموحد لهيكل ونماذج النظام (System Design Reference)

هذا الملف هو المرجع الأساسي لهيئة الصفحات ونماذج الإضافة والتعديل في نظام "خدمة برو". يجب الالتزام بهذه الأنماط لضمان تجربة مستخدم موحدة واحترافية.

---

## 1. هيكل الصفحات (Page Layouts)

### أ. صفحة المركز (Hub Page)
تُستخدم كمدخل رئيسي لكل وحدة (Module) وتوفر وصولاً سريعاً لأهم العمليات.
- **المكونات:**
  - هيدر (Header) يحتوي على العنوان والوصف العام.
  - كروت سريعة (Quick Cards) كبيرة الحجم مع أيقونات ملونة.
  - عدادات إحصائية (Badges) توضح عدد العمليات المفتوحة.
- **قاعدة:** لا تحتوي على جداول بيانات، بل روابط للقوائم المتخصصة.

### ب. صفحة الفهرس عالي الكثافة (High-Density Index)
تُستخدم لعرض قوائم البيانات الكبيرة (كروت العمل، الفواتير).
- **المكونات:**
  - **Header:** شريط بحث ذكي + زر إضافة بارز.
  - **Tabs:** تبويبات علوية (Dashboard, Open, Closed) للتنقل السريع.
  - **Status Tabs:** تبويبات داخلية ملونة للفلترة حسب الحالة (Draft, Overdue, Pending).
  - **Table:** جدول مدمج الحواف مع Infinite Scroll (تحميل تلقائي عند التمرير).
- **قاعدة:** يجب أن تدعم الـ Dark Mode والتحجيم التلقائي للشاشات.

### ج. صفحة العرض التفصيلي (Show Page)
تُستخدم لعرض تفاصيل (كرت عمل، عميل، فاتورة).
- **المكونات:**
  - **Top Bar:** شريط علوي يحتوي على أهم الأرقام (الإجمالي، المتبقي، الحالة).
  - **Side Actions:** قائمة جانبية أو علوية للإجراءات (طباعة، إلغاء، تحويل).
  - **Tabs System:** نظام تبويبات سفلي لفصل البيانات (الخدمات، قطع الغيار، سجل الأنشطة).

---

## 2. نماذج الإضافة والتعديل (Add/Edit Form Modals)

تعتمد جميع عمليات الإدخال على **النماذج المنبثقة (Modals)** لضمان بقاء المستخدم في سياق العمل.

### المعايير البصرية (Visual Standards):
- **الزوايا:** استخدام زوايا مستديرة كبيرة `rounded-2xl` أو `rounded-3xl`.
- **الخلفية:** استخدام `bg-gray-50` للحقول لتسهيل القراءة.
- **التنظيم:** تقسيم الحقول إلى مجموعات منطقية (Sections) مع عناوين فرعية.

### هيكل النموذج الموحد:
```vue
<template>
  <Modal :show="show" @close="close">
    <div class="p-6">
      <!-- Title -->
      <h3 class="text-xl font-bold mb-6">عنوان النموذج</h3>
      
      <!-- Form Content -->
      <div class="space-y-6">
        <!-- Section 1 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold mb-1">اسم الحقل</label>
            <input class="w-full px-4 py-2.5 rounded-xl border-gray-300 bg-gray-50">
          </div>
        </div>
        
        <!-- Divider -->
        <hr class="border-gray-100">
        
        <!-- Section 2 -->
        ...
      </div>
      
      <!-- Footer Actions -->
      <div class="mt-8 flex justify-end gap-3">
        <button @click="close" class="px-6 py-2.5 text-gray-500">إلغاء</button>
        <button class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg">حفظ</button>
      </div>
    </div>
  </Modal>
</template>
```

---

## 3. هوية الألوان للوحدات (Module Branding)

يتم تخصيص لون لكل وحدة ليسهل على المستخدم معرفة موقعه:

| الوحدة | اللون الأساسي | الرمز البرمجي |
| :--- | :--- | :--- |
| **كروت العمل (Work Orders)** | Indigo | `indigo-600` |
| **الفواتير والمالية (Invoices)** | Emerald / Green | `emerald-600` |
| **العملاء (Customers)** | Cyan | `cyan-600` |
| **المركبات (Vehicles)** | Blue | `blue-600` |
| **التقييمات (Quotes)** | Amber / Orange | `amber-500` |
| **الإعدادات (Settings)** | Gray | `gray-700` |

---

## 4. قواعد الترجمة والبيانات (Data & i18n Rules)

- **الأرقام:** تُعرض دائماً بالأرقام الإنجليزية (1, 2, 3) باستخدام وظيفة `toEnglish`.
- **العملة:** تُعرض دائماً مع `SAR` أو `ر.س` باستخدام `formatCurrency`.
- **التواريخ:** تُعرض بالتنسيق المحلي باستخدام `formatDate`.
- **النصوص:** لا يُسمح بكتابة أي نص عربي أو إنجليزي داخل ملف الـ Vue؛ يجب استخدام `$t('key')`.

---

## 5. مصفوفة الحالات (Status Matrix)

يجب استخدام هذه الألوان للحالات في جميع الجداول والكروت:
- **مسودة (Draft):** Gray
- **مفتوح (Open):** Blue
- **قيد التنفيذ (In Progress):** Indigo
- **مكتمل (Done/Completed):** Emerald
- **ملغي (Cancelled):** Red
- **متأخر (Overdue):** Rose/Red
