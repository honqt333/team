# سجل المكونات الرسمي — خدمة برو (Khidmah Pro Component Registry)

> **الحالة:** `Active`
> **المرجع الأعلى:** [AGENTS.md](../AGENTS.md)
> **الوثائق المرتبطة:** [COMPONENTS.md](COMPONENTS.md) · [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) · [DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md) · [DUPLICATE_COMPONENTS_REGISTER.md](../audit_reports/ui_compliance/DUPLICATE_COMPONENTS_REGISTER.md)
>
> **قاعدة إلزامية:** لا يجوز إنشاء أي مكون جديد قبل البحث في هذا السجل والتأكد من عدم وجود مكون رسمي أو متخصص يؤدي الوظيفة نفسها أو يمكن توسيعه عبر `props` / `slots`.

---

## تصنيف المكونات في المنظومة

1. **المكونات الرسمية المعتمدة (Official Core Components):** المكونات الأساسية المُلزمة لجميع الصفحات والميزات الجديدة.
2. **المكونات المتخصصة بالنطاق (Specialized Domain Components):** مكونات متخصصة بمجالات محددة (اللوحات السعودية، التواقيع، التواريخ، الألوان، الطباعة) ويُمنع بناء بدائل لها.
3. **المكونات الموازية / المرشحة للتوحيد (Parallel Components — غير معتمدة للتوسع):** مكونات قائمة في `DashboardV2/common/` مسجلة في `DUPLICATE_COMPONENTS_REGISTER.md`؛ تبقى عاملة حالياً ويُمنع استيرادها في أي صفحات جديدة.
4. **المكونات المتوقفة (Deprecated Components):** تبقى للتوافق العكسي فقط، ويُمنع استيرادها في أي كود جديد (`UI-006`).

---

## 1. المكونات الرسمية المعتمدة (Official Core Components)

### 1.1 `AppButton`
- **المسار الفعلي:** `resources/js/Components/App/AppButton.vue`
- **الوظيفة:** الزر القياسي الموحد لجميع الإجراءات والروابط التفاعلية.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`, `CustomerPortalLayout`, `EmployeePortalLayout`, `AuthLayout`.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `variant` (`primary`, `secondary`, `ghost`, `danger`, `success`, `warning`, `action`, `action-lg`, `action-xl`, `action-sm`, `action-xs`, `action-bold`, `action-shadow-lg`, `action-shadow-md`), `size` (`xs`, `sm`, `md`, `lg`), `type` (`button`, `submit`, `reset`), `as` (`button`, `a`, `link`), `href`, `loading`, `disabled`, `block`, `iconLeftPath`, `iconRightPath`.
  - **Events:** أحداث النقر القياسية عبر العنصر الجذري.
  - **Slots:** `default`, `iconLeft`, `iconRight`.
- **مثال استخدام صحيح:**
  ```vue
  <AppButton variant="primary" :loading="form.processing" @click="submit">
      {{ $t('common.save') }}
  </AppButton>
  ```
- **الحالات التي يجب استخدامه فيها:** أزرار الحفظ، الإلغاء، الحذف، الإجراءات داخل الجداول والنوافذ، وروابط Inertia (`as="link"`).
- **الحالات التي لا يناسبها:** مفاتيح التبديل الثنائية (`AppToggle`) أو أزرار التبويبات (`AppTabs`).
- **البدائل الممنوعة أو Deprecated:** `PrimaryButton.vue`, `SecondaryButton.vue`, `DangerButton.vue`, وأزرار `<button>` الخام في النماذج.
- **المكونات المرتبطة:** `AppPrintButton`, `BaseModal`.

### 1.2 `AppInput`
- **المسار الفعلي:** `resources/js/Components/App/AppInput.vue`
- **الوظيفة:** حقل الإدخال النصي والرقمي الموحد مع دعم الحالات الدلالية وإمكانية الوصول.
- **اللوحات التي يُستخدم فيها:** جميع اللوحات الخمس.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `id`, `modelValue`, `label`, `hint`, `error`, `invalid`, `type`, `placeholder`, `required`, `disabled`, `readonly`, `autofocus`, `autocomplete`, `min`, `max`, `step`, `prefix`, `suffix`.
  - **Events:** `update:modelValue`, `blur`, `focus`, `enter`.
  - **Slots:** `prefix`, `suffix`.
- **مثال استخدام صحيح:**
  ```vue
  <AppInput
      v-model="form.name"
      :label="$t('common.name')"
      :error="form.errors.name"
      required
  />
  ```
- **الحالات التي يجب استخدامه فيها:** جميع حقول النصوص، الأرقام، البريد، الهاتف القياسي، وكلمات المرور.
- **الحالات التي لا يناسبها:** النصوص الطويلة متعددة الأسطر (`AppTextarea`)، القوائم المنسدلة (`AppSelect`/`SearchableSelect`)، ولوحات المركبات (`SaudiPlateInput`).
- **البدائل الممنوعة أو Deprecated:** `TextInput.vue`, `InputLabel.vue`, `InputError.vue`, وحقول `<input>` الخام.
- **المكونات المرتبطة:** `FormField`, `FormSection`.

### 1.3 `AppTextarea`
- **المسار الفعلي:** `resources/js/Components/App/AppTextarea.vue`
- **الوظيفة:** حقل إدخال النصوص الطويلة والملاحظات.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`, `CustomerPortalLayout`, `EmployeePortalLayout`.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `id`, `modelValue`, `label`, `hint`, `error`, `placeholder`, `required`, `disabled`, `readonly`, `rows`, `maxlength`.
  - **Events:** `update:modelValue`, `blur`, `focus`.
  - **Slots:** لا توجد.
- **مثال استخدام صحيح:**
  ```vue
  <AppTextarea v-model="form.notes" :label="$t('common.notes')" :rows="3" :error="form.errors.notes" />
  ```
- **الحالات التي يجب استخدامه فيها:** الملاحظات، الشروط، الأوصاف، والتعليقات الطويلة.
- **الحالات التي لا يناسبها:** المدخلات ذات السطر الواحد (`AppInput`) أو المحتوى المنسق الغني.
- **البدائل الممنوعة أو Deprecated:** `<textarea>` الخام.
- **المكونات المرتبطة:** `FormField`, `AppInput`.

### 1.4 `AppSelect`
- **المسار الفعلي:** `resources/js/Components/App/AppSelect.vue`
- **الوظيفة:** قائمة اختيار بسيطة للخيارات الثابتة المحدودة.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`, `EmployeePortalLayout`.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `id`, `modelValue`, `label`, `hint`, `error`, `required`, `disabled`, `placeholder`.
  - **Events:** `update:modelValue`, `change`, `blur`, `focus`.
  - **Slots:** `default` (`<option>`).
- **مثال استخدام صحيح:**
  ```vue
  <AppSelect v-model="form.center_type" :label="$t('branches.type')">
      <option value="main">{{ $t('branches.types.main') }}</option>
      <option value="branch">{{ $t('branches.types.branch') }}</option>
  </AppSelect>
  ```
- **الحالات التي يجب استخدامه فيها:** القوائم الثابتة المحدودة فقط (2 إلى 5 خيارات ثابتة لا تحتاج بحثاً).
- **الحالات التي لا يناسبها:** القوائم الديناميكية أو التي يتجاوز عدد خياراتها 5 عناصر (عملاء، مركبات، قطع، موظفون، فروع كثيرة).
- **البدائل الممنوعة أو Deprecated:** `<select>` الخام في النماذج الجديدة، أو استخدام `AppSelect` لقوائم قابلة للبحث.
- **المكونات المرتبطة:** `SearchableSelect`, `FormField`.

### 1.5 `SearchableSelect`
- **المسار الفعلي:** `resources/js/Components/SearchableSelect.vue`
- **الوظيفة:** قائمة منسدلة قابلة للبحث محلياً أو عبر الخادم (`asyncSearch`).
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`, `CustomerPortalLayout`, `EmployeePortalLayout`.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `modelValue`, `options`, `label`, `placeholder`, `error`, `optionLabel`, `optionValue`, `searchPlaceholder`, `required`, `compact`, `asyncSearch`, `disabled`.
  - **Events:** `update:modelValue`, `change`, `search`.
  - **Slots:** `option`, `prefix`.
- **مثال استخدام صحيح:**
  ```vue
  <SearchableSelect
      v-model="form.customer_id"
      :options="customers"
      option-label="name"
      option-value="id"
      :label="$t('work_orders.customer')"
      :error="form.errors.customer_id"
  />
  ```
- **الحالات التي يجب استخدامه فيها:** **إلزامي** لجميع القوائم الديناميكية أو القابلة للبحث.
- **الحالات التي لا يناسبها:** الاختيارات الثابتة من خيارين أو ثلاثة (`AppSelect` أو `RadioGroup`).
- **البدائل الممنوعة أو Deprecated:** `<select>` الخام أو بناء dropdown بحث مخصص.
- **المكونات المرتبطة:** `AppSelect`, `FormField`.

### 1.6 `AppCheckbox`
- **المسار الفعلي:** `resources/js/Components/App/AppCheckbox.vue`
- **الوظيفة:** مربع اختيار قياسي لقيمة منطقية (`Boolean`) أو مصفوفة قيم (`Array`).
- **اللوحات التي يُستخدم فيها:** جميع اللوحات.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `id`, `modelValue`, `label`, `hint`, `error`, `disabled`, `required`, `value`.
  - **Events:** `update:modelValue`, `change`.
  - **Slots:** `default`.
- **مثال استخدام صحيح:**
  ```vue
  <AppCheckbox v-model="form.is_taxable" :label="$t('finance.taxable')" />
  ```
- **الحالات التي يجب استخدامه فيها:** تحديد الصلاحيات، الموافقات، والاختيارات المتعددة.
- **الحالات التي لا يناسبها:** مفاتيح التشغيل/الإيقاف الفورية (`AppToggle`) أو الاختيار الأحادي (`RadioGroup`).
- **البدائل الممنوعة أو Deprecated:** `Checkbox.vue` و`<input type="checkbox">` الخام.
- **المكونات المرتبطة:** `AppToggle`, `FormField`.

### 1.7 `AppToggle`
- **المسار الفعلي:** `resources/js/Components/Common/AppToggle.vue`
- **الوظيفة:** مفتاح تبديل منطقي (On/Off) مع وصف اختياري وموضع تسمية مرن.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `modelValue`, `checked`, `value`, `trueValue`, `falseValue`, `disabled`, `size`, `color`, `label`, `description`, `labelPosition`, `name`, `id`.
  - **Events:** `update:modelValue`, `update:checked`, `change`.
  - **Slots:** `default`, `description`.
- **مثال استخدام صحيح:**
  ```vue
  <AppToggle
      v-model="form.is_active"
      :label="$t('common.active')"
      :description="$t('settings.active_hint')"
  />
  ```
- **الحالات التي يجب استخدامه فيها:** تفعيل/تعطيل الحالات والإعدادات ومفاتيح الخيارات.
- **الحالات التي لا يناسبها:** الموافقة على الشروط أو تحديد صفوف الجدول (`AppCheckbox`).
- **البدائل الممنوعة أو Deprecated:** `Toggle.vue` أو محاكاة التبديل بـ`<input type="checkbox">`.
- **المكونات المرتبطة:** `AppCheckbox`.

### 1.8 `AppFileUpload`
- **المسار الفعلي:** `resources/js/Components/Common/AppFileUpload.vue`
- **الوظيفة:** رفع الملفات (`dropzone`) أو الصور/الشعارات (`image`) مع معاينة وتحقق من الحجم والنوع.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`, `EmployeePortalLayout`.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `variant` (`dropzone` / `image`), `accept`, `maxSizeMb`, `multiple`, `disabled`, `loading`, `label`, `hint`, `error`, `existingUrl`, `fallbackUrl`, `defaultBadge`, `uploadButtonText`, `resetButtonText`, `compactPreview`, `size`.
  - **Events:** `select`, `invalid`, `remove-existing`.
  - **Slots:** `default`.
- **مثال استخدام صحيح:**
  ```vue
  <AppFileUpload
      variant="image"
      :existing-url="tenant.logo_url"
      :label="$t('company_profile.logo')"
      @select="onLogoSelect"
      @remove-existing="removeLogo"
  />
  ```
- **الحالات التي يجب استخدامه فيها:** كل نموذج يتضمن رفع مستندات، إيصالات، أو صور/شعارات.
- **الحالات التي لا يناسبها:** رفع ملفات الاستيراد المخصص إن كان يستخدم `ImportFileField.vue`.
- **البدائل الممنوعة أو Deprecated:** `<input type="file">` الخام.
- **المكونات المرتبطة:** `FormField`, `ImportFileField`.

### 1.9 `BaseModal`
- **المسار الفعلي:** `resources/js/Components/BaseModal.vue`
- **الوظيفة:** الغلاف الموحد لجميع نوافذ الإدخال والتعديل والعرض المنبثقة.
- **اللوحات التي يُستخدم فيها:** جميع اللوحات.
- **Props وEvents وSlots الأساسية:**
  - **Props:** `show`, `closeable`, `title`, `size`, `zIndexClass`, `scrollEntire` (افتراضي `true`), `overflowVisible`.
  - **Events:** `close`.
  - **Slots:** `title`, `default`, `footer`.
- **مثال استخدام صحيح:**
  ```vue
  <BaseModal :show="showModal" :title="$t('common.add')" size="lg" @close="closeModal">
      <div class="space-y-4">...</div>
      <template #footer>
          <AppButton variant="secondary" @click="closeModal">{{ $t('common.cancel') }}</AppButton>
          <AppButton variant="primary" @click="save">{{ $t('common.save') }}</AppButton>
      </template>
  </BaseModal>
  ```
- **الحالات التي يجب استخدامه فيها:** كافة نوافذ النماذج والتفاصيل المنبثقة (مع إبقاء `scrollEntire` مفعّلاً وتجنب حقن `overflow-y-auto` داخلي).
- **الحالات التي لا يناسبها:** نوافذ التأكيد الحرجة البسيطة (`ConfirmModal` + `useConfirm`).
- **البدائل الممنوعة أو Deprecated:** `Modal.vue`, `DialogModal.vue`.
- **المكونات المرتبطة:** `ConfirmModal`, `AppButton`, `useFormScrollToError`.

### 1.10 `ConfirmModal` (+ `useConfirm`)
- **المسار الفعلي:** `resources/js/Components/ConfirmModal.vue` و`resources/js/Composables/useConfirm.js`
- **الوظيفة:** نافذة التأكيد الموحدة للإجراءات الحرجة (حذف، إلغاء، ترحيل، استرجاع).
- **اللوحات التي يُستخدم فيها:** مُثبّت عالمياً في `AppLayout`, `SystemLayout`, `EmployeePortalLayout`, `CustomerPortalLayout`.
- **الواجهة البرمجية:** استدعاء `const { confirm } = useConfirm()` ثم `await confirm({ title, message, confirmText, cancelText, type })`.
- **مثال استخدام صحيح:**
  ```js
  const ok = await confirm({
      title: t('common.confirm_delete_title'),
      message: t('common.confirm_delete_message'),
      confirmText: t('common.delete'),
      cancelText: t('common.cancel'),
      type: 'danger',
  });
  if (!ok) return;
  ```
- **الحالات التي يجب استخدامه فيها:** أي تأكيد قبل إجراء مدمر أو لا يمكن التراجع عنه.
- **الحالات التي لا يناسبها:** النماذج التي تتطلب حقول إدخال من المستخدم (`BaseModal`).
- **البدائل الممنوعة أو Deprecated:** `window.confirm()`, `window.alert()`, `DialogModal.vue`, أو بناء نافذة تأكيد مخصصة لكل صفحة.
- **المكونات المرتبطة:** `BaseModal`, `Toast`.

### 1.11 `FormField` و`FormSection`
- **المسار الفعلي:** `resources/js/Components/Common/FormField.vue` و`resources/js/Components/Common/FormSection.vue`
- **الوظيفة:** تغليف الحقل الفردي بالتسمية والتلميح ورسالة الخطأ (`FormField`)، وتجميع الحقول في بطاقة قسم موحدة (`FormSection`).
- **اللوحات التي يُستخدم فيها:** جميع اللوحات.
- **Props وSlots الأساسية:**
  - `FormField`: **Props:** `id`, `label`, `hint`, `error`, `required`, `disabled`. **Slots:** `default`, `label`, `label-action`, `hint`.
  - `FormSection`: **Props:** `title`, `description`. **Slots:** `default`, `title`, `description`, `icon`, `actions`.
- **الحالات التي يجب استخدامه فيها:** عند تغليف مكونات لا تحمل `label`/`error` داخلياً (مثل `CustomDatePicker` أو `SaudiPlateInput`) ولتنظيم النماذج الطويلة.
- **الحالات التي لا يناسبها:** عندما يوفّر المكون (`AppInput`, `AppTextarea`) التسمية والخطأ داخلياً فلا حاجة لتكرارهما.
- **البدائل الممنوعة أو Deprecated:** `InputLabel.vue`, `InputError.vue`.
- **المكونات المرتبطة:** `AppInput`, `AppSelect`, `SearchableSelect`.

### 1.12 `SelectableCard` و`RadioGroup`
- **المسار الفعلي:** `resources/js/Components/Common/SelectableCard.vue` و`resources/js/Components/Common/RadioGroup.vue`
- **الوظيفة:** بطاقات اختيار مرئية ومجموعات راديو متوافقة مع إمكانية الوصول.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`, `CustomerPortalLayout`.
- **Props وEvents وSlots الأساسية:**
  - `SelectableCard`: **Props:** `modelValue`, `name`, `value`, `title`, `description`, `disabled`, `type` (`radio`/`checkbox`). **Emits:** `update:modelValue`, `change`. **Slots:** `default`, `title`, `description`, `icon`.
  - `RadioGroup`: **Props:** `modelValue`, `options`, `name`, `direction`, `disabled`. **Emits:** `update:modelValue`, `change`.
- **الحالات التي يجب استخدامه فيها:** اختيار الباقات، طرق الدفع، أو خيارات الإعدادات الواضحة بصرياً.
- **الحالات التي لا يناسبها:** القوائم الطويلة (`SearchableSelect`).
- **البدائل الممنوعة:** بطاقات `<div @click>` بدون أدوار وصول أو أزرار راديو غير مقيّدة بالتوكنز.
- **المكونات المرتبطة:** `AppCheckbox`, `AppToggle`.

### 1.13 `DataTable` و`AppPagination`
- **المسار الفعلي:** `resources/js/Components/Common/DataTable.vue` و`resources/js/Components/Common/AppPagination.vue`
- **الوظيفة:** عرض جداول البيانات الموحدة مع الفرز، حالات التحميل والفراغ، وترقيم صفحات Laravel.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`, `CustomerPortalLayout`, `EmployeePortalLayout`.
- **Props وEvents وSlots الأساسية:**
  - `DataTable`: **Props:** `structured`, `columns`, `data`, `items`, `loading`, `rowKey`, `sortColumn`, `sortDirection`, `striped`, `hoverable`, `emptyTitle`, `emptyDescription`, `emptyActionText`, `emptyActionHref`, `showPagination`. **Emits:** `sort`, `rowClick`, `emptyAction`. **Slots:** `default`, `header`, `cell`, `empty`, `footer`, `pagination`.
  - `AppPagination`: **Props:** `links`, `from`, `to`, `total`, `preserveScroll`, `preserveState`, `showSummary`.
- **الحالات التي يجب استخدامه فيها:** جميع جداول القوائم والبيانات في النظام (`UI-012`).
- **الحالات التي لا يناسبها:** قوالب الطباعة المخصصة أو جداول تحرير الصفوف المتعددة المعقدة المشمولة بالاستثناء `NC-003`.
- **البدائل الممنوعة:** بناء `<table>` خام لعرض قوائم قياسية.
- **المكونات المرتبطة:** `AppTableSkeleton`, `AppEmptyState`, `useServerTable`.

### 1.14 `AppTabs`, `AppViewModeToggle`, `AppPrintButton`
- **المسار الفعلي:** `resources/js/Components/Common/AppTabs.vue`, `AppViewModeToggle.vue`, `AppPrintButton.vue`
- **الوظيفة:** تبويبات التنقل (مع مزامنة URL اختيارية)، تبديل وضع العرض (جدول/بطاقات)، وزر الطباعة الموحد.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`.
- **Props وEvents الأساسية:**
  - `AppTabs`: **Props:** `modelValue`, `tabs`, `variant`, `syncUrl`, `urlParam`, `size`. **Emits:** `update:modelValue`, `change`.
  - `AppViewModeToggle`: **Props:** `modelValue`, `storageKey`, `size`. **Emits:** `update:modelValue`, `change`.
  - `AppPrintButton`: **Props:** `label`, `tooltip`, `loading`, `disabled`, `variant`, `size`, `href`. **Emits:** `click`.
- **الحالات التي يجب استخدامه فيها:** صفحات التفاصيل ذات التبويبات، صفحات القوائم ذات العرض المزدوج، وإجراءات الطباعة.
- **البدائل الممنوعة:** `DashboardTabs.vue` في `DashboardV2/common/` (موازي غير معتمد للتوسع).
- **المكونات المرتبطة:** `PageHeader`, `PrintEngine`.

### 1.15 `AppEmptyState`, `AppTableSkeleton`, `AppCardSkeleton`
- **المسار الفعلي:** `resources/js/Components/Common/AppEmptyState.vue`, `AppTableSkeleton.vue`, `AppCardSkeleton.vue`
- **الوظيفة:** تمثيل حالات الفراغ والتحميل الهيكلي بشكل موحد (`UI-020`).
- **اللوحات التي يُستخدم فيها:** جميع اللوحات.
- **Props وEvents وSlots الأساسية:**
  - `AppEmptyState`: **Props:** `title`, `description`, `actionText`, `actionHref`, `secondaryActionText`, `secondaryActionHref`. **Emits:** `action`, `secondaryAction`. **Slots:** `icon`, `title`, `description`, `actions`, `extra`.
  - `AppTableSkeleton`: **Props:** `rows`, `cols`, `showHeader`, `showPagination`.
  - `AppCardSkeleton`: **Props:** `count`.
- **الحالات التي يجب استخدامه فيها:** عند غياب البيانات أو أثناء جلب البيانات من الخادم.
- **المكونات المرتبطة:** `DataTable`, `PageHeader`.

### 1.16 `PageHeader` و`StatusBadge`
- **المسار الفعلي:** `resources/js/Components/PageHeader.vue` و`resources/js/Components/Common/StatusBadge.vue`
- **الوظيفة:** رأس الصفحة القياسي (`UI-013`) وشارة الحالة الموحدة.
- **اللوحات التي يُستخدم فيها:** `AppLayout`, `SystemLayout`, `EmployeePortalLayout`, `CustomerPortalLayout`.
- **Props وSlots الأساسية:**
  - `PageHeader`: **Props:** `title`, `subtitle`, `totalCount`, `countLabel`, `gradientFrom`, `gradientTo`, `glowFrom`, `glowTo`, `badgeBg`, `badgeText`, `badgeBorder`, `badgeDot`. **Slots:** `back`, `icon`, `identity`, `extra`, `actions`, `filters`, `footer`. *(ملاحظة: `PageHeader` يطبع `totalCount` تلقائياً بجانب `countLabel`، فلا تضع `{count}` داخل `countLabel`.)*
  - `StatusBadge`: **Props:** `label`, `showDot`, `dotClass`, `dotColor`.
- **البدائل الممنوعة:** `DashboardV2/common/PageHeader.vue` و`DashboardV2/common/StatusBadge.vue` خارج `DashboardV2`.
- **المكونات المرتبطة:** `AppIdentityPills`, `AppViewModeToggle`, `AppButton`.

### 1.17 `Toast` (+ `useToast` / `useFlashToasts`)
- **المسار الفعلي:** `resources/js/Components/Toast.vue`, `resources/js/Composables/useToast.js`, `resources/js/Composables/useFlashToasts.js`
- **الوظيفة:** نظام الإشعارات الفورية الوحيد في كافة اللوحات الخمس (`UI-014`).
- **الحالات التي يجب استخدامه فيها:** عرض رسائل النجاح والأخطاء والتحذيرات من Flash أو الطلبات غير المتزامنة.
- **البدائل الممنوعة:** `window.alert()`, أو أي مكون تنبيهات عائم موازٍ.

---

## 2. المكونات المتخصصة بالنطاق (Specialized Domain Components)

| المكون | المسار الفعلي | الوظيفة | Props / Events / Slots | متى يُستخدم / متى يُمنع غيره |
| :--- | :--- | :--- | :--- | :--- |
| **`SaudiPlateDisplay`** | `Components/Vehicles/SaudiPlateDisplay.vue` | عرض اللوحة السعودية بصرياً | **Props:** `plateNumber`, `size` | إلزامي لعرض أرقام اللوحات في البطاقات والجداول والطباعة. |
| **`SaudiPlateInput`** | `Components/Vehicles/SaudiPlateInput.vue` | إدخال اللوحة السعودية بالعربية والإنجليزية | **Props:** `modelValue`, `error` · **Emits:** `update:modelValue`, `blur` | إلزامي لإدخال لوحات المركبات؛ يُمنع استخدام `AppInput` عادي بدلاً منه. |
| **`SignaturePad`** | `Components/Common/SignaturePad.vue` | لوحة التوقيع الإلكتروني | **Props:** `modelValue`, `label` · **Emits:** `update:modelValue`, `clear` | إلزامي لالتقاط التوقيع في الاستلام وعروض الأسعار وبوابة العميل. |
| **`CustomDatePicker`** | `Components/CustomDatePicker.vue` | منتقي التاريخ الموحد | **Props:** `modelValue`, `placeholder`, `alignRight`, `disabled`, `required`, `maxDate`, `minDate` · **Emits:** `update:modelValue`, `change` | إلزامي لاختيار التواريخ؛ يُمنع `<input type="date">` الخام غير المتسق. |
| **`ColorField`** | `Components/ColorField.vue` | اختيار وتسمية اللون | **Props:** `modelValue`, `label`, `error` · **Emits:** `update:modelValue` | لمنتقي الألوان في الإعدادات والمواصفات. |
| **`PrintEngine`** | `Components/Print/PrintEngine.vue` | محرك الطباعة الموحد | **Props:** `documentType`, `data`, `centerData`, `documentSettings`, `visualSettings`, `previewMode` · **Slots:** `default` | إلزامي لمستندات الطباعة مع `usePrint.js`. |

---

## 3. المكونات الموازية والمسجلة كتكرار (`DUP-001` .. `DUP-003`)

هذه المكونات موجودة في `resources/js/Components/DashboardV2/common/` لأغراض لوحة القيادة الحالية فقط. **يُمنع استيرادها في أي صفحة أو ميزة جديدة**، والبديل الرسمي لكل منها موضح أدناه:

| المكون الموازي (غير معتمد للجديد) | المسار الفعلي | البديل الرسمي المعتمد | المرجع |
| :--- | :--- | :--- | :--- |
| `DashboardV2/common/PageHeader.vue` | `resources/js/Components/DashboardV2/common/PageHeader.vue` | `Components/PageHeader.vue` | `DUP-001` |
| `DashboardV2/common/StatusBadge.vue` | `resources/js/Components/DashboardV2/common/StatusBadge.vue` | `Components/Common/StatusBadge.vue` | `DUP-002` |
| `DashboardV2/common/DashboardTabs.vue` | `resources/js/Components/DashboardV2/common/DashboardTabs.vue` | `Components/Common/AppTabs.vue` | `DUP-003` |

---

## 4. المكونات المتوقفة (`Deprecated Components — UI-006`)

يُمنع استيراد أي من الملفات التالية في أي كود جديد (بموجب `AGENTS.md` و`.eslintrc.cjs` و`NC-001`):

- `resources/js/Components/PrimaryButton.vue` ← استبدل بـ `AppButton.vue` (`variant="primary"`)
- `resources/js/Components/SecondaryButton.vue` ← استبدل بـ `AppButton.vue` (`variant="secondary"`)
- `resources/js/Components/DangerButton.vue` ← استبدل بـ `AppButton.vue` (`variant="danger"`)
- `resources/js/Components/TextInput.vue` ← استبدل بـ `AppInput.vue`
- `resources/js/Components/InputLabel.vue` ← استبدل بـ `FormField.vue` أو خاصية `label` في `AppInput`
- `resources/js/Components/InputError.vue` ← استبدل بـ `FormField.vue` أو خاصية `error` في `AppInput`
- `resources/js/Components/Checkbox.vue` ← استبدل بـ `AppCheckbox.vue`
- `resources/js/Components/Toggle.vue` ← استبدل بـ `AppToggle.vue`
- `resources/js/Components/Modal.vue` ← استبدل بـ `BaseModal.vue` أو `ConfirmModal.vue`
- `resources/js/Components/DialogModal.vue` ← استبدل بـ `BaseModal.vue` أو `ConfirmModal.vue`
