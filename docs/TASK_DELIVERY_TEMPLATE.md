# قالب تقرير تسليم المهام — خدمة برو (Khidmah Pro)

> يُستخدم هذا القالب عند إتمام أي صفحة أو ميزة أو تعديل جوهري وفق [DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md).

---

## 1. ملخص المتطلبات والنطاق
- **الهدف / الميزة:** `<وصف مختصر للمطلوب>`
- **اللوحة المستهدفة:** `<AppLayout | SystemLayout | CustomerPortalLayout | EmployeePortalLayout | AuthLayout>`
- **الحالة النهائية:** `<READY | BLOCKED | NEEDS VISUAL REVIEW>`

## 2. المكونات المعاد استخدامها (من `COMPONENT_REGISTRY.md`)
- `<مثال: PageHeader, DataTable, AppButton, SearchableSelect, BaseModal, FormField>`
- **مكونات جديدة (إن وجدت):** `<لا يوجد — أو اسم المكون ومبرر عدم إمكانية إعادة الاستخدام>`

## 3. الملفات الجديدة والمعدلة
| النوع | المسار | الغرض |
| :--- | :--- | :--- |
| جديد / معدل | `path/to/file` | `<وصف التغيير>` |

## 4. مصادر البيانات والعزل
- **مصدر الحقيقة المستخدم:** `<مثال: page.props.tenant.currency, Setting::forTenant(...)>`
- **عزل المستأجر/الفرع:** `<TenantScoped / CenterScoped / Global مع @bypass-tenancy-scanner>`

## 5. الترجمات (`i18n`)
- **المفاتيح المضافة/المحدثة:** `<قائمة أو النطاق في ar.json / en.json / lang/*.php>`
- **نتيجة `check-i18n-parity.mjs`:** `<PASS | FAIL | NOT VERIFIED | NOT APPLICABLE>`

## 6. الصلاحيات والأمان
- **Policy / Gate / Middleware:** `<اسم الـPolicy أو الصلاحية المفروضة في الخادم>`
- **التدقيق والمعاملات:** `<logActivity / DB::transaction + lockForUpdate إن وجدت>`

## 7. نتائج الفحوصات والاختبارات
| الفحص | الأمر | الحالة | الملاحظات / الأرقام الفعلية |
| :--- | :--- | :---: | :--- |
| دستور الواجهة الآلي | `npm run check:ui` | `PASS/FAIL/NOT VERIFIED/NOT APPLICABLE` | |
| ESLint | `npm run lint` | `...` | |
| Type Check | `npm run type-check` | `...` | |
| Vitest | `npm run test` | `...` | |
| Build | `npm run build` | `...` | |
| PHPUnit | `php artisan test ...` | `...` | |
| Pint & PHPStan | `composer lint && composer stan` | `...` | |
| الفحص البصري (المتصفح) | Dark/Light + RTL/LTR | `...` | *(الفحص الآلي لا يثبت الجودة البصرية)* |

## 8. المخالفات والاستثناءات
- **مخالفات جديدة:** `<0 — أو تفاصيلها>`
- **استثناءات مطبقة (`NC-001..NC-004` / `ADR`):** `<إن وجدت>`
- **Baseline Failures (قائمة مسبقاً خارج النطاق):** `<إن وجدت>`

## 9. ما لم يتم التحقق منه
- `<قائمة واضحة بأي فحص صُنّف NOT VERIFIED وسبب ذلك، مثل: الفحص البصري في المتصفح>`
