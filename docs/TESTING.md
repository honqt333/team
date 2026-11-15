# بوابات الجودة والاختبارات وحوكمة ADR — خدمة برو (Khidmah Pro)

> **الحالة:** `Active`
> **المرجع الأعلى:** [AGENTS.md](../AGENTS.md)

---

## 1. مصفوفة بوابات الجودة حسب نوع التغيير (Quality Gates)

| نوع التغيير | الاختبارات والأوامر الإلزامية |
| :--- | :--- |
| **Backend (Controllers / Actions / Services / Policies)** | `php artisan test <TargetTest>` + `composer lint` (`pint --test`) + `composer stan` |
| **صلاحيات / عزل مستأجرين أو فروع / نماذج جديدة** | اختبارات الصلاحيات والعزل + `bash scripts/check-tenant-scope.sh` |
| **Frontend (Vue / TS / Composables)** | `npm run lint` + `npm run type-check` + `npm run test` + `npm run build` |
| **تعديل مفاتيح أو نصوص الترجمة** | `node scripts/check-i18n-parity.mjs` + `npm run build` + `php artisan optimize:clear` |
| **تعديل توثيق / حوكمة فقط** | مراجعة الاتساق والروابط + `git diff` + تشغيل الفحوصات المرتبطة إن شمل تعديل كود أو lint |

---

## 2. تصنيف القواعد الآلية

- **Enforced (مطبقة آلياً في CI):**
  - `composer lint` (`vendor/bin/pint --test` — يشمل `declare_strict_types: true`).
  - `composer stan` (`vendor/bin/phpstan analyse` level 5 مع `phpstan-baseline.neon`).
  - `bash scripts/check-tenant-scope.sh`.
  - `php artisan test` (على `sqlite :memory:` المعزول وفق `phpunit.xml`).
  - `npm run lint` (ESLint v8 عبر `.eslintrc.cjs` — `v-safe-html` كـ `error`، والتحذيرات التاريخية `warn`).
  - `npm run type-check` (`vue-tsc --noEmit`).
  - `npm run test` (Vitest) و`npm run build` (يولّد `page-manifest.js` ويبني الأصول).
- **Enforceable (قابلة للتفعيل التدريجي):**
  - `node scripts/check-i18n-parity.mjs` (تطابق مفاتيح `ar.json`/`en.json` بعد إغلاق المفاتيح الـ 3 المتبقية).
  - ترقية ESLint إلى v9 لاحقاً لتفعيل `design-system/no-hardcoded-colors` من `eslint.config.js`.
- **Advisory:** `composer audit` و`npm audit` في CI (`continue-on-error: true` حالياً).
- **Review Required:** التفويض المنطقي، اختيار Service مقابل Action، ومراجعة `git diff`.

---

## 3. سياسة التعامل مع Baseline Failures
- يُمنع إخفاء أو حذف أو إضعاف أي اختبار فاشل مسبقاً خارج نطاق المهمة.
- يُمنع الادعاء بأن "كامل اختبارات المشروع ناجحة" إذا كان الفحص جزئياً أو احتوى على فشل قائم مسبقاً.
- يُوثّق أي فشل قائم (Baseline Failure) باسم الملف ورقم السطر وسبب عدم ارتباطه بالتغيير الحالي (مثال: `tests/Feature/WorkOrdersCrudTest.php:1349`).

---

## 4. توثيق القرارات الهندسية (ADR)
- تُنشأ ملفات ADR في `docs/ADR/NNNN-short-title.md` فقط للقرارات المعمارية المؤثرة أو الاستثناءات الدستورية المعتمدة (وليس للتعديلات البسيطة).
- يجب أن يحتوي كل ADR على: **Status** · **Context** · **Decision** · **Alternatives** · **Consequences** · **Migration Plan** (إن وجد).
