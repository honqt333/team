---
description: Project standards and development guidelines for Carag
---

# Carag Project Rules & Guidelines

## Technology Stack
- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3 + Inertia.js + Tailwind CSS
- **Database**: MySQL with soft deletes
- **i18n**: Vue-i18n (ar/en)

---

## 1. Bilingual Support (Required for all user-facing content)

### Database
- Use `name_ar` + `name_en` columns (not just `name`)
- `name_ar` is required, `name_en` is nullable
- For text: `description_ar` + `description_en`

### Models
```php
protected $appends = ['name'];

public function getNameAttribute(): string
{
    $locale = app()->getLocale();
    return $locale === 'en' ? ($this->name_en ?: $this->name_ar) : ($this->name_ar ?: $this->name_en);
}
```

### Frontend
```javascript
import { useLocalized } from '@/Composables/useLocalized';
const { getName, getDescription } = useLocalized();
// Template: {{ getName(item) }}
```

---

## 2. Form Modals Pattern

### Rule: Modals should NOT trigger toasts or reloads
- Emit `saved` event only
- Parent page handles toast + `router.reload()`

```javascript
// Modal onSuccess:
onSuccess: () => {
    form.reset();
    emit('saved');
    emit('close');
}

// Parent handleSaved:
function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['items'] });
}
```

---

## 3. Delete Confirmation

Use `useConfirm` composable with i18n:

```javascript
import { useConfirm } from '@/Composables/useConfirm';
const { confirm } = useConfirm();

const confirmed = await confirm({
    title: t('common.confirm_delete_title'),
    message: `${getName(item)}: ${t('common.confirm_delete_message')}`,
    confirmText: t('common.delete'),
    cancelText: t('common.cancel'),
    type: 'danger',
});
```

---

## 4. Toast Notifications

```javascript
import { useToast } from '@/Composables/useToast';
const { success, error } = useToast();

success(t('common.saved_success'));
success(t('common.deleted_success'));
```

---

## 5. Status Badges

- **Active**: `bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300`
- **Inactive**: `bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300`

---

## 6. Controllers

### Inertia Responses
- `store`, `update`, `destroy` must return `redirect()->back()`
- Never return JSON for Inertia pages

```php
public function store(Request $request): RedirectResponse
{
    // ...
    return redirect()->back();
}
```

---

## 7. Translation Keys Structure

```json
{
    "module_name": {
        "title": "...",
        "add": "...",
        "edit": "...",
        "search": "...",
        "empty": "...",
        "form": {
            "name_ar": "الاسم بالعربية",
            "name_en": "Name in English"
        },
        "columns": { }
    }
}
```

---

## 8. Styling Conventions

- RTL-first design
- Dark mode support required
- Use Tailwind utility classes
- Gradient buttons: `bg-gradient-to-r from-{color}-600 to-{color2}-600`
- Rounded corners: `rounded-xl` for modals/cards, `rounded-lg` for inputs

---

## 9. File Organization

```
resources/js/
├── Components/
│   ├── {Module}/              # Module-specific components
│   │   └── {Name}Modal.vue
│   └── {Name}.vue             # Shared components
├── Composables/
│   └── use{Name}.js
├── Layouts/
│   └── AppLayout.vue
├── Pages/
│   └── {Module}/
│       └── Index.vue
└── i18n/
    └── lang/
        ├── ar.json
        └── en.json
```

---

## 10. Git Commit Messages

- Arabic or English (consistent per project)
- Format: `[type]: description`
- Types: `feat`, `fix`, `refactor`, `style`, `docs`, `chore`

---

## 11. Mobile Responsiveness (Required for ALL pages)

### Core Principles
- **Mobile-first design**: Start with mobile layout, enhance for larger screens
- **All pages must work on screens 320px and wider**
- **Touch-friendly**: Minimum tap target size of 44x44px

### Breakpoints (Tailwind defaults)
| Prefix | Min Width | Description |
|--------|-----------|-------------|
| (none) | 0px       | Mobile (default) |
| `sm:`  | 640px     | Small tablets |
| `md:`  | 768px     | Tablets |
| `lg:`  | 1024px    | Laptops |
| `xl:`  | 1280px    | Desktops |

### Required Patterns

#### Flex Layout Stacking
```html
<!-- Stack on mobile, row on desktop -->
<div class="flex flex-col lg:flex-row gap-4">
```

#### Full-Width on Mobile
```html
<input class="w-full sm:w-auto" />
<button class="w-full sm:w-auto">Submit</button>
```

#### Grid Responsiveness
```html
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
```

#### Hide/Show Elements
```html
<!-- Hide on mobile, show on desktop -->
<span class="hidden sm:inline">Full Label</span>
<!-- Show on mobile, hide on desktop -->
<span class="sm:hidden">Short</span>
```

#### Table Overflow
```html
<div class="overflow-x-auto">
    <table class="min-w-full">...</table>
</div>
```

### Checklist for New Pages
- [ ] Tested at 375px width (iPhone SE)
- [ ] Tested at 768px width (iPad)
- [ ] Tested at 1280px width (Desktop)
- [ ] No horizontal scrolling on mobile
- [ ] Text is readable without zooming
- [ ] Buttons/links have adequate touch targets
- [ ] Forms are usable on mobile
- [ ] Modals fit within mobile viewport

---

## 12. Arabic Numeral Conversion (Automatic)

All Arabic-Indic numerals (٠١٢٣٤٥٦٧٨٩) are automatically converted to Western numerals (0123456789) system-wide.

### Backend (Automatic)
- Middleware `ConvertArabicNumerals` handles all incoming request data automatically
- No action needed in controllers or form requests
- Applied to both POST data and query parameters

### Frontend (optional)
```javascript
import { useNumberFormat } from '@/Composables/useNumberFormat';
const { toEnglish, toArabic, sanitizeInput } = useNumberFormat();

toEnglish('١٢٣'); // Returns '123'
toArabic('123');  // Returns '١٢٣'

// For real-time input sanitization
<input @input="sanitizeInput" />

// Manual conversion (if needed)
const { toEnglish, toArabic } = useNumberFormat();
toEnglish('١٢٣'); // Returns '123'
toArabic('123');  // Returns '١٢٣'
```

---

## 13. English Numeral Display (Required for ALL numeric outputs)

All numbers, prices, quantities, and calculations **MUST** be displayed using English numerals (0-9) only, regardless of user locale or browser language settings.

### Frontend Display (Required)

**Always use `useNumberFormat` composable for displaying numbers:**

```javascript
import { useNumberFormat } from '@/Composables/useNumberFormat';
const { formatNumber, formatCurrency, formatInteger } = useNumberFormat();
```

#### Examples

```vue
<!-- Prices and Currency -->
<div>{{ formatCurrency(price) }} {{ $t('common.currency') }}</div>
<!-- Output: "500.00 ر.س" NOT "٥٠٠٫٠٠ ر.س" -->

<!-- Quantities -->
<div>{{ formatNumber(quantity, 2) }}</div>
<!-- Output: "1.00" NOT "١٫٠٠" -->

<!-- Integers (IDs, counts) -->
<div>{{ formatInteger(count) }}</div>
<!-- Output: "42" NOT "٤٢" -->
```

### DO NOT:
❌ Use raw numbers in templates: `{{ price }}`  
❌ Use `.toFixed()` without locale control  
❌ Rely on automatic browser localization

### DO:
✅ Always use `formatCurrency()` for prices  
✅ Always use `formatNumber()` for decimals  
✅ Always use `formatInteger()` for whole numbers

### Why This Matters
- **Input Conversion** (Section 12) handles Arabic → English for **user input**
- **Display Formatting** (Section 13) handles English-only for **visual output**
- Together they create a consistent numeric experience

### Backend
Backend already returns English numerals via `number_format()`. No changes needed.
