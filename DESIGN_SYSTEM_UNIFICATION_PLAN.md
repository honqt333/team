# خطة توحيد نظام التصميم (Design System Unification Plan)

> **الدور:** أنا المراجع والمصمم | **أنت:** المنفذ | **سأراجع** بعد كل مرحلة

---

## 📊 تحليل الفجوات الحالية

### ✅ موحد بالفعل (يستخدم Design Tokens):
| Component | يستخدم CSS Variables | الحالة |
|-----------|---------------------|--------|
| `PrimaryButton.vue` | `--color-action-primary`, `--color-action-on`, `--color-action-primary-hover` | ✅ |
| `SecondaryButton.vue` | `--color-surface`, `--color-text-primary`, `--color-border-strong` | ✅ |
| `DangerButton.vue` | `--color-danger`, `--color-text-inverse` | ✅ |
| `tokens.css` | المصدر الوحيد للحقيقة | ✅ |

### ❌ يحتاج توحيد (Hardcoded Tailwind Colors):
| Component | الألوان الصلبة | البديل المطلوب |
|-----------|----------------|----------------|
| `AppToggle.vue` | `bg-violet-600`, `bg-gray-200`, `dark:bg-gray-700`, `focus:ring-violet-600` | `--color-action-primary`, `--color-border`, `--color-focus-ring` |
| `TextInput.vue` | `focus:border-indigo-500`, `focus:ring-indigo-500`, `dark:focus:border-indigo-600` | `--color-action-primary`, `--color-focus-ring` |
| `Checkbox.vue` | `text-indigo-600`, `focus:ring-indigo-500` | `--color-action-primary`, `--color-focus-ring` |
| `SearchableSelect.vue` | `bg-violet-50`, `text-violet-700`, `focus:ring-violet-500`, `border-violet-500` | `--color-action-primary`, `--color-focus-ring` |
| `InputLabel.vue` | لا يستخدم tokens للنصوص | `--color-text-primary`, `--color-text-secondary` |
| `SelectableCard.vue` (مطلوب جديد) | `bg-violet-50`, `text-violet-700` | توحيد مع tokens |

> **ملاحظة:** `Toggle.vue` القديم أصبح deprecated، الهدف هو `AppToggle.vue`. المكون `AppTextarea.vue` موجود بالفعل وموثق.

---

## 🎯 خطة التنفيذ (4 مراحل مضغوطة)

---

### المرحلة 1: هندسة الأساس — Token Plumbing & Tailwind Config (30-45 دقيقة)

#### 1.1 `tailwind.config.js` — ربط Design Tokens
```js
// tailwind.config.js
theme: {
  extend: {
    colors: {
      // Brand
      'brand-primary': 'var(--color-brand-primary)',
      'brand-primary-hover': 'var(--color-brand-primary-hover)',
      'brand-primary-soft': 'var(--color-brand-primary-soft)',
      
      // Action (Primary actions)
      'action-primary': 'var(--color-action-primary)',
      'action-primary-hover': 'var(--color-action-primary-hover)',
      'action-primary-active': 'var(--color-action-primary-active)',
      'action-primary-soft': 'var(--color-action-primary-soft)',
      'action-primary-soft-dark': 'var(--color-action-primary-soft-dark)',
      'action-on': 'var(--color-action-on)',
      
      // Surfaces
      'surface': 'var(--color-surface)',
      'surface-muted': 'var(--color-surface-muted)',
      'surface-raised': 'var(--color-surface-raised)',
      'border': 'var(--color-border)',
      'border-strong': 'var(--color-border-strong)',
      
      // Text
      'text-primary': 'var(--color-text-primary)',
      'text-secondary': 'var(--color-text-secondary)',
      'text-muted': 'var(--color-text-muted)',
      'text-inverse': 'var(--color-text-inverse)',
      
      // Focus
      'focus-ring': 'var(--color-focus-ring)',
      'focus-ring-soft': 'var(--color-focus-ring-soft)',
      
      // Selection / Toggle states (متغير واحد يتغير في .dark تلقائياً)
      'selection-bg': 'var(--color-selection-bg)',
      'selection-text': 'var(--color-selection-text)',
      'selection-ring': 'var(--color-selection-ring)',
      
      // Toggle
      'toggle-off-bg': 'var(--color-toggle-off-bg)',
      'toggle-thumb': 'var(--color-toggle-thumb)',
      
      // Feedback (semantic)
      'success': 'var(--color-success)',
      'success-soft': 'var(--color-success-soft)',
      'warning': 'var(--color-warning)',
      'warning-soft': 'var(--color-warning-soft)',
      'danger': 'var(--color-danger)',
      'danger-soft': 'var(--color-danger-soft)',
      'info': 'var(--color-info)',
      'info-soft': 'var(--color-info-soft)',
    }
  }
}
```

#### 1.2 `tokens.css` — إضافة متغيرات الاختيار (بدون `-dark` صريحة، بلوك `.dark` يتعامل معها)
```css
/* في :root (Light Mode) */
--color-selection-bg: var(--color-action-primary-soft);
--color-selection-text: var(--color-action-primary-hover);
--color-selection-ring: var(--color-action-primary);
--color-toggle-off-bg: var(--color-border);
--color-toggle-thumb: var(--color-text-inverse);

/* في بلوك .dark — يتغير تلقائياً بدون الحاجة لـ -dark suffix في Tailwind */
.dark {
  --color-selection-bg: var(--color-action-primary-soft-dark);
  --color-selection-text: var(--color-action-primary-hover);
  --color-toggle-off-bg: var(--color-border);
}
```

**معيار القبول:** `npm run build` يمر، `TextInput` يستخدم `focus:border-action-primary focus:ring-focus-ring`

---

### المرحلة 2: توحيد الحقول الأساسية (45-60 دقيقة)

| Task | الملف | التغيير المطلوب | كلاسات Tailwind الجديدة |
|------|-------|----------------|------------------------|
| 2.1 | `TextInput.vue` | Focus rings | `focus:border-action-primary focus:ring-focus-ring` |
| 2.2 | `Checkbox.vue` | ألوان التحديد | `text-action-primary focus:ring-focus-ring border-border-strong` |
| 2.3 | `InputLabel.vue` | ألوان النصوص | `text-text-primary` للـ label، `text-text-secondary` للـ hint |
| 2.4 | `AppTextarea.vue` | توحيد مع TextInput | نفس كلاسات TextInput |

**ملاحظة:** `AppTextarea.vue` موجود بالفعل (وليس مفقوداً) — نحدثه فقط.

**معيار القبول:** لا `indigo-*` أو `violet-*` في هذه الملفات، Dark mode يعمل تلقائياً

---

### المرحلة 3: مفاتيح التبديل والقوائم (45-60 دقيقة)

| الملف | التغيير | كلاسات Tailwind الجديدة |
|-------|---------|------------------------|
| `AppToggle.vue` | الهدف الحقيقي | `bg-action-primary` (ON)، `bg-toggle-off-bg` (OFF)، `focus:ring-focus-ring`، thumb: `bg-text-inverse` |
| `SearchableSelect.vue` | Dropdown states | `bg-selection-bg` للـ selected، `focus:ring-focus-ring`، `border-action-primary`، `text-selection-text` |

**ملاحظة هامة:** `Toggle.vue` القديم ← Deprecated، نحدث `AppToggle.vue` فقط.

**معيار القبول:** لا `violet-*` أو `indigo-*` في هذه الملفات، Dark mode يعمل تلقائياً

---

### المرحلة 4: مكونات موحدة جديدة (60-90 دقيقة)

| المكون الجديد | الغرض | يستبدل |
|--------------|-------|---------|
| `FormField.vue` | Wrapper موحد: Label + Input/Select + Error + Hint | تكرار في 50+ صفحة |
| `FormSection.vue` | Fieldset موحد لتجميع الحقول مع title/description | fieldset مكرر |
| `SelectableCard.vue` | بطاقة قابلة للتحديد (بديل للـ checkbox في القوائم) | checkbox في القوائم |
| `RadioGroup.vue` | لمجموعات Radio buttons (مفقود حالياً) | — |
| `AppSelect.vue` | **لا ننشئه** — نطور `SearchableSelect.vue` مباشرة | — |

**ملاحظة:** لا ننشئ `AppSelect.vue` — نطور `SearchableSelect.vue` مباشرة ليكون الـ API الرسمي.

**معيار القبول:** المكونات الجديدة تستخدم Tailwind tokens classes فقط، تعمل في Light/Dark/RTL

---

### المرحلة 5: توثيق + Enforcement (15-20 دقيقة)

| المهمة | الملف |
|---------|-------|
| دليل الاستخدام | `COMPONENT_USAGE.md` |
| ESLint Rule | `eslint.config.js` — إضافة `no-hardcoded-colors` |
| تحديث `AGENTS.md` | إضافة قواعد المكونات الجديدة وقواعد المنع |

---

## ✅ معايير القبول النهائية (Final DoD)

```bash
# 1. صفر ألوان صلبة في مكونات UI المستهدفة (Forms/Inputs/Toggles/Selects)
# ملاحظة: نستثني مكونات الشارات واللوحات (SaudiPlateDisplay، StatusBadges، إلخ)
grep -rE "(violet|indigo|blue|purple|red|green|yellow|orange|pink|teal|cyan|sky|slate|gray|gray|stone|neutral|zinc)-(50|100|200|300|400|500|600|700|800|900|950)" \
  resources/js/Components/{FormField,FormSection,TextInput,Checkbox,AppToggle,AppTextarea,SearchableSelect,SelectableCard,RadioGroup,InputLabel,PrimaryButton,SecondaryButton,DangerButton,SecondaryButton}.vue \
  --include="*.vue" | grep -v "var("
# → الناتج: فارغ

# 2. Focus rings موحدة
grep -r "focus:ring-" resources/js/Components --include="*.vue" | grep -v "focus-ring"
# → الناتج: فارغ

# 3. Build يمر
npm run build
# → نجاح

# 4. Dark mode يعمل
# اختبار يدوي: تفعيل dark mode ← لا مشاكل بصرية في المكونات المحدثة
```

---

## 📋 قائمة التحقق السريعة (Quick Checklist)

```bash
# التحقق من عدم وجود ألوان صلبة في مكونات النماذج فقط
grep -rE "(violet|indigo|blue|purple|red|green|yellow|orange|pink|teal|cyan|sky|slate|gray|gray|stone|neutral|zinc)-(50|100|200|300|400|500|600|700|800|900|950)" \
  resources/js/Components/FormField.vue \
  resources/js/Components/FormSection.vue \
  resources/js/Components/TextInput.vue \
  resources/js/Components/Checkbox.vue \
  resources/js/Components/AppToggle.vue \
  resources/js/Components/AppTextarea.vue \
  resources/js/Components/SearchableSelect.vue \
  resources/js/Components/SelectableCard.vue \
  resources/js/Components/RadioGroup.vue \
  resources/js/Components/InputLabel.vue \
  resources/js/Components/PrimaryButton.vue \
  resources/js/Components/SecondaryButton.vue \
  resources/js/Components/DangerButton.vue \
  --include="*.vue" | grep -v "var("

# التحقق من استخدام tokens للـ focus
grep -r "focus:ring-" resources/js/Components --include="*.vue" | grep -v "focus-ring"

# التحقق من استخدام tokens للـ selection
grep -r "bg-violet-50\|bg-violet-100\|text-violet-" resources/js/Components --include="*.vue"
```

---

## 🚀 خريطة الطريق الزمنية المضغوطة

| الجلسة | المدة | المحتوى |
|---------|-------|---------|
| **جلسة 1** | ~60-90 دقيقة | المرحلة 1 + 2 (Tailwind Config + Inputs الأساسية) |
| **جلسة 2** | ~60-90 دقيقة | المرحلة 3 + 4 (AppToggle, SearchableSelect + مكونات جديدة) |
| **جلسة 3** | ~30-45 دقيقة | المرحلة 5 (توثيق + Enforcement + مراجعة شاملة) |

**المجموع:** ~2.5 - 4 ساعات عمل فعلية (بدلاً من 4 أسابيع)

---

## 🎯 نقطة البداية الفورية

**ابدأ بـ:** تحديث `tailwind.config.js` + إضافة المتغيرات في `tokens.css` (المرحلة 1)

بمجرد أن يعمل `npm run build` وتستخدم `TextInput` الكلاس `focus:border-action-primary` — أبلغني للمراجعة قبل الانتقال للمرحلة 2.

---

**جاهز للبدء؟ ابدأ بالمرحلة 1 وأبلغني عند الانتهاء.**