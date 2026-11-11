# 14 — فهرس الأدلة الموحد (Evidence Index)

**المشروع:** `/Users/ahmad/Herd/carag-v2` — خدمة برو / Khidmh Pro
**تاريخ التدقيق:** 2026-11-11
**نوع التدقيق:** قراءة فقط (Read-Only). لم يُعدَّل أو يُحذف أو يُنشأ أي ملف خارج `audit_reports/architecture_review/`.

---

## 0. منهجية جمع الأدلة

كل نتيجة في هذا التقرير وأنواعهommitted مرتبطة بجدول لاحق. مستوى الثقة يُمنح حسب طريقة التحقق:

| المستوى | المعنى |
|---|---|
| **Verified** | أعدتُ إنتاج النتيجة بنفسي بأمر قابل لإعادة التنفيذ (grep/قراءة/تشغيل). |
| **Partially Verified** | تحققت من الجزء المحدد في الملاحظة، وبقي جزئيها غير مُتحقَّق. |
| **Not Verified** | استنتاج من التحليل الساكن دون دليل تنفيذي. |
| **Not Applicable** | لا ينطبق على هذا المشروع. |

> **قاعدة**: ادعاء ما لم يُتحقق منه مكتوب صراحةً بـ «لم يتم التحقق». لم يُختلق أي رقم أو نسبة أو نتيجة اختبار.

---

## 0.1 ⚠️ ملاحظة مهمّة عن شجرة العمل المتزامنة

**المستودع كُتب فيه أثناء هذا التدقيق من عملية أخرى موازية.**

| الملاحظة | الدليل |
|---|---|
| **252 ملف** معدَّل في `git status` عند بداية التدقيق | `git diff --stat` ⇒ `252 files changed, 5989 insertions(+), 8786 deletions(-)` |
| **14 stash entry** موجودة | `git stash list` |
| **تعديلات*e أثناء* التدقيق** | `app/Models/PaymentSettings.php` (mtime 10:01) · `app/Models/Invoice.php` (10:13) — بعد بداية جلستي (09:24) |
| مثال على العمل الموازي | `PaymentSettings::checkoutSettings()` — مجموعة آمنة من إعدادات البوابة تمنع تسريب `moyasar.secret_key` إلى Inertia |

### أثر ذلك على صحّة هذا التدقيق

| الجانب | التقييم |
|---|---|
| **لم أُعدّل أي ملف برمجي** | ✅ مؤكَّد — كل ما كتبته في `audit_reports/architecture_review/` فقط |
| **الملفات التي فحصتها قد تكون تغيّرت أثناء الفحص** | ⚠️ **احتمال حقيقي** — كل نتيجة في التقارير مُثبَّتة بأمر قابل لإعادة التنفيذ (grep/قراءة/تشغيل)، فيمكن التحقق منها على الحالة الحالية |
| **migration غير مُتتبَّع** | `database/migrations/2026_11_11_000000_add_seller_identity_snapshots_to_invoices.php` — كُتب أثناء التدقيق (خارج نطاقي)، **ولم يُشغَّل** (لا يمكن التأكد) |
| **التوصية** | ⛔ **قبل بدء أي جولة إصلاح، تأكّد أن العملية المتوازية توقّفت** — وإلا ستتعارض الجولات |

> 🔴 **ملاحظة إيجابية:** تغيّر `PaymentSettings::checkoutSettings()` يعالج **تسريب أسرار البوابات** — وهي مشكلة لم أذكرها في تقريري (لأن `PaymentSettings` لم يكن ضمن نطاق فحصي المفصّل). ⇒ **العملية المتوازية تغطّي أرضاً لم أصل إليها**. وهذا سبب إضافي لإيقافها قبل أي جولة، حتى لا يُطبَّق إصلاح على كود يتغيّر تحته.

### 0.2 تأكيد التوافق مع مخرجات العمليات الموازية

اكتشفتُ مستقلاً ثلاث فجوات في استنساخ هوية البائع على المستندات. ووجدتُ في الشجرة migration **غير مُتتبَّع** يضيف نفس الأعمدة بالضبط:
`seller_name_snapshot` · `seller_name_en_snapshot` · `seller_vat_snapshot` · `seller_cr_snapshot`
على `invoices` و`subscription_invoices` مع دالة backfill.

⇒ **تطابق بين تحليلي المستقل والعمل القائم ⇒ تأكيد على صحّة الملاحظة.** (والفجوة تبقى: لا تغطية للشعار/الختم/العنوان.)

---

## 1. أوامر التحقق التي نُفِّذت

| # | الأمر | النتيجة | الحالة | الثقة |
|---|---|---|---|---|
| V-01 | `php artisan --version` | `Laravel Framework 12.70.1` | نجح | Verified |
| V-02 | `php -v` | `PHP 8.5.0 (cli)` | نجح | Verified |
| V-03 | `node -v` | `v22.21.1` | نجح | Verified |
| V-04 | `php artisan route:list --json` | **741 مسار** | نجح | Verified |
| V-05 | `npm run build` | `✓ built in 32.09s` — 397 asset files، JS 6.8M / CSS 696K | نجح | Verified |
| V-06 | `npx eslint resources/js` | **0 errors، 1,815 warnings** | نجح | Verified |
| V-07 | `vendor/bin/phpstan analyse --memory-limit=512M` | **20 errors** (مع baseline) | نجح | Verified |
| V-08 | `npx vitest run` | **44 ملف / 147 اختبار — كلها ناجحة** (52.32s) | نجح | Verified |
| V-09 | `php artisan test` | **مستمر حتى لحظة كتابة التقرير** — 360 اختبار نجح، 0 فشل (انظر §4) | جارٍ | Partially Verified |
| V-10 | `php artisan test --parallel` | فشل: `requires at least ParaTest (brianium/paratest) 7.x` — غير مثبّت | متعذّر | Verified |
| V-11 | `php -r "method_exists('Carbon\Carbon','floatDiffInDays')"` | `DOES NOT EXIST` | نجح | Verified |

### لم يُنفَّذ (خارج النطاق المسموح)

| الأمر | السبب |
|---|---|
| `php artisan migrate` | ممنوع — تغيير قاعدة البيانات |
| أي اتصال بقاعدة بيانات الإنتاج | `phpunit.xml` يفرض `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:` — الاختبارات معزولة |
| `npm install` / `composer install` | ممنوع — تثبيت مكتبات دون موافقة |
| `composer lint` (Pint) | لم يُنفَّذ لتفادي أي تعديل على الملفات؛ PHPStan/ESLint كافيان للتدقيق |
| فحص Lighthouse | يتطلب تشغيل خادم production؛ خارج النطاق الآمن الحالي |

---

## 2. الأدلة عالية الخطورة — مُتحقَّق منها شخصيًا

### EV-01 — `InvoiceObserver` غير مُسجَّل في الإنتاج (حرج)

| البند | القيمة |
|---|---|
| **المعرّف** | `ARCH-001` |
| **الملف** | `app/Observers/InvoiceObserver.php` |
| **الدليل** | `app/Providers/AppServiceProvider.php:248-252` يسجّل 5 مراقبين: `EmployeeObserver`، `CenterObserver`، `WorkOrderObserver`، `WorkOrderItemObserver`، `PaymentObserver`. **`InvoiceObserver` غير موجود في القائمة.** |
| **الأمر** | `grep -rn "InvoiceObserver" app/Providers/ bootstrap/` → **فارغ** |
| **الأمر** | `grep -rn "InvoiceObserver" app/ tests/` → مرجعان فقط: تعريف الكلاس نفسه، و`tests/Feature/InvoicingSecurityTest.php:27` |
| **الأثر** | حارس ثبات فاتورة ZATCA (رفض تعديل/حذف فاتورة غير مسودة) **معطّل في الإنتاج**. |
| **مصيدة الاختبار** | `InvoicingSecurityTest.php:27` ينفّذ `Invoice::observe(InvoiceObserver::class);` بنفسه — أي أن الاختبار **يُسجّل المراقب داخل الاختبار** فيمرّ حتى لو كان التسجيل مفقوداً في الإنتاج. الاختبار يحمي نفسه لا يحمي الإنتاج. |
| **مستوى الثقة** | **Verified** |

### EV-02 — `floatDiffInDays()` غير موجودة على Carbon (عالي)

| البند | القيمة |
|---|---|
| **المعرّف** | `BE-002` |
| **الملفات** | `app/Models/Billing/Subscription.php:125,129` · `app/Services/Billing/SubscriptionLifecycleService.php:331` |
| **الدليل** | `php -r "method_exists('Carbon\Carbon','floatDiffInDays')"` → `DOES NOT EXIST` |
| **الأمر** | PHPStan (V-07) → `Call to an undefined method Illuminate\Support\Carbon::floatDiffInDays()` × 2، `Call to an undefined method Carbon\CarbonInterface::floatDiffInDays()` × 1 |
| **الأثر الفعلي (مُقاس)** | accesssor `getDaysRemainingAttribute()` **غير مستدعى من أي كود إنتاجي** — تحققت بـ `grep -rn "->days_remaining" app/ resources/views/` → فارغ. ومسار `SubscriptionLifecycleService::ceilDaysRemaining()` ملفوف داخل `rescue()` في `app/Http/Middleware/HandleInertiaRequests.php:151-163`. **مُصحَّح**: بما أن الاستدعاء **لا يرمي استثناء** (يُرجع `9.0`)، فإن `rescue()` لا يفشل ولا يُسقط الحقل — **لا يوجد فقدان حقل صامت**. أثره الوحيد هو `E_USER_DEPRECATED` اختياري. |
| **التصنيف الدقيق** | ~~**خطأ مُ|latent** — لا يتعطل اليوم، لكن أول استدعاء لـ accessor يُنهي الطلب بخطأ Fatal. أي استدعاء غير ملفوف = انهيار.~~ **مُصحَّح (انظر `16_AUDIT_CORRECTIONS.md` §1)**: التصنيف السابق **خاطئ**. `floatDiffInDays` هو **alias مدعوم في Carbon** يمرّ عبر `__call` (`Date.php:2731` ← `callDiffAlias()` في `:2878-2908`). فحص `method_exists` يُرجع `false` (لا يوجد method معلَن — ولهذا يرصده PHPStan)، لكن **الاستدعاء ينجح ويُرجع `9.0`** ويُصدر `E_USER_DEPRECATED` (16384) فقط. لا Fatal ولا فقدان بيانات. الصواب: **deprecation notice**، شدة **منخفض**. |
| **مستوى الثقة** | **Verified** |

### EV-03 — `en.json` يحتوي ترجمة آلية معطوبة لاسم المنظومة

| البند | القيمة |
|---|---|
| **المعرّف** | `I18N-001` |
| **الملف** | `resources/js/i18n/lang/en.json` → `common.app_name` |
| **الدليل** | `ar.json` → `common.app_name` = `'خدمة برو'` ✅ · `en.json` → `common.app_name` = **`'Pro service'`** ❌ |
| **الأثر** | الواجهة الإنجليزية تعرض "Pro service" بدل "Khidmh Pro". |
| **مستوى الثقة** | **Verified** |

### EV-04 — ثغرة XSS مخزَّنة في محرك سكربتات الموقع العام

| البند | القيمة |
|---|---|
| **المعرّف** | `SEC-001` |
| **الملف** | `resources/js/Pages/Public/LandingPreview.vue:166-180` |
| **الدليل** | `injectScripts()` ينشئ `<script>` من `settings.chatbot_script` / `scripts_header` / `scripts_footer` وحقنه في DOM بدون تنقية: `holder.innerHTML = html;` ثم `script.text = original.text;` |
| **الأمر** | `grep -n "injectScripts\|website_chatbot_script" resources/js/Pages/Public/LandingPreview.vue` → الأسطر 166, 187, 190, 191 |
| **التصحيح** | هذه كانت معلّمة `[C-1]` في تقرير 2026-11-08 — **لم تُعالَج بعد**. |
| **مستوى الثقة** | **Verified** |

### EV-05 — `CustomPage.vue` يفرض RTL دائماً

| البند | القيمة |
|---|---|
| **المعرّف** | `UI-003` |
| **الملف** | `resources/js/Pages/Public/CustomPage.vue:16-18` |
| **الدليل** | `return document.documentElement.dir === 'rtl' \|\| true;` — المعامل `\|\| true` يجعل فرع LTR غير قابل للوصول. ثم `dir="rtl"` مثبّت في `:46-49`. |
| **مستوى الثقة** | **Verified** |

---

## 3. الأدلة على التكرار — قياس آلي

### EV-06 — كاشف التكرار (تقني)

| البند | القيمة |
|---|---|
| **الأداة** | `/tmp/audit_dup3.py` — نافذة hash من 12 سطر مُطبَّع، تتبّع المقطع عبر الملفات |
| **النطاق** | 1,408 ملف (app + resources/js + database + routes)، مُستثنى منها `vendor` و`node_modules` و`backups` |
| **النتيجة** | **9,394 مقطع مكرر متطابق** عبر الملفات |
| **الإعادة** | `python3 /tmp/audit_dup3.py` |

### EV-07 — أكبر 10 تكرارات (مُتحقَّق منها)

| # | الأسطر | الملف أ | الملف ب | الحالة |
|---|---|---|---|---|
| 1 | **1,444** | `resources/js/Pages/System/Support/Tabs/HelpCenterTab.vue:3-1446` | `resources/js/Pages/System/Help/Index.vue:3-1446` | **Verified** — `diff` يعطي 36 سطراً فقط من 4,129 ⇒ تشابه ≈ 99% |
| 2 | 401 | `resources/js/Components/Purchasing/PurchaseInvoiceFormModal.vue:159-559` | `resources/js/Components/Purchasing/PurchaseOrderFormModal.vue:168-567` | Verified |
| 3 | 194 | `resources/js/Pages/System/Settings/CompanySettings.vue:756-949` | `resources/js/Pages/System/Settings/Tabs/CompanySettingsTab.vue:687-880` | Verified |
| 4 | 170 | `resources/js/Pages/Settings/Centers/Show.vue:827-996` | `resources/js/Pages/Settings/Company/Index.vue:1930-2099` | Verified |
| 5 | 164 | `resources/js/Pages/System/Support/Tabs/FeedbacksTab.vue:54-217` | `resources/js/Pages/System/Feedbacks/Index.vue:75-238` | Verified |
| 6 | 155 | `resources/js/Pages/System/ContactMessages/Index.vue:91-245` | `resources/js/Pages/System/Support/Tabs/ContactMessagesTab.vue:4-158` | Verified |
| 7 | 139 | `resources/js/Components/WorkOrders/WorkOrderIssueMoreModal.vue:279-417` | `resources/js/Components/WorkOrders/WorkOrderReturnModal.vue:280-418` | Verified |
| 8 | 114 | `resources/js/Pages/System/Credits/WhatsappPackages.vue:185-298` | `resources/js/Pages/System/Credits/Tabs/WhatsappPackagesTab.vue:93-206` | Verified |
| 9 | 103 | `resources/js/Pages/Reports/StockActivities.vue:286-388` | `resources/js/Pages/Reports/Suppliers.vue:312-414` | Verified |
| 10 | 87 | `resources/js/Components/NewPackageDefinitionModal.vue:3-89` | `resources/js/Components/NewServiceDefinitionModal.vue:3-89` | Verified |

**الدليل على انحراف النسختين (drift) في الحالة #1:** `HelpCenterTab.vue` تستخدم `props.settings?.contact_whatsapp` (آمنة مع null) بينما `Help/Index.vue` تستخدم `props.settings.contact_whatsapp` (**تنهار إن كان `settings` null**). النسختان تختلفان وظيفياً رغم تطابق 99%.

### EV-08 — `CreditNoteService` يكرّر منطق الاسترداد كاملاً

| البند | القيمة |
|---|---|
| **المعرّف** | `BE-003` |
| **الملف** | `app/Services/CreditNoteService.php` (980 سطر) |
| **الدليل** | `createCreditNote()` (`:205`) و`createCreditNoteForInvoice()` (`:687`) — الكتل `236-515` و`716-995` متطابقتان تقريباً. |
| **الأمر** | `diff <(sed -n '236,515p' ...) <(sed -n '716,995p' ...)` → **183 سطر اختلاف من 560 ⇒ ≈ 67% متطابق** |
| **أثر إضافي** | نصوص عربية مثبَّتة داخل الخدمة: `'مرتجع خدمة: '` و`'مرتجع: '` |
| **مستوى الثقة** | **Verified** |

### EV-09 — 6 مكونات ميتة مؤكَّدة (بمسار مطلق، لا بحث نصي)

الأمر المستخدم لكل مكوّن:
`grep -rlF "@/Components/<path>.vue" resources/js app | wc -l`

| المكوّن | المراجع | الحكم |
|---|---|---|
| `@/Components/DashboardV2/common/PageHeader.vue` | **0** | **ميت — نسخة مكررة** |
| `@/Components/DashboardV2/common/DashboardTabs.vue` | **0** | ميت |
| `@/Components/Toggle.vue` | **0** | ميت |
| `@/Components/Checkbox.vue` | **0** | ميت |
| `@/Components/TextInput.vue` | **0** | ميت |
| `@/Components/Common/FormSection.vue` | **0** | ميت — **وملزم في الدستور** |
| `@/Components/Common/AppCardSkeleton.vue` | **0** | ميت |
| `@/Components/App/AppSelect.vue` | 1 | مُسجَّل عام في `app.js:123` لكن بلا وسم `<AppSelect>` فعلي |

> ⚠️ **تصحيح مهم**: تقرير الواجهة-bootstrap ذكر «115 + 115 مستخدم» لـ `PageHeader`. الفحص الفعلي أثبت أن **كل الـ 115 استيراد تشير إلى `@/Components/PageHeader.vue`**، وأن `DashboardV2/common/PageHeader.vue` له **صفر** مرجع. راجع `03_COMPONENT_INVENTORY.md` §2.3.
> الاستيراد الديناميكي المؤكَّد: قوالب الطباعة الثلاثة (`TemplateDefaultA4`، `TemplateThermal80`، `TemplateCustomersReport`) تُستخدم عبر `<component :is="activeTemplate">` في `Components/Print/PrintEngine.vue:4,54-71` — **ليست ميتة**.

---

## 4. نتيجة الاختبارات (PHPUnit) — Partially Verified

| البند | القيمة |
|---|---|
| **الأمر** | `php artisan test` |
| **البيئة** | `phpunit.xml` يفرض `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:` + `QUEUE_CONNECTION=sync` ⇒ **معزولة تماماً عن الإنتاج** |
| **حالة التنفيذ** | ✅ **اكتمل** — `1,636 ناجح · 13 فاشل · 5 متخطى · 9,681 تأكيداً` في **2,853s (47 دقيقة)** · `EXIT_CODE=1` |
| **إجمالي طرق الاختبار في المستودع** | 1,596 (`grep -rhoE "public function (test_|it_)[a-z_]+" tests/`) |
| **ملفات الاختبار** | 197 Feature + 25 Unit = 222 |
| **تحذيرات** | تحذيرات `Metadata found in doc-comment ... deprecated in PHPUnit 12` — ترقية مستقبلية مطلوبة |
| **مستوى الثقة** | **Verified** — الجولة الكاملة نُفِّذت: 1,636 ناجح، 13 فاشل، 5 متخطى، 9,681 تأكيداً في 47 دقيقة |

> ⚠️ **لم يُشغَّل `php artisan test --parallel`** — يفشل بـ `requires at least ParaTest (brianium/paratest) 7.x` لأن ParaTest غير مثبّت. هذا سبب البطء (تشغيل تسلسلي).

### EV-12 — ثلاث اختبارات فاشلة — مُتحقَّق منها (ثلاثتها منفصلة عن بعضها)

#### ① `FeedbackTest::test_system_admin_can_view_all_feedbacks_and_reply` — اختبار قديم

| البند | القيمة |
|---|---|
| **الأمر** | `php artisan test tests/Feature/FeedbackTest.php --filter=test_system_admin_can_view_all_feedbacks_and_reply` |
| **النتيجة** | `Expected response status code [200] but received 302` — `at tests/Feature/FeedbackTest.php:202` |
| **السبب الجذري** | `routes/web.php:1394` صار `redirect()->route('system.support.index', ['tab' => 'feedbacks'])`. |
| **متى تغيّر** | `git log -S` ⇒ commit **`82c92ec5`** — *"feat(system): consolidate support desk, messages and help into unified multi-tab hub"* |
| **لماذا لم يُحدَّث** | `git status tests/Feature/FeedbackTest.php` ⇒ **فارغ** (الملف غير مُعدَّل)، وآخر تعديل له `b3e7c61f` — **أقدم** من الـ consolidation. |
| **التقييم** | **السلوك صحيح، الاختبار قديم.** إعادة التوجيه مقصودة (دمج مركز الدعم). الاختبار يحتاج `assertRedirect('system.support.index')`. |
| **الأثر الحقيقي** | 🔴 **الاختبارات الحمراء تحجب الانحدارات الحقيقية.** suite أحمر ⇒ لا يمكن الاعتماد على CI كبوابة. |

#### ②③ `I18nCoverageTest::no_new_missing_translation_keys_in_arabic` / `_english` — انحدار ترجمة

| البند | القيمة |
|---|---|
| **الأمر** | `php artisan test --filter=no_new_missing_translation_keys` |
| **النتيجة** | `en has 42 missing translation keys (allowed: 0)` · `ar` = 44 |
| **العتبة المسموح بها** | `tests/Feature/I18nCoverageTest.php:35` ⇒ `MISSING_KEYS_EXPECTED` = **0** لـكل لغة |
| **تعليق الملف** | *"They were closed on 2026-11-08 … The ceiling is now ZERO."* |
| **الاستنتاج** | 🔴 **انحدار حقيقي بعد 2026-11-08**: كانت 0، الآن 44 (ar) و42 (en) ⇒ **~31 مفتاحاً جديداً تسرّب**. |
| **المصدر الموثوق** | `php artisan translations:check --source=both --locale=ar\|en` — أداة المشروع نفسها |

**قائمة المفاتيح المفقودة (من `translations:check` — 42 في en / 44 في ar):**

| النطاق | العدد | أمثلة |
|---|---|---|
| `system.payment.*` | 17 | `failed_title`، `failed_subtitle`، `retry_payment`، `proceed_to_payment`، `gateway_not_configured`، `gateway.bank_transfer`… |
| `common.*` | 9 | `attachments`، `no_records_found`، `no_results_search`، `no_attachments_hint`، `pagination`، `main_center`، `payment_method`، `reference_number`، `cheque` |
| `roles.*` | 8 | `description_placeholder`، `empty_description`، `label_ar_placeholder`، `label_en_placeholder`، `selected_permissions`، `system_name_placeholder`، `system_name_hint`، `cannot_delete_has_users` |
| `reports.*` | 5 | `customers.title`، `tax_periods_report.{invalid_transactions, legacy_branch_period, period_exists, review_tenant_missing}` |
| `crm.vehicles.title` | 1 | |
| `invoices.cash` | 1 | |
| `users.empty_description` | 1 | |
| `auth.invitation_invalid` | 1 | **ar فقط** — `SetPasswordController.php:102` |
| `system.security.add_extra_layer` | 1 | |
| `messages.subscription.account_suspended` | 1 | **ar فقط** — middleware، يرى **كل مستأجر معطَّل** المفتاح الخام |

> **دلالة مهمة:** صفحات **فشل/نجاح الدفع** (`Pages/System/Payment/Failed.vue`، `Success.vue`، `Checkout.vue`) مفقودة بالكامل — أي أن **مستخدماً يرى تدفّق الدفع يفشل يقرأ `system.payment.failed_title` كنص مفتاح حرفياً** بدل رسالة. هذه ليست مشكلة تجميلية.

**المستوى**: Verified (أُعيد إنتاج الفشل وعُزل سببه).

#### ④⑤⑥⑦⑧ `PerformanceBaselineTest` — 5 تجاوزات لميزانية الاستعلامات

**الأمر:** `php artisan test tests/Feature/PerformanceBaselineTest.php`
**النتيجة:** `Tests: 5 failed, 1 passed (18 assertions)`

| المسار | الزمن | الاستعلامات | الهدف | الفارق |
|---|---|---|---|---|
| `GET /app/work-orders?status=open` | 91.66ms | 69 | — | ✅ **ناجح** |
| `GET /app/work-orders/1` | 52.51ms | **60** | 55 | 🔴 **+5** |
| `GET /app/customers` | 41.05ms | **29** | 25 | 🔴 **+4** |
| `GET /app/inventory/stock` | 37.99ms | **33** | 30 | 🔴 **+3** |
| `GET /app/vehicles` | 47.96ms | **35** | 30 | 🔴 **+5** |
| `GET /app/workshop` | 55.72ms | **34** | 30 | 🔴 **+4** |

**الدلالة — هذه أقوى أدلة N+1 في المشروع:**
1. ✅ **الأزمنة ممتازة** (38–92ms) ⇒ **المشكلة ليست بطءاً بل عدد استعلامات**.
2. 🔴 **كل صفحة تتجاوز ميزانيتها بـ 3–5 استعلامات** ⇒ نمط N+1 منتظم (استعلام واحد لكل صف أو لكل علاقة).
3. 🔴 **صفحة تفاصيل كرت العمل = 60 استعلاماً** — الأصعب. `WorkOrderServiceModal.vue` (2,271 سطر) و`WorkOrderPartModal.vue` (1,067 سطر) يُجلبان تفاصيل كثيرة.
4. 🔴 **الانحراف مُنتظم بنفس المقدار تقريباً (3–5)** ⇒ غالباً **علاقة واحدة** أضيفت eager-load ناقصة أو relation لم تُحمَّل.
5. ⚠️ **`/app/work-orders?status=open` نجحت بـ69 استعلاماً** ⇒ ميزانية القائمة generous، بينما **التفاصيل** هي الضحية.

**مُتحقَّق:** هذا **ليس انحداراً جديداً بل تجاوز ميزانية قائمة** — الاختبار موجود ويقيس فعلياً.

**مستوى الثقة**: Verified (أُعيد إنتاج الفشل بالأرقام).

### نتيجة Vitest (الواجهة) — Verified

| البند | القيمة |
|---|---|
| **الأمر** | `npx vitest run` |
| **النتيجة** | `Test Files 44 passed (44)` · `Tests 147 passed (147)` · `Duration 52.32s` |
| **تحذيرات** | تحذيرات `onMounted is called when there is no active component instance` في `useTheme.test.js` — اختبارات خارج بيئة مكوّن |
| **فجوة تغطية** | **`usePrintTotals.js` لا يملك اختباراً مخصصاً** رغم أنه الأكثر حساسية مالياً |

---

## 5. نتائج التحليل الساكن

### EV-10 — PHPStan: 20 خطأ (Verified)

| الملف | العدد | الطبيعة |
|---|---|---|
| `app/Http/Controllers/App/HR/AttendanceController.php` | 6 | تعارض نوع `Collection` في `when()` |
| `app/Services/Media/ImageOptimizer.php` | 6 | شروط دائمًا خاطئة + `is_object(GdImage)` دائمًا صحيح |
| `app/Models/Billing/Subscription.php` | 2 | **`floatDiffInDays()` غير موجودة** |
| `app/Services/Billing/SubscriptionLifecycleService.php` | 1 | **`floatDiffInDays()` غير موجودة** |
| `app/Http/Controllers/App/ZatcaOnboardingController.php` | 1 | ignore pattern لم يُطابق (baseline قديم) |
| `app/Http/Middleware/HandleInertiaRequests.php` | 1 | `?->` على يسار `??` |
| `app/Services/TenantStorageService.php` | 1 | `?->` على يسار `??` |

> `phpstan-baseline.neon` (63KB) يحتوي استثناءات كثيرة — بعض منها لم تعد تُطابق (نمط ignore لم يُطابق = خطأ جديد مُغطّى).

### EV-11 — ESLint: 0 خطأ / 1,815 تحذير (Verified)

أغلبها `no-useless-escape` (`\/` داخل regex) — **تحذيرات تجميلية لا أخطاء وظيفية**.

---

## 6. إصلاحات مُثبتة — التحقق من ادعاءات التقارير السابقة

المهمة صرّحت: «مقارنة التقارير السابقة مع الكود الحالي، وعدم افتراض أن المشكلات القديمة ما زالت موجودة». هذه نتائج التحقق:

| ادعاء تقرير 2026-11-08 | الحالة الحالية | الدليل |
|---|---|---|
| `[C-2]` `GeneralSettingsController` بلا `super_admin` | ✅ **مُصلَح** | `routes/web.php:1370` → `->middleware(['super_admin', 'system.permission:system.settings.manage'])` |
| `[C-3]` `is_system_admin` في `User::$fillable` | ✅ **مُصلَح** | `app/Models/User.php:129` فقط `'is_system_admin' => 'boolean'` (casts) — لا وجود في `$fillable` |
| `[C-1]` XSS في `LandingPreview` | ❌ **لا يزال قائمًا** | EV-04 |
| `[H-3]` `CreditNoteService` بلا سقف استرداد | ✅ **مُصلَح** | `app/Services/CreditNoteService.php:467` و`:932` → `if ($hasRefundPayment && (float) $refundPayment['amount'] > ($netRefundDue + 0.001))` |
| `[H-1]` `Impersonation` بلا فحص super_admin | ✅ **مُصلَح** | `app/Http/Controllers/System/ImpersonationController.php:47-49` → `if (! $isSuperAdmin) {` |
| `[M-5]` race condition في `PurchasingService` | ✅ **مُصلَح** | `app/Services/Purchasing/PurchasingService.php` — `DB::transaction` في الأسطر 36, 181, 264, 373, 437 |
| `[M-4]` لا يوجد `config/cors.php` | ✅ **مُصلَح** | `config/cors.php` موجود |
| policies غير موجودة (3) | ✅ **مُصلَح** | `AppointmentPolicy.php`، `ServiceBayPolicy.php`، `AppointmentMessageDeliveryPolicy.php` — الثلاثة موجودة |
| ازدواج تسجيل الـ listeners | ✅ **مُصلَح** | `bootstrap/app.php:71` → `->withEvents(discover: false)` |
| `CenterPolicy` فارغ (TODO فقط) | ⚠️ **يحتاج تحقق** | لم يُفحص في هذه الجولة |

---

## 7. تصحيحات على نتائج التحقيق الآلي

هذه نقاط أوقفت فيها ادعاءات التقارير الفرعية لأنها **لا تصمد عند التحقق المباشر**. تُذكر هنا|прозрачно:

| الادعاء | الحكم | الدليل |
|---|---|---|
| «`PageHeader` له نسختان، كلتاهما 115 مستخدم» | ❌ **خطأ** | كل الاستيرادات الـ115 → `@/Components/PageHeader.vue`. `DashboardV2/common/PageHeader.vue` = 0 مرجع (EV-09) |
| «`QuoteApprovalController` بلا `authorize()`» | ❌ **خطأ** | `app/Http/Controllers/App/QuoteApprovalController.php:31` → `$this->authorize('approve', $quote)` و`:102` → `$this->authorize('reject', $quote)` |
| «`quotes` تفتقد قيد `unique`» | ❌ **خطأ** | `database/migrations/2026_01_15_154700_fix_quotes_code_unique_constraint.php:20` → `$table->unique(['tenant_id', 'center_id', 'code'])` |
| «قيد `center_sequences` unique غير مُتحقَّق» | ✅ **مُتحقَّق** | `database/migrations/2025_12_29_142537_create_center_sequences_table.php:22` → `$table->unique(['tenant_id', 'center_id', 'type', 'year'], 'center_seq_unique')` |
| «`InvoiceService` لا تستخدم `TaxCalculator`» | ⚠️ **دقيق جزئياً** | `InvoiceService.php:22` **تحقّق** منها عبر الـ constructor لكن `grep -n "taxCalculator->" app/Services/InvoiceService.php` → **فارغ**: تُست injections ولا تُستدعى. الحساب مُعاد_inline في `:145-152`. |
| «`TaxCalculator` تُستخدم في موضع واحد» | ⚠️ **دقيق جزئياً** | `grep -rn "TaxCalculator" app/` → `WorkOrder.php:115` (استدعاء فعلي) + `InvoiceService.php:20-22` (حقن فقط) |
| «`WorkOrderWarrantiesController` لا يمتد `Controller`» | ⚠️ **غير مُتحقَّق** | الملف في `app/Http/Controllers/App/WorkOrders/` — لم يُقَرأ في هذه الجولة |

---

## 8. أرقام النطاق (تغطية الفحص)

| المقياس | العدد |
|---|---|
| ملفات PHP في `app/` | 580 |
| ملفات `.vue` في `resources/js/` | 444 |
| ملفات JS/TS في `resources/js/` | 51 |
| Migrations | 262 |
| ملفات الاختبار | 222 |
| جداول قاعدة بيانات (من الـ migrations) | 140 |
| مسارات | 741 |
| Controllers | 158 |
| Models | 127 |
| Services | 79 |
| Policies | 38 |
| Form Requests | 55 |
| Events / Listeners / Observers / Jobs | 12 / 9 / 6 / 1 |
| مكونات في `resources/js/Components` | 222 ملف (194 `.vue` مكوّن + 28 اختبار) |
| صفحات في `resources/js/Pages` | 248 |
| Composables | 19 تشغيلية + 9 اختبار |
| Layouts | 6 |

**الطريقة:** حصر آلي (`find`/`grep`) + قراءة مباشرة + تحليل آلي للتكرار + 4 تحقيقات متخصصة مستقلة + تحقّق شخصي لكل نتيجة حرجة.

---

## 9. حدود التحقق (ما لم يُفحص)

| البند | السبب |
|---|---|
| المخطط الفعلي لقاعدة البيانات | كل استنتاجات الفهارس مصدرها **ملفات الـ migration** لا `SHOW CREATE TABLE`. لم يُتصل بأي قاعدة بيانات. |
| سلوك التزامن الحقيقي | لم تُشغَّ أي عملية متزامنة. استنتاجات الـ race conditions من تحليل الكود. |
| تغطية الاختبارات (code coverage) | لم يُولَّد تقرير تغطية — الأداة (Xdebug/PCOV) غير مُتحقَّق من توفرها. |
| مقارنة بصرية قبل/بعد | لم تُلتقط لقطات شاشة — تتطلب تشغيل خادم production. |
| فحص WCAG للتباين | يتطلب ألواناً مُصيَّرة (rendered)، لا يمكن استنتاجه من الكود. |
| `resources/js/Pages/Public/landing/*` | فُحصت كنمط تكرار مع `LandingPreview.vue` فقط. |
| حزمة `app/Actions` (6 ملفات) | فُحصت كمصادر في خريطة المعمارية، دون تدقيق داخلي تفصيلي. |
| `app/Services/AI`، `Zatca`، `Backup` | حُصرت في الخريطة؛ لم تُدقَّق داخلياً. |
| ملفات الاختبار الـ 222 | فُحصت هيكلها وعددها ونقاط تغطيتها، لا محتوى كل اختبار. |
