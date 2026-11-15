---
trigger: always_on
---

# حوكمة مهارات تصميم الواجهات — خدمة برو (Khidmah Pro)

تُطبَّق هذه القاعدة دائماً قبل استخدام أي مهارة تصميم واجهات أو تعديل أي ملف واجهة في المشروع.

## 1. أولوية مصادر الحقيقة (المرجعية الملزمة)

عند أي تعارض بين مخرجات مهارة خارجية ووثائق المشروع، تسود وثائق المشروع بالترتيب التالي:

1. `AGENTS.md` (الدستور الهندسي الأعلى)
2. `docs/DESIGN_SYSTEM.md` (الألوان الدلالية، الخط، RTL/LTR، Dark/Light)
3. `docs/COMPONENTS.md` (حوكمة المكونات والمكونات المتوقفة `Deprecated`)
4. `docs/COMPONENT_REGISTRY.md` (السجل التفصيلي للمكونات المعتمدة)
5. `resources/js/Design/tokens.css` (المصدر الوحيد الفعلي لقيم التوكنز)

## 2. القيود الفنية الصارمة

المشروع مبني على **Laravel + Vue 3 (`<script setup>`) + Inertia.js 2 + Tailwind CSS**. يُمنع منعاً باتاً:
- تغيير الخط الرسمي **`IBM Plex Sans Arabic`** أو إضافة `Cairo` أو `Figtree` أو `Tajawal`.
- إضافة أكواد Hex ثابتة أو ألوان Tailwind صلبة خارج `resources/js/Design/tokens.css`.
- إنشاء نظام Tokens موازٍ أو اعتماد `MASTER.md` أو `DESIGN.md` كمرجع أعلى من وثائق المشروع.
- استبدال Vue بمكونات React أو إضافة `shadcn/ui` أو `@radix-ui/*`.
- إنشاء مكونات بديلة أو موازية للمكونات المعتمدة في `docs/COMPONENT_REGISTRY.md` (`App*`, `SearchableSelect`, `AppToggle`, `AppFileUpload`, `BaseModal`, `ConfirmModal`, `DataTable`, `Toast`).
- تغيير الهوية البصرية أو الاسم الرسمي (**خدمة برو / Khidmah Pro**) دون موافقة صريحة.

## 3. تقييد المهارات الخارجية

- **`ui-ux-pro-max`:** للاستشارة فقط (`--domain ux|icons|chart` و`--stack vue|laravel`) ضمن الهوية الحالية. يُمنع استخدام `--persist` أو توليد `MASTER.md` أو تغيير الخط والألوان.
- **`impeccable`:** تُستخدم نسخة `.agent/skills/impeccable/` فقط للفحص والتحسين المحدود (`audit`, `critique`, `polish`, `harden`, `adapt`, `clarify`, `optimize`, و`impeccable detect --json`) مع الحفاظ على الهوية الحالية. يُمنع مسار إعادة التصميم الشاملة (`redesign`) أو إنشاء `DESIGN.md` بديل دون موافقة صريحة.
- **`ui-styling`:** **معطّلة** في كود التطبيق لأنها مبنية لـ React و`shadcn/ui`.
- **`design`:** مسموحة فقط لأصول الهوية التسويقية غير المرتبطة بكود Vue (مثل الشعارات والبنرات والعروض)، ويُمنع اتباع توجيهها إلى `ui-styling` أو `design-system` في واجهات النظام.
- **`design-system`:** يُمنع استخدامها لإنشاء طبقة Tokens أو إعداد Tailwind موازٍ لـ `tokens.css`.

## 4. بروتوكول التنفيذ والتوقف عند التعارض

1. اقرأ `AGENTS.md` و`docs/DESIGN_SYSTEM.md` و`docs/COMPONENTS.md` و`docs/COMPONENT_REGISTRY.md` قبل استدعاء أي مهارة خارجية.
2. ابحث في `docs/COMPONENT_REGISTRY.md` و`resources/js/Components/{App,Common}/` لإعادة استخدام المكونات القائمة قبل إنشاء أي مكون جديد.
3. إذا تعارضت توصية مهارة خارجية مع أي قاعدة هنا، **توقف فوراً قبل تعديل أي ملف** وبلّغ المستخدم بالتعارض.
