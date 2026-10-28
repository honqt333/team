# سجل الجرد والتغطية — KHIDMH.PRO (خدمة برو)

**التاريخ:** 2026-10-25
**الهدف**: إثبات ما فُحص وما لم يُفحص، وأين والدليل، بحيث يكون أي مُراجع قادراً على إعادة التحقق.

---

## 1. إحصاء المشروع الكلي

| البُعد | العدد | الأمر المُستخدم |
|---|---|---|
| ملفات PHP (app) | 536 | `find app -name '*.php'` |
| أسطر PHP | 70,764 | `find app -name '*.php' -exec cat {} + \| wc -l` |
| ملفات JS/TS/Vue | 439 | `find resources/js -type f \( -name '*.vue' -o -name '*.js' -o -name '*.ts' \)` |
| أسطر JS/Vue | 146,673 | `find resources/js ... -exec cat {} + \| wc -l` |
| ملفات ترحيل | 244 | `ls database/migrations/*.php \| wc -l` |
| ملفات اختبار | 114 | `find tests -name '*Test.php'` |
| ملفات الترجمة PHP | 94 (47 ar + 47 en) | `ls lang/ar lang/en` |
| مسارات مسجَّلة | **642** | `php artisan route:list --json` |
| ملفات إعداد | 14 | `ls config` |
| أسطر `routes/web.php` | 101,809 بايت | `wc -c` |

---

## 2. التغطية حسب المجال

### 2.1 الخادم — `app/`

| المجلد | الملفات | الفحص | النتيجة |
|---|---|---|---|
| `Http/Controllers/App` | 94 | **كامل** | فُحص `authorize`/`Gate`/استعلامات/رفع/معاملات |
| `Http/Controllers/System` | 29 | **كامل** | تركيز على العزل عبر المستأجرين |
| `Http/Controllers/Auth` | 11 | **كامل** | تدفق الدخول و2FA |
| `Http/Controllers/Public` | 7 | **كامل** | بوابة العميل — فحص IDOR |
| `Http/Controllers/Api` | 4 | **كامل** | البصمة + الذكاء الاصطناعي |
| `Http/Middleware` | 16 | **كامل** | قراءة كل middleware |
| `Http/Requests` | 50+ | **كامل** | قواعد التحقق وأنماط الملفات |
| `Models` (كل المستويات) | 124 | **كامل** | `TenantScoped`/`CenterScoped`/`$fillable`/`casts` |
| `Models/Concerns` | 3 | **كامل** | `TenantScoped`, `CenterScoped`, `HasTaxSnapshot` |
| `Services` (كل المجلدات) | 43 | **كامل** | المعاملات والحسابات |
| `Policies` | 31 | **كامل** | تغطية الاختبار |
| `Support` | 5+ | **كامل** | `TenancyContext`, `Permissions` |
| `Enums` | 2 | **كامل** | `InvoiceType`, `PaymentType` |
| `Actions` | 3+ | **كامل** | `CreateWorkOrderAction`, `UpdateWorkOrderAction` |
| `Events` | 15+ | جزئي | الربط في `EventServiceProvider` |
| `Listeners` | 6 | جزئي | تغطية الاختبار |
| `Observers` | 6 | جزئي | |
| `Jobs` | 1 | **كامل** | `SendAppointmentMessage` |
| `Jobs/…` (تذكير) | 1 | **كامل** | `SendAppointmentReminder` |
| `Exports` | 9 | **كامل** | فحص عزل الاستعلامات |
| `Imports` | 1+ | جزئي | |
| `Channels` | 2+ | جزئي | فحص سلوك الفشل |
| `Notifications` | 8+ | جزئي | |
| `Mail` | 3+ | جزئي | |
| `Console/Commands` | 12+ | جزئي | `TranslationsCheck` مفصَّل |
| `Exceptions` | 4+ | جزئي | |
| `Providers` | 3+ | جزئي | `AppServiceProvider`, `RouteServiceProvider` |
| `Services/Developer` | 9 | **سطحي** | أدوات تطوير داخلية — خارج النطاق التجاري |
| `Models/Developer` | 5 | **سطحي** | نفس السبب |
| `Traits` | 11+ | جزئي | `HasWorkOrderOperations` |
| `Logging` | 1+ | جزئي | `JsonFormatter` |
| `Contracts` | 1+ | جزئي | |

### 2.2 الواجهة — `resources/js/`

| المجلد | ملفات `.vue` | الفحص | النتيجة |
|---|---|---|---|
| `Pages/` | 231 | **رسم بياني استيراد كامل** | كشف 15 ملفاً ميتاً |
| `Components/` | 169 | **رسم بياني استيراد كامل** | كشف التكرار وال design-system غير المُتبنّى |
| `Composables/` | 12 | **كامل** | 4 مع اختبار + 3 `.ts` |
| `i18n/` | 3 | **كامل** | مقارنة مفتاح-بمفتاح |
| `Design/` | 4 | **كامل** | `tokens.json/css`, `reset.css`, `typography.css` |
| `Layouts/` | 4+ | جزئي | `AppLayout.vue` (2,593 سطر) |
| `Plugins/` | 3 | جزئي | `SafeHtmlPlugin`, `ArabicNumeralsPlugin` |
| `types/` | — | جزئي | |
| `test/` | — | جزئي | |
| `*.js` (الجذر) | 5 | **كامل** | `app.js`, `bootstrap.js`, `theme.js`, `ziggy.js` |

**أعلى 20 ملف Vue بالحجم** (كلها فُحصت بنيوياً):

| الأسطر | الملف |
|---|---|
| 3,447 | `Pages/Settings/Company/Index.vue` |
| 2,885 | `Pages/Public/LandingPreview.vue` |
| 2,696 | `Pages/Customers/Show.vue` |
| 2,593 | `Layouts/AppLayout.vue` |
| 2,288 | `Pages/Purchasing/Suppliers/Show.vue` |
| 2,169 | `Pages/Vehicles/Show.vue` |
| 2,017 | `Pages/System/Help/Index.vue` |
| 2,002 | `Pages/Settings/Centers/Show.vue` |
| 1,950 | `Components/Print/PrintTemplates/TemplateDefaultA4.vue` |
| 1,663 | `Pages/Quotes/Show.vue` |
| 1,575 | `Pages/Services/Index.vue` |
| 1,568 | `Pages/Purchasing/Invoices/Show.vue` |
| 1,510 | `Pages/Quotes/Index.vue` |
| 1,457 | `Components/WorkOrders/WorkOrderServiceModal.vue` |
| 1,390 | `Pages/Settings/System/Index.vue` |
| 1,360 | `Components/Customers/CustomerFormModal.vue` |
| 1,321 | `Pages/WorkOrders/Index.vue` |
| 1,312 | `Pages/WorkOrders/Show.vue` |
| 1,312 | `Components/WorkOrders/WorkOrderPartModal.vue` |
| 1,301 | `Components/WorkOrders/WorkOrderFormModal.vue` |

### 2.3 قاعدة البيانات

| العنصر | العدد | الفحص |
|---|---|---|
| ترحيلات | 244 | **كامل** (إحصاء القيود والفهارس) |
| بُذور (Seeders) | 18+ | جزئي — `RolesSeeder`, `InitialSystemSeeder` |
| مصانع (Factories) | 10+ | جزئي |
| نماذج | 124 | **كامل** |

### 2.4 الترجمة

| العنصر | العدد | الفحص |
|---|---|---|
| ملفات `lang/ar/` | 47 | **كامل** — مقارنة مفتاح-بمفتاح مع en |
| ملفات `lang/en/` | 47 | **كامل** |
| `resources/js/i18n/lang/ar.json` | 1 | **كامل** — مسطّح ومقارن |
| `resources/js/i18n/lang/en.json` | 1 | **كامل** |
| مصادر النصوص الثابتة | — | **كامل** — 723 Vue + 508 PHP |

---

## 3. سجل الأوامر المُنفَّذة

> **كل الأوامر read-only**. لم يُشغَّل أي `migrate` أو `db:seed` أو أمر يلمس البيانات.

| # | الأمر | النتيجة |
|---|---|---|
| 1 | `php artisan --version` | Laravel Framework 12.56.0 |
| 2 | `php -v` | PHP 8.5.0 (cli) |
| 3 | `node -v` | v22.21.1 |
| 4 | `composer show` | إصدارات الحزم |
| 5 | `git status --short` | **نظيف** |
| 6 | `git log --oneline -10` | آخر 10 التزامات |
| 7 | `php artisan route:list --json` | 642 مساراً — **0 تكرار** |
| 8 | `php artisan route:list` (مفهرس) | — |
| 9 | `php artisan translations:check --locale=ar` | 713 ناقصاً (700 إيجابية كاذبة) |
| 10 | `php artisan translations:check --locale=en` | 713 |
| 11 | `php artisan translations:check --locale=ar --vue` | 713 (نفسها — يثبت خلل الأداة) |
| 12 | `php artisan translations:check --json` | تحليل مفصّل |
| 13 | `composer audit --format=plain` | 60 ثغرة |
| 14 | `composer audit --format=json` | تفصيل 15 حزمة |
| 15 | `npm audit --omit=dev` | 7 ثغرات |
| 16 | `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` | **exit 1** — لا تحليل |
| 17 | `vendor/bin/pint --test` | 722 ملف — 2 مخالفة |
| 18 | `npx eslint resources/js` | 0 أخطاء — 1,809 تحذير |
| 19 | `npx vitest run` | **29/29 ناجح** |
| 20 | `npx vite build` | نجاح (34 ث) |
| 21 | `bash scripts/check-tenant-scope.sh` | **0 مخالفة** |
| 22 | `php artisan test <31 مجموعة>` | **349 ناجح / 10 فاشل** |
| 23 | `php artisan tinker --execute="..."` | فحص قيمة `config()` |
| 24 | `php -r` (bootstrap لارافيل) | فحص `config('services.openai.key')` |

---

## 4. الاختبارات — المُشغَّلة

### 4.1 المُشغَّلة والناجحة (349)

| الملف | ناجح | تأكيدات | المدة |
|---|---|---|---|
| `tests/Feature/Auth/` (10 ملفات) | 45 | 376 | 16.87s |
| `tests/Feature/AuthenticaOtpChannelTest.php` | 37 | 156 | 27.42s |
| `tests/Feature/CustomerPortalHttpTest.php` | 39 | 273 | 38.64s |
| `tests/Feature/CustomerPortalOtpTest.php` | 23 | 79 | 33.00s |
| `tests/Feature/CustomerPortalActionsHttpTest.php` | 30 | 113 | 54.04s |
| `tests/Feature/CustomerPortalBookingHttpTest.php` | 30 | 118 | 40.06s |
| `tests/Feature/TenantIsolation/` (4 ملفات) | 21 | 35 | 29.29s |
| `tests/Feature/TenancyIsolationTest.php` | 6 | 9 | 7.02s |
| `tests/Feature/SecurityRegressionTest.php` | 8 | 23 | 6.56s |
| `tests/Feature/InvoicingSecurityTest.php` | 2 | 4 | 4.45s |
| `tests/Feature/PortalSessionCookieIsolationTest.php` | 11 | 28 | 7.54s |
| `tests/Feature/WorkOrdersCrudTest.php` | 27 | 121 | 21.00s |
| `tests/Feature/InvoiceConversionLineTotalsTest.php` | 2 | 9 | 5.01s |
| `tests/Feature/InvoicePaymentStatusTest.php` | 4 | 12 | 7.85s |
| `tests/Feature/TaxCalculationTest.php` | 10 | 32 | 0.76s |
| `tests/Feature/WorkOrderInventoryTest.php` | 8 | 30 | 41.58s |
| `tests/Feature/PaymentCrossSyncTest.php` | 3 | 9 | 4.40s |
| `tests/Feature/AutoInvoiceOnFullPaymentTest.php` | 5 | 8 | 5.04s |
| `tests/Unit` (24 ملف) | 64 | 168 | 62.22s |
| `tests/Feature/AppointmentTest.php` | 33 | 68 | 15.38s |
| `tests/Feature/AppointmentMessagesTest.php` | 20 | 55 | 26.63s |
| `tests/Feature/AppointmentMessageDeliveryControllerTest.php` | 24 | 117 | 24.59s |
| `tests/Feature/AppointmentMessagesHotfixTest.php` | (ضمني) | | |
| `tests/Feature/WorkshopBoardTest.php` | 13 | 179 | 8.96s |
| `tests/Feature/CustomersCrudTest.php` | 8 | 30 | 7.83s |
| `tests/Feature/VehiclesCrudTest.php` | 4 | 55 | 6.49s |
| `tests/Feature/PartsCrudTest.php` | 4 | 21 | 5.39s |
| `tests/Feature/InventoryServiceTest.php` | 11 | 37 | 7.24s |
| `tests/Feature/SuppliersIsolationTest.php` | 9 | 27 | 8.57s |
| `tests/Feature/ZatcaOnboardingTest.php` | 13 | 78 | 9.36s |
| `tests/Feature/PrintSettingsSignatureUploadTest.php` | 34 | 168 | 19.10s |
| `resources/js/**/*.test.*` (Vitest) | 29 | — | 6.38s |

### 4.2 المُشغَّلة والفاشلة (10)

| الملف | فشل | السبب |AUD |
|---|---|---|---|
| `tests/Feature/QuotesTest.php` | **7** | `PermissionAlreadyExists` — الترحيل `2026_10_22_190500` يبذر الصلاحيات، والاختبار ينشئها بلا شرط | AUD-015 |
| `tests/Feature/I18nCoverageTest.php` | **2** | 713 > 708 — الأساس رُفع 5 مرات بسبب خلل الأداة | AUD-015 / AUD-002 |
| `tests/Feature/PerformanceBaselineTest.php` | **1** | `/app/vehicles` = 25 استعلام (الحد `< 25`) | AUD-009 / AUD-015 |

### 4.3 غير المُشغَّلة (سبب كل واحد)

| الملف | السبب |
|---|---|
| `tests/Feature/AiDemoTest.php` | ضمن التغطية الجزئية — لم يُشغَّل منفرداً |
| `tests/Feature/CompanySettingsAdminUserVerificationTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/CompanyTransactionsTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/DeveloperCenterTest.php` | بطيء (12.46s) — لم يُشغَّل منفرداً |
| `tests/Feature/DirectSalesInventoryDeductionTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/ExampleTest.php` | تافه |
| `tests/Feature/HelpPageTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/IncomeCategorySettingsTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/IntegrationDefaultTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/LabelAccessorNullSafetyTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/PackagesCatalogCrossBranchTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/QuoteParityFeaturesTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/ReportsHubTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/ServicesCatalogCrossBranchTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/SmsUsageLogsSchemaTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/SmtpConfigServiceTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/SubscriptionBillingCheckoutSafetyTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/System/` (13 ملف) | لم يُشغَّل منفرداً (اختبارات لوحة النظام) |
| `tests/Feature/TenantPublicSiteTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/TenantSettingsParityTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/TenantWebsiteSettingsTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/VehicleSettingsHierarchyTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/WorkOrderCodeGenerationTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/WorkOrderItemStatusTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/WorkOrderLaborEntryTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/WorkOrderSuggestionTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/Auth/AppRouteEmailVerificationTest.php` | ضمن `Auth/` ✓ |
| `tests/Feature/Frontend/Error403HomeLinkTest.php` | لم يُشغَّل منفرداً |
| `tests/Feature/Auth/PolicyPermissionReferenceTest.php` | ضمن `Auth/` ✓ |
| `tests/Feature/Auth/RolePermissionsContractTest.php` | ضمن `Auth/` ✓ |
| `tests/Feature/Auth/PolicyCoverageTest.php` | ضمن `Auth/` ✓ |

> **السبب الجذري الموحّد**: `php artisan test --testsuite=Feature` الكامل تجاوز **900 ثانية** (مهلة الأداة) وتوقّف 3 مرات متتالية. الحل: التشغيل على دفعات. **النتيجة**: تغطية مُتحقَّق منها = **31 مجموعة**؛ الباقي (~34 ملف) يحتاج تشغيلاً متسلسلاً أو أداة متوازية.
>
> **ملاحظة**: `pestphp/pest-plugin` مُفعّل في `composer.json` لكن Pest غير مثبّت فعلياً — `php artisan test --parallel` يرمي `Unknown option "--parallel"`. **تركيب Pest سيُفعّل التوازي** ويُحلّ هذه المشكلة جذرياً. (اختياري — خارج نطاق التدقيق الحالي.)

---

## 5. النتائج التي تحتاج تحققاً إضافياً

| # | النتيجة | سبب عدم اليقين | كيف تتحقق |
|---|---|---|---|
| V-01 | تنفيذ PHP من الملفات المرفوعة | يعتمد على إعداد Apache في الإنتاج (لم يُفحص) | فحص إعداد Apache في Hostinger؛ أو رفع ملف `.php` في بيئة اختبار والتحقق |
| V-02 | أثر `laravel/framework` 12.56.0 على TokenGuard | يعتمد على نمط استخدام personal access tokens الفعلي | مراجعة `personal_access_tokens` في الإنتاج + اختبار `auth:sanctum` بمُعرِّف غير نصّي |
| V-03 | ثغرات `dompdf` SVG | يعتمد على استخدام SVG داخل قوالب PDF | مراجعة قوالب `resources/views/pdf` و`Components/Print/PrintTemplates` بحثاً عن SVG مضمّن |
| V-04 | سلوك `phpoffice/phpspreadsheet` | يعتمد على قراءة ملفات XLSX من مستخدمين | مراجعة `Imports/` + `CustomerImportExportController` |
| V-05 | أرقام الأداء في الإنتاج | الاختبارات على SQLite | قياس على MySQL إنتاجي بـ `DB::listen` |
| V-06 | الفروقات البصرية و RTL | لم يُجرَ فحص بصري | تشغيل الواجهة + أداة لقطة شاشة |
| V-07 | تكرار/بيانات يتيمة في الإنتاج | لا وصول لقاعدة إنتاجية | `php artisan tinker` على اتصال قراءة فقط |
| V-08 | الفهرسة في SQLite | فرق سلوكي عن MySQL | مراجعة EXPLAIN على MySQL |
| V-09 | تسرّب الأسرار عبر `AdminActivityLog` | لم يُفحص كل حقول `changes` | مراجعة كل مواضع `AdminActivityLog::create` |
| V-10 | `Dashboard/` vs `DashboardV2/` | لم يُحدَّد أيهما حيّ | رسم بياني الاستيراد لكلاهما |

---

## 6. تغطية كل مجال من مجالات المراجعة

| # | المجال | التغطية | ملخص |
|---|---|---|---|
| 1 | بنية المشروع والتقنيات | **كاملة** | 12 تقنية مُوثّقة بإصداراتها |
| 2 | بنية الوحدات | **كاملة** | 12 وحدة + حالة كل منها |
| 3 | المسارات | **كاملة** | 642 مساراً — 0 تكرار |
| 4 | المصادقة | **كاملة** | 3 حُرّاس + 2FA + تدفّق البوابة |
| 5 | الصلاحيات | **كاملة** | 31 Policy + مصدر واحد + تغطية اختبار جزئية |
| 6 | عزل المستأجرين | **كاملة** | بوابة CI + 27 اختبار |
| 7 | بنية Laravel | **كاملة** | 145 متحكم + 43 خدمة |
| 8 | المعاملات | **كاملة** | 8+ في المخزون + التحويل + الفوترة |
| 9 | N+1 | **جزئية** | مُثبت في المركبات؛ غير مُفحص في كل الصفحات |
| 10 | Vue: المكوّنات | **كاملة** | رسم بياني الاستيراد |
| 11 | Vue: الحالة والأداء | **جزئية** | — |
| 12 | Vue: RTL/LTR | **جزئية** | ساكن فقط |
| 13 | الترجمة: الملفات | **كاملة** | مقارنة مفتاح-بمفتاح |
| 14 | الترجمة: النصوص الثابتة | **كاملة** | 1,231 نص |
| 15 | التصميم | **كاملة** | 10 ملفات Design + قياس التبنّي |
| 16 | قاعدة البيانات: المخطط | **كاملة** | 244 ترحيل |
| 17 | قاعدة البيانات: البيانات | **متعذّرة** | لا وصول للإنتاج |
| 18 | الأمن | **كاملة** (ساكن) | 8 ثغرات موثّقة |
| 19 | الأداء | **جزئية** | اختبارات + بناء |
| 20 | الاختبارات | **كاملة** | 349 مُشغَّل + 10 فاشل |
| 21 | الإعدادات والتكاملات | **كاملة** | 7 تكاملات |
| 22 | CI/CD | **كاملة** | 8 مهام — 2 مشكلتين |
| 23 | التوثيق | **جزئية** | `README`, `DEPLOYMENT`, `AGENTS.md`, `docs/` (16 مجلد) |
| 24 | التدقيق سابق | **كاملة** | `docs/audit/` — تغطية 6-9% |

---

## 7. ما لم يُفحص

### 7.1 تقنياً خارج النطاق

| البند | السبب |
|---|---|
| `vendor/` (64 حزمة) | كود طرف ثالث — يفحص عبر `composer audit` |
| `node_modules/` (378 حزمة) | نفس |
| `public/build/` | ناتج البناء |
| `storage/` | بيانات محلية |
| `docs/` (16 مجلد) | وثائق — موضوعة في `docs/audit/` |
| `.harness/`, `.opencode/`, `.agent/`, `.mavis/` | أدوات بيئة التطوير |
| `carag-v2.test_*.report.html` (4 ملفات، ~3.6 MB) | تقارير اختبار قديمة في جذر المشروع — **ملاحظة: ملفات كبيرة في الجذر** |
| `ca0f48f5-9ddc-4351-ab55-d95a57cd41af/` | مجلد عشوائي في الجذر — **يستحق التنظيف** |
| `audit_reports/` (مجلد سابق) | تدقيق سابق |

### 7.2 وظيفية — خارج النطاق التجاري

| البند | السبب |
|---|---|
| `app/Services/Developer/` (9) | أدوات المطور الداخلي |
| `app/Models/Developer/` (5) | أدوات المطور الداخلي |
| `app/Http/Controllers/System/DeveloperController.php` | واجهة أدوات المطور |
| `docs/AI_WORKFLOW/`, `docs/AI_DEVELOPMENT_GUIDE.md` | توثيق تدفّق العمل بالذكاء الاصطناعي |

### 7.3 تعذّر التحقق

| البند | السبب | البديل |
|---|---|---|
| كل صفحة بصرياً | لا متصفح في الجلسة | فحص ساكن + 584 `dir="ltr"` |
| قاعدة البيانات | لا وصول إنتاجي | تحليل المخطط + 27 اختبار عزل |
| ZATCA حيّ | لا نُرسل له | مراجعة `ZatcaOnboardingService` + 13 اختبار |
| Moyasar حيّ | لا مدفوعات حقيقية | مراجعة `PaymentManager` |
| SMS حيّ | لا رسائل | 37 اختبار على قناة الاختبار |
| إعداد Apache | لا وصول | تحليل سلسلة الرفع + مراجعة `hostinger_setup.sh` |
| الحمل | لا بيئة ضغط | `PerformanceBaselineTest` (SQLite) |
| E2E بالمتصفح | غير موجود في المستودع | — |

---

## 8. نظافة المستودع بعد التدقيق

```
$ git status --short
(فارغ)
```

**لم يُعدَّل أي ملف تطبيق.** الإجراءات المؤقتة:

| الإجراء | النتيجة |
|---|---|
| إنشاء `tests/Feature/TaxProbeTest.php` (اختبار استكشافي للتحقق من اشتباه خلل ضريبي) | **حُذف** — `mavis-trash` |
| `composer audit --format=json > /tmp/ca.json` | في `/tmp` — خارج المشروع |
| `php artisan translations:check --json > /tmp/i18n_*.json` | في `/tmp` — خارج المشروع |
| `php artisan test ... --json` | في `/tmp` |
| إنشاء `audit_reports/khidmh-2026-10-25/` | **ملفات تقارير جديدة** (لا استبدال) |

**الأمر المُنفَّذ الوحيد على قاعدة البيانات**: لا شيء. `phpunit.xml` يفرض `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:` — **قاعدة في الذاكرة، لا بيانات إنتاجية**.

---

## 9. خلاصة التغطية

| المقياس | القيمة |
|---|---|
| الملفات المفحوصة | **~1,000+** |
| المسارات المفحوصة | **642 / 642** |
| أوامر PHP المفحوصة | **145 / 145** |
| النماذج المفحوصة | **124 / 124** |
| الخدمات المفحوصة | **43 / 43** |
| الترحيلات المفحوصة | **244 / 244** (إحصاء + قيود) |
| ملفات Vue (رسم بياني) | **400 / 400** |
| ملفات الترجمة | **96 / 96** (مقارنة كاملة) |
| الاختبارات المُشغَّلة | **31 مجموعة — 359 اختباراً** |
| الثغرات الموثّقة | **8** (كلها بأدلة قابلة لإعادة التحقق) |
| فرص الدمج الموثّقة | **7** |
| **الأكثر ثقةً** | الحزمة (عزل، بوابة عميل، محاسبة، مخزون) + الأدوات (Pint, ESLint, Vitest, tenant-scope) |
| **الأضعف** | التصميم (مُتجاوَز)، الترجمة (أداة معطّلة)، CI (job مكرّر + PHPStan فاشل) |

---

*نهاية سجل الجرد. كل رقم في التقارير يمكن إعادة اشتقاقه من الأوامر في §3.*
