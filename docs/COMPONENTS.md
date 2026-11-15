# سجل وحوكمة مكونات الواجهة — خدمة برو (Khidmah Pro)

> **الحالة:** `Active`
> **المرجع الأعلى:** [AGENTS.md](../AGENTS.md)
> **الوثائق المرتبطة:** [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) · [MODALS.md](MODALS.md) *(Historical / Reference)*

---

## 1. المكونات المعتمدة رسمياً (Official Component Registry)

### أ. عناصر النماذج والأزرار الأساسية

| المكون | المسار | الاستخدام الصحيح | Props / Events / Slots الأساسية |
| :--- | :--- | :--- | :--- |
| **AppButton** | `@/Components/App/AppButton.vue` | جميع الأزرار والإجراءات | **Props:** `variant` (`primary`, `secondary`, `ghost`, `danger`, `success`, `warning`, `action*`), `size` (`xs`, `sm`, `md`, `lg`), `as` (`button`, `a`, `link`), `href`, `loading`, `disabled`, `block`, `iconLeftPath`, `iconRightPath`. **Slots:** `default`, `iconLeft`, `iconRight`. |
| **AppInput** | `@/Components/App/AppInput.vue` | حقول الإدخال النصية والرقمية | **Props:** `modelValue`, `type` (`text`, `email`, `tel`, `number`, `password`, `url`, `search`), `label`, `hint`, `error`, `invalid`, `required`, `disabled`, `readonly`, `prefix`, `suffix`. **Emits:** `update:modelValue`, `blur`, `focus`, `enter`. **Slots:** `prefix`, `suffix`. |
| **AppTextarea** | `@/Components/App/AppTextarea.vue` | حقول النصوص الطويلة | **Props:** `modelValue`, `label`, `hint`, `error`, `rows`, `maxlength`, `required`, `disabled`, `readonly`. **Emits:** `update:modelValue`, `blur`, `focus`. |
| **AppSelect** | `@/Components/App/AppSelect.vue` | القوائم الثابتة البسيطة والمحدودة فقط (مثل 2-5 خيارات ثابتة) | **Props:** `modelValue`, `label`, `hint`, `error`, `placeholder`, `required`, `disabled`. **Emits:** `update:modelValue`, `change`, `blur`, `focus`. **Slot:** `default` (`<option>`). |
| **SearchableSelect** | `@/Components/SearchableSelect.vue` | **إلزامي** لجميع القوائم الديناميكية أو القابلة للبحث (عملاء، مركبات، خدمات، قطع، موظفين...) | **Props:** `modelValue`, `options`, `optionLabel`, `optionValue`, `label`, `placeholder`, `searchPlaceholder`, `error`, `required`, `compact`, `asyncSearch`, `disabled`. **Slots:** `prefix`, `option`. |
| **AppCheckbox** | `@/Components/App/AppCheckbox.vue` | مربعات الاختيار القياسية (Boolean أو Array) | **Props:** `modelValue`, `value`, `label`, `hint`, `error`, `disabled`, `required`. **Emits:** `update:modelValue`, `change`. |
| **AppToggle** | `@/Components/Common/AppToggle.vue` | **إلزامي** لمفاتيح التبديل المنطقية (On/Off) | **Props:** `modelValue`, `value`, `trueValue`, `falseValue`, `size` (`sm`, `md`, `lg`), `color`, `label`, `description`, `labelPosition`, `disabled`. |
| **AppFileUpload** | `@/Components/Common/AppFileUpload.vue` | **إلزامي** لرفع الملفات (`dropzone`) والصور/الشعارات (`image`) | **Props:** `variant` (`dropzone`, `image`), `modelValue`, `accept`, `maxSizeMb`, `multiple`, `existingUrl`, `fallbackUrl`, `defaultBadge`, `compactPreview`, `size`, `label`, `hint`, `error`, `disabled`, `loading`. **Emits:** `select`, `invalid`, `remove-existing`. |

### ب. النوافذ، الجداول، والتخطيط المشترك

| المكون | المسار | الغرض والقواعد |
| :--- | :--- | :--- |
| **BaseModal** | `@/Components/BaseModal.vue` | الغلاف الموحد لجميع نوافذ النماذج. **Props:** `show`, `title`, `size` (`sm`..`full`), `closeable`, `zIndexClass`, `scrollEntire` (افتراضي `true` — يُمنع تعطيله إلا لضرورة موثقة), `overflowVisible`. **Emits:** `close`. **Slots:** `title`, `default`, `footer`. |
| **ConfirmModal** | `@/Components/ConfirmModal.vue` | نوافذ التأكيد للإجراءات الحرجة (حذف، إلغاء، استرجاع). |
| **FormField** | `@/Components/Common/FormField.vue` | غلاف موحد للحقل (`id`, `label`, `hint`, `error`, `required`, `disabled`). **Slots:** `default`, `label`, `label-action`. |
| **FormSection** | `@/Components/Common/FormSection.vue` | بطاقة تجميع الحقول مع عنوان ووصف وأزرار فرعية. |
| **SelectableCard / RadioGroup** | `@/Components/Common/{SelectableCard,RadioGroup}.vue` | اختيارات البطاقات ومجموعات الراديو المتوافقة مع الوصول. |
| **DataTable / AppPagination** | `@/Components/Common/{DataTable,AppPagination}.vue` | عرض الجداول الموحد مع `useServerTable.ts` (يدعم `columns`, `data`/`items`, `loading`, `sortColumn`, `sortDirection`, `striped`, `hoverable`, `empty*`). |
| **AppTabs / AppViewModeToggle / AppPrintButton / AppIdentityPills** | `@/Components/Common/` | التبويبات (`?tab=...`)، تبديل العرض، زر الطباعة الموحد، وشارات الهوية في `PageHeader`. |
| **Toast + useToast** | `@/Components/Toast.vue`, `@/Composables/useToast.js` | المصدر الوحيد للإشعارات في كافة التخطيطات (`AppLayout`, `SystemLayout`, `CustomerPortalLayout`, `EmployeePortalLayout`, `AuthLayout`). |
| **مكونات النطاق** | `SaudiPlateDisplay`, `SaudiPlateInput`, `SignaturePad`, `CustomDatePicker`, `ColorField`, `PrintEngine` | مكونات إلزامية لمجالاتها المتخصصة؛ يمنع بناء بدائل لها. |

---

## 2. المكونات القديمة المتوقفة (Deprecated Components)

تبقى هذه المكونات عاملة للتوافق العكسي مع الصفحات القائمة، لكن **يُمنع استيرادها في أي كود جديد** (يُنبّه عنها ESLint بتحذير `no-restricted-imports`):

| المكون القديم (Deprecated) | البديل المعتمد |
| :--- | :--- |
| `PrimaryButton.vue`, `SecondaryButton.vue`, `DangerButton.vue` | `AppButton.vue` |
| `TextInput.vue`, `InputLabel.vue`, `InputError.vue` | `AppInput.vue` + `FormField.vue` |
| `Checkbox.vue` | `AppCheckbox.vue` |
| `Toggle.vue` | `AppToggle.vue` |
| `Modal.vue`, `DialogModal.vue` | `BaseModal.vue` أو `ConfirmModal.vue` |

---

## 3. قواعد إنشاء مكون جديد
1. ابحث أولاً في `resources/js/Components/App/` و`resources/js/Components/Common/` عن مكون يؤدي الغرض أو يمكن توسيعه عبر `props` / `slots`.
2. لا تنشئ مكوناً عاماً جديداً لإصلاح حالة صفحة واحدة؛ اكتب الكود الخاص بالصفحة داخل مجلد وحدتها.
3. أي مكون عام جديد يجب أن يستهلك Design Tokens من `tokens.css` فقط، ويدعم RTL/LTR وDark/Light Mode وإمكانية الوصول (`aria-*`)، ويُوثّق في هذا الملف.
