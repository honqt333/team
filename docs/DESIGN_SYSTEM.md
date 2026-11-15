# نظام التصميم والهوية البصرية — خدمة برو (Khidmah Pro)

> **الحالة:** `Active`
> **المرجع الأعلى:** [AGENTS.md](../AGENTS.md)
> **مصدر التوكنز الفعلي:** [resources/js/Design/tokens.css](../resources/js/Design/tokens.css) · [tailwind.config.js](../tailwind.config.js)

---

## 1. الألوان الدلالية (Semantic Colors)

المرجع الوحيد للألوان هو `resources/js/Design/tokens.css` (إصدار v2 Clean Modern):

| الفئة | كلاسات Tailwind المعتمدة | متغيرات CSS (Light → Dark) |
| :--- | :--- | :--- |
| **العلامة والإجراء الأساسي** | `bg-brand-primary`, `bg-action-primary`, `hover:bg-action-primary-hover`, `text-action-on`, `bg-action-primary-soft` | `--color-brand-primary` (`#2563eb` → `#3b82f6`), `--color-action-primary` (`#2563eb` → `#3b82f6`) |
| **التمييز الذهبي للشعار** | `text-accent-gold`, `bg-accent-gold-soft` | `--color-accent-gold` (`#f59e0b` → `#fbbf24`), `--color-accent-gold-light` (`#fbbf24`) |
| **الأسطح والحدود** | `bg-surface`, `bg-surface-muted`, `bg-surface-raised`, `bg-surface-tertiary`, `border-border`, `border-border-strong` | `--color-surface` (`#ffffff` → `#131d2e`), `--color-background` (`#f8fafc` → `#0b1120`) |
| **النصوص** | `text-text-primary`, `text-text-secondary`, `text-text-muted`, `text-text-inverse` | `--color-text-primary` (`#0f172a` → `#f1f5f9`) |
| **الحالات الدلالية** | `bg-success-soft`, `text-success`, `bg-warning-soft`, `text-warning`, `bg-danger-soft`, `text-danger`, `bg-info-soft`, `text-info` | `--color-success`, `--color-warning`, `--color-danger`, `--color-info` |
| **التركيز والتحديد والتبديل** | `focus:ring-focus-ring`, `bg-selection-bg`, `text-selection-text`, `bg-toggle-off-bg`, `bg-toggle-thumb` | `--color-focus-ring`, `--color-selection-*`, `--color-toggle-*` |

- **قاعدة المنع:** يُمنع إدخال ألوان Tailwind الصلبة (`indigo-*`, `violet-*`, `slate-*`, `gray-*`, إلخ) أو أكواد Hex ثابتة داخل المكونات الجديدة.
- **ملاحظة تاريخية:** الجداول القديمة في [brand-guidelines.md](brand-guidelines.md) التي تذكر `--color-brand-primary: #fbbf24` قديمة ومرجعها الفعلي هنا هو `--color-accent-gold`، بينما اللون الأساسي للنظام هو الأزرق (`#2563eb`).

---

## 2. الخطوط (Typography)
- **الخط الرسمي الوحيد:** **`IBM Plex Sans Arabic`** (`--font-family-sans`, `--font-family-display`) لجميع الواجهات والتقارير وشاشات الطباعة.
- يُمنع استدعاء أو استخدام `Cairo`, `Figtree`, أو `Tajawal`.
- السلم القياسي في `tokens.css`: `--font-size-xs` (`0.75rem`) إلى `--font-size-4xl` (`2.25rem`)، والأوزان `400`, `500`, `600`, `700`.

---

## 3. المسافات، الزوايا، الظلال، والحركة
- **المسافات:** سلّم `--space-0` إلى `--space-24` (`0.25rem` مضاعفات).
- **الزوايا (Border Radius):** `--radius-sm` (`0.25rem`)، `--radius-md` (`0.5rem`)، `--radius-lg` (`0.75rem`)، `--radius-xl` (`1rem`)، `--radius-2xl` (`1.25rem` للهيدر والبطاقات والنوافذ)، `--radius-full`.
- **الظلال:** `--shadow-xs`, `sm`, `md`, `lg`, `xl`, و `--shadow-focus`.
- **هندسة الهيدر (`PageHeader.vue`):** في RTL، العنوان والشارة أقصى اليمين، يليه شريط الأدوات الزجاجي، ثم زر الإجراء الأساسي (`CTA`) أقصى اليسار.

---

## 4. الوضع الداكن/الفاتح و RTL/LTR وحالات المكونات والوصول
- **Dark / Light Mode:** يتم التبديل عبر `<html class="dark">` (و`:root[data-theme='dark']`). استخدام التوكنز الدلالية يضمن التكيف التلقائي دون تكرار أصناف `dark:*` عشوائية.
- **RTL / LTR:** استخدم دائماً الخصائص المنطقية (`ms-*`, `me-*`, `ps-*`, `pe-*`, `start-*`, `end-*`, `text-start`, `text-end`) بدلاً من `ml-*`/`mr-*`/`left-*`/`right-*`.
- **حالات المكونات:** كل مكون إدخال/زر يجب أن يدعم بوضوح حالات `hover`, `focus-visible`, `active`, `disabled`, `loading`, و`error`.
- **إمكانية الوصول (Accessibility):**
  - ربط `label` بالحقل via `for`/`id`، واستخدام `aria-invalid`, `aria-required`, `aria-describedby` للأخطاء والتلميحات (`role="alert"`).
  - أزرار الأيقونات فقط يجب أن تحمل `aria-label` مترجماً.
- **حوكمة اللوحات:** تشترك جميع اللوحات (`AppLayout`, `SystemLayout`, `CustomerPortalLayout`, `EmployeePortalLayout`, `AuthLayout`) في التوكنز والمكونات نفسها دون فرض تخطيط هيكلي واحد متطابق.
