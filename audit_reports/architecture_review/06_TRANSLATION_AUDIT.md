# 06 — تدقيق نظام الترجمة (Translation Audit)

**المشروع:** `/Users/ahmad/Herd/carag-v2` · **التاريخ:** 2026-11-11 · **النطاق:** قراءة فقط

---

## 1. الحكم العام

> **الشكل منظّم، المحتوى مضطرب.**

القاموس متوازن تماماً بين اللغتين (7,631 مفتاحاً لكل منهما، صفر اختلاف). لكن **42–44 مفتاحاً مفقوداً** (يُعرض كنص مفتاح)، و**969 نصاً عربياً مثبَّتاً**، و**لا يوجد أي دعم للجمع العربي**، واسم المنظومة في الإنجليزية **مكسور آلياً**.

---

## 2. الجرد المُقاس

### 2.1 جانب Vue

| المقياس | القيمة |
|---|---|
| الملفات | `ar.json` · `en.json` |
| عدد النطاقات في المستوى الأعلى | 57 (متطابقة في اللغتين) |
| **المفاتيح الورقية** | **7,631** في كل ملف |
| **مفاتيح في `ar` فقط** | **0** ✅ |
| **مفاتيح في `en` فقط** | **0** ✅ |
| قيم فارغة | 2 في `ar.json` (`inventory.parts.make_model_hint`، `make_model_placeholder`)، 0 في `en.json` |
| `ar == en` (غير مترجم) | **53** — كلها مشروعة (أسماء علامات `Apple Pay`/`Visa`/`Moyasar`، placeholders `0.00`/`SUP-001`/`VIN`/`+966…`، `#`، `%`) |

### 2.2 جانب PHP

| المقياس | القيمة |
|---|---|
| الملفات | 48 في `lang/ar/` + 48 في `lang/en/` |
| المفاتيح | 4,192 (ar) · 4,194 (en) |
| **فجوة 2 مفتاح** | `auth.invitation_invalid` (ar فقط ناقص) · `messages.company_transactions.approved` (ar ناقص) |

### 2.3 حجم الاستخدام

| السطح | الاستدعاءات | المفاتيح المميزة |
|---|---|---|
| JS/TS/Vue (4,840 ملف) | **10,747** | 5,585 |
| PHP (152 ملف) | — | 504 |

---

## 3. 🔴 المفاتيح المفقودة — مُتحقَّق منها بأداة المشروع

الأمر: `php artisan translations:check --source=both --locale=ar|en`

| النطاق | `en` | `ar` | أمثلة |
|---|---|---|---|
| `system.payment.*` | **17** | 17 | `failed_title`، `failed_subtitle`، `retry_payment`، `proceed_to_payment`، `gateway_not_configured`، `gateway.bank_transfer`، `view_subscription`… |
| `common.*` | **9** | 9 | `attachments`، `no_records_found`، `no_results_search`، `no_attachments_hint`، `pagination`، `main_center`، `payment_method`، `reference_number`، `cheque` |
| `roles.*` | **8** | 8 | `description_placeholder`، `empty_description`، `label_ar_placeholder`، `label_en_placeholder`، `selected_permissions`، `system_name_placeholder`، `system_name_hint`، `cannot_delete_has_users` |
| `reports.*` | **5** | 5 | `customers.title`، `tax_periods_report.{invalid_transactions, legacy_branch_period, period_exists, review_tenant_missing}` |
| `crm.vehicles.title` | 1 | 1 | |
| `invoices.cash` | 1 | 1 | |
| `users.empty_description` | 1 | 1 | |
| `system.security.add_extra_layer` | 1 | 1 | |
| `auth.invitation_invalid` | — | **1** | `SetPasswordController.php:102` |
| `messages.subscription.account_suspended` | — | **1** | middleware |
| `messages.subscription.expired_readonly` | — | **1** | middleware |
| **المجموع** | **42** | **44** | |

### 3.1 🔴 انحدار مُثبت

`tests/Feature/I18nCoverageTest.php:35`:
```php
private const MISSING_KEYS_EXPECTED = [
    // Rebased to 13 for ar / 13 for en on 2026-10-25.
    // ... They were closed on 2026-11-08 ... The ceiling is now ZERO.
```

**العتبة = 0. Reality = 44 (ar) / 42 (en).**

⇒ **~31 مفتاحاً تسرّب بعد 2026-11-08.** الاختبار **أحمر** — وهذا يعني أن الأداة تعمل، لكن أحداً لم يُصلح.

### 3.2 مواقع ظهورها على الشاشة

| المفتاح | الملف | ما يراه المستخدم |
|---|---|---|
| `system.payment.failed_title` | `Pages/System/Payment/Failed.vue:5` | **عنوان صفحة فشل الدفع** |
| `system.payment.failed_subtitle` | `Failed.vue:6` | العنوان الفرعي |
| `system.payment.retry_payment` | `Failed.vue:92` | زر إعادة المحاولة |
| `system.payment.proceed_to_payment` | `Checkout.vue:212` | **CTA الدفع** |
| `system.payment.gateway_not_configured` | `Checkout.vue:92` | عنوان التنبيه |
| `system.payment.gateway_not_configured_desc` | `Checkout.vue:95` | نص التنبيه |
| `common.no_records_found` | `Common/DataTable.vue` +5 | **حالة فارغة في كل جدول** |
| `common.pagination` | `Common/AppPagination.vue:86` | `aria-label` الترقيم |
| `common.attachments` | `Common/AppAttachmentManager.vue:20,23` | عنوان المكوّن |
| `common.main_center` | `Reports/PurchaseInvoices.vue` +2، `Reports/Payments.vue:596`، `Reports/Invoices.vue:722` | اسم المركز الرئيسي |
| `roles.*` (8) | `Settings/Roles/RoleFormModal.vue` | placeholders نموذج الدور |
| `users.empty_description` | `Settings/Users/Index.vue:337` | حالة فارغة |
| `reports.customers.title` | `Reports/Customers.vue:1257` | **عنوان التقرير** |
| `messages.subscription.account_suspended` | `Middleware/EnsureTenantActive.php:44` | **كل مستأجر معطَّل** |
| `messages.subscription.expired_readonly` | `Middleware/EnsureTenantActive.php:54` | **كل مشترك منتهٍ** |

> **الأسوأ:** مفتاحان في **middleware** — أي مستأجر معطَّل أو مشترك منتهٍ يرى **المفتاح الخام** على **كل صفحة** يفتحها.

---

## 4. التكرار — 822 قيمة مشتركة بين 2,986 مفتاحاً (39%)

### 4.1 مرشحات دمج آمنة (نفس المفهوم، سطح مختلف)

| القيمة | عدد المفاتيح | المفتاح المقترح | المخاطرة |
|---|---|---|---|
| `الحالة` | 29 | `common.labels.status` | منخفض |
| `العميل` | 22 | `common.labels.customer` | منخفض — انتبه `group_customer` = «مجموعة» |
| `الإجمالي` | 22 | `common.labels.total` | منخفض — `grand_total` مفهوم مختلف |
| `الخدمات` | 21 | `common.labels.services` | منخفض — `services_count` عدّ |
| `المبلغ` | 20 | `common.labels.amount` | منخفض |
| `الخصم` | 19 | `common.labels.discount` | منخفض |
| `الاسم` | 18 | `common.labels.name` | منخفض |
| `التاريخ` | 17 | `common.labels.date` | منخفض |
| `عرض` | 17 | `common.actions.view` | ⚠️ «عرض» فعل (زر) و«عرض» عدّ («عرض X من Y») — **مفهومان** |
| `البريد الإلكتروني` | 16 | `common.labels.email` | منخفض |
| `ر.س` | 16 | `common.currency.sar` | منخفض |
| `المركبة` | 16 | `common.labels.vehicle` | منخفض |
| `الفرع / المركز` | 16 | `common.labels.center` | منخفض |
| `ملاحظات` | 11 | `common.labels.notes` | منخفض |
| `العنوان` | 13 | `common.labels.address` | ⚠️ `common.title` = «عنوان» ≠ «عنوانوان» — **تعارض نصي** |
| `من تاريخ` / `إلى تاريخ` | 13 / 13 | `common.date.from` / `common.date.to` | منخفض |
| `المتبقي` | 11 | `common.labels.balance` | ⚠️ `qty_pending` كمية لا مبلغ |

### 4.2 ⛔ مرشحات **لا تُدمج** — تعارض دلالي

| القيمة | المفاتيح | السبب |
|---|---|---|
| **`مدفوع` / `مدفوعة` / `مسدد`** | 15 | **ثلاثة معانٍ**: (أ) حالة سداد الفاتورة · (ب) **المبلغ** المدفوع (`المدفوع` نكرة/معرَّفة) · (ج) **طريقة** الدفع (نقد/بطاقة/تحويل). اختلاف التذكير العربي أيضاً: `مدفوع` (مذكر) vs `مدفوعة` (مؤنث للفاتورة) vs `مسدد` (مُسدَّد). **لا تُدمج أبداً.** |
| **`مسودة`** | 16 | نفس الكلمة، **دورات حياة مختلفة**: مسودة عقد ≠ مسودة فاتورة ≠ مسودة إذن استلام. الدمج كـ**تسمية** مقبول، كـ**enum** يخلط القوائم. |
| **`ملغي`** | 11 | `work_orders.status.cancelled` (كرت عمل) ≠ `work_orders.item_status.cancelled` (بند). و`reports.vehicles_report.status_cancelled` ≠ `ticket_status_cancelled` (حالة المركبة ≠ حالة التذكرة). |
| **`مكتمل`** | 11 | 🔴 `work_orders.status.completed` و`work_orders.status.done` — **مفتاحان بنفس القيمة العربية `مكتمل` وبإنجليزية مختلفة** (`Completed` vs `Done`). عيب i18n حقيقي. |
| **`نشط`** | 11 | "نشط" العقد ≠ الموظف ≠ المستخدم ≠ الاشتراك — lifecycles مستقلة. |
| **`إلغاء`** | 10+ | **فعل** (زر الإلغاء) ≠ **إغلاق** نافذة. `system.feedbacks.reply_modal.cancel` = "إغلاق" دلالياً. |
| **`رقم ضريبي`** | 10 | 🔴 **3 كيانات مختلفة**: رقم ضريبة الفرع (`center_settings.profile.vat_number`)، رقم ضريبة المستأجر (`company_profile.profile.vat_number`)، رقم ضريبة **العميل** (`customers.form.tax_number`)، رقم ضريبة **المورد** (`purchasing.suppliers.tax_number`). **مشكلة مصطلح لا تكرار.** |
| **`الفاتورة` / `فاتورة`** | 13 |Tooltips/صفحات/صلاحيات/تبويبات — أدوار UI مختلفة، تكرار نصي مشروع. |

### 4.3 ⭐ أعلى قيمة/أقل مخاطرة: chrome التقارير

**9 ملفات تقارير** تكرّر **نفس 15 مفتاحاً**:

```
reports.payments_report.*      reports.tickets_report.*
reports.services_report.*      reports.customers_report.*
reports.invoices_report.*      reports.purchase_invoices_report.*
reports.stock_activities_report.*  reports.suppliers_report.*
reports.employees_report.*
```

المكرّر: `print_report` (13) · `back_to_reports` (13) · `export_excel` (11) · `date_range` (11) · `today` (15) · `yesterday` (12) · `this_week` (12) · `this_month` (14) · `this_year` (12) · `all_time` (12) · `all_centers` (12) · `date_from` (13) · `date_to` (13) · `to` (12) · `from` (15)

**الدمج:** `reports.common.*` ⇒ **توفير ≈ 120 مفتاحاً** · **المخاطرة منخفضة جداً** (chrome UI خالص، نفس الدلالة في كل تقرير).

> **ملاحظة:** `reports.payments_report.shop` = `الفرع` — **الاسم فيه "shop" والقيمة "فرع"**. عيب تسمية يجب إصلاحه مع الدمج.

---

## 5. المفاتيح غير المستخدمة — 1,400 (18.3%)

### المنهجية

1. استخراج كل استدعاء `$t('…')` / `te('…')` / `t('…')` من 4,840 ملف (خارج `*.test.*` و`i18n/lang/`) ⇒ 5,585 مفتاحاً حرفياً.
2. استخراج **91 بادئة ديناميكية** من 3 صيغ بناء: `'prefix.' + var` · `` `prefix.${var}` `` · `'a'+'b'`.
3. طرح 722 مفتاحاً قابلاً للوصول ديناميكياً.
4. الباقي (1,400) **غير مستخدم في أي مسار**.

### أهم الفئات

| الفئة | العدد | الملاحظة |
|---|---|---|
| `system.*` | 225 | أكبر شريحة |
| `reports.*` | 183 | مرايا لمفاتيح تُبنى ديناميكياً الآن |
| `work_orders.*` | 127 | منها `work_orders.activities.actions.*` (19 مفتاحاً) |
| `validation.*` | **110** | 🔴 **النسخة Vue منnamespace Laravel — ميتة بالكامل** |
| `quotes.*` | 99 | |
| `company_profile.*` | 70 | 🔴 **نسخة قديمة من نسخة PHP** |
| `dashboard.*` | 52 | |
| `inventory.*` | 51 | |
| `purchasing.*` | 49 | |
| `customer_portal.*` | 48 | |
| `hr.*` | 47 | |
| `customers.*` | 32 | |
| `tenant_site.*` | 28 | |
| `invoices.*` | 25 | |
| `center_settings.*` | 24 | |
| `subscription.*` | 23 | |
| `settings.*` | 19 | |
| `landing.*` | 18 | |

### 🔴 `validation.*` في Vue — 123 مفتاحاً، 110 غير مستخدمة

PHP `lang/{ar,en}/validation.php` (123 مفتاحاً) يملك نفس النصوص. النسخة Vue **نسخة مكرّرة**: إما تُحذف بالكامل، أو تُصبح مصدراً واحداً.

### 🔴 `company_profile.*` في Vue — 70 غير مستخدمة

نفس المفاتيح **موجودة وتُستخدم في PHP**: `lang/ar/company_profile.php` (240 مفتاحاً)، مثل `company_profile.admin_user.email_invalid` في `app/Http/Controllers/App/CompanyProfileController.php:360`.

⇒ **نسخة Vue stale ستنحرف عن PHP.**

### ⚠️ تحذير منهجي

الماسح يستثني `*.test.*` ولا يحلّ مفاتيح تمر عبر prop أو متغيّر. **كل الاستدعاءات المُتحقَّق منها استدعاءات مباشرة `$t('literal')`** — لم أجد نمطاً يُخفي مفتاحاً.

---

## 6. النصوص المثبَّتة (Hardcoded)

### 6.1 Vue — **969 سطر عربي** في ~180 ملف

| الملف | العدد | الأثر |
|---|---|---|
| `Pages/EmployeePortal/MyTasks.vue` | 101 | **أكبر م concentrations** |
| `Composables/usePrint.js` | 72 | 🔴 **نصوص مالية عربية داخل محرك الطباعة** |
| `Pages/Settings/Website.vue` | 60 | |
| `Pages/Reports/Employees.vue` | 48 | |
| `Print/PrintTemplates/TemplateDefaultA4.vue` | 41 | |
| `Pages/Reports/StockActivities.vue` | 40 | |
| `Pages/Auth/Register.vue` | 39 | |
| `Components/Common/CommandPalette.vue` | 33 | |
| `Print/PrintTemplates/TemplateThermal80.vue` | 30 | |

### 6.2 🔴 أخطر ما في `usePrint.js` — محرك الطباعة

```js
// resources/js/Composables/usePrint.js:99-104
cash: 'نقداً', card: 'بطاقة مدى / ائتمانية', bank_transfer: 'تحويل بنكي',
check: 'شيك', online: 'دفع إلكتروني', other: 'آخر'
```
⇒ **اللغة الإنجليزية تطبع طرق الدفع بالعربية.**

```js
// resources/js/Composables/usePrint.js:136-148
invoice: taxActive ? 'فاتورة ضريبية' : 'فاتورة',
proforma_invoice: 'فاتورة أولية',
parts_invoice: 'فاتورة قطع غيار ضريبية',
work_order: 'كرت صيانة',
receipt: 'سند قبض مالي',
purchase_invoice: 'فاتورة مشتريات ضريبية',
credit_note: 'إشعار دائن (فاتورة إرجاع)'
```
⇒ **13 نوع مستند، كل عناوينها بالعربية.** فاتورة إنجليزية تطبع عنواناً عربياً.

```js
// resources/js/Composables/usePrint.js:56
const suffix = isRtl.value ? ' ر.س' : ' SAR';
```
⇒ **رمز العملة مكرَّر خارج `common.currency`.**

```js
// resources/js/Composables/usePrint.js:177-192
white: { ar: 'أبيض', en: 'White' }, ...
```
⇒ خريطة ألوان في JS بدل `common.colors.*`.

### 6.3 🔴 انتهاكات اسم المنظومة (6 مواضع)

| # | الملف | السطر | النص |
|---|---|---|---|
| 1 | `Print/PrintTemplates/TemplateThermal80.vue` | 32 | `'مركز خدمة برو'` |
| 2 | `Print/PrintTemplates/TemplateThermal80.vue` | 654 | `'خدمة برو'` |
| 3 | `Print/PrintTemplates/TemplateDefaultA4.vue` | 1336 | `'خدمة برو'` |
| 4 | `Pages/Auth/Register.vue` | 573-574 | `'مرحباً بك في خدمة برو'` / `'Welcome to Khidmh Pro'` |
| 5 | `Pages/Public/CustomPage.vue` | 34-35 | `'خدمة برو'` / `'Khidmh Pro'` |
| 6 | `resources/js/app.js` | 31 | `\|\| import.meta.env.VITE_APP_NAME \|\| 'Laravel'` |

> **`common.app_name` مستخدم بشكل صحيح في موضع واحد فقط** — `app.js:31` — **وهو بحد ذاته السقوط إلى `'Laravel'`**.

### 6.4 PHP — **1,217 سطر** عربي في `app/`

| الملف | السطر | الأثر |
|---|---|---|
| `Models/AdminActivityLog.php` | 41-46 | 🔴 **سجل نشاط الإدارة بالعربية دائماً**: `login`/`logout`/`create`/`update`/`delete`/`impersonate` |
| `Models/AdminUser.php` | 144-146 | 🔴 **أدوار المنصة بالعربية دائماً**: `super_admin`/`admin`/`support` |
| `Models/SystemAnnouncement.php` | 60-63, 74-78 | أنواع الإعلانات والجمهور بالعربية |
| `Models/BankTransferReceipt.php` | 91-93 | حالات الإيصال بالعربية |
| `Models/TenantWebsite.php` | 253-254 | نص تسويقي في الموديل |
| `Mail/SubscriptionActivatedMail.php` | 35 | عنوان بالعربية |
| `Mail/SubscriptionExpiredMail.php` | 29 | عنوان بالعربية |
| `Mail/SubscriptionRenewalReminderMail.php` | 31 | 🔴 **جمع عربي يدوي** |
| `Mail/TwoFactorCodeMail.php` | 32 | 🔴 عنوان عربي + `config('app.name')` **إنجليزي** |
| `Notifications/ResetPasswordNotification.php` | 105 | 🔴 نفس الخلل |
| `Notifications/WelcomeEmployeeInvitation.php` | 55 | 🔴 نفس الخلل |
| `Models/Service.php` | 190-195 | `"{$hours}س {$minutes}د"` |
| `Models/QuoteLine.php` | 158 | `'أخرى'` |
| `Services/InvoiceService.php` | 109-124 | 🔴 **وحدات الضمان بالعربية داخل الخدمة** + جمع يدوي |

---

## 7. عدم اتساق المصطلحات

| المفهوم | الصيغ | التفصيل |
|---|---|---|
| **الفاتورة** | **6** 🔴 | `فاتورة` (7) · `الفاتورة` (3) · `فاتورة ضريبية` (2) · `فاتورة أولية` (3) · `فاتورة مبيعات` (2) · `فاتورة مشتريات` (3) |
| **الضريبة** | **5** 🔴 | `الضريبة` (8) · `VAT` (4) · `ضريبة القيمة المضافة` (4) · `ضريبة` (1) · `الرقم الضريبي` (10) |
| **الفرع/المركز** | **4** 🔴 | `الفرع / المركز` (16) · `الفرع` (10) · `المركز` (3) · `مركز` (1) |
| **مدفوع** | **3** 🔴 | `مدفوع` (5) · `مدفوعة` (9) · `مسدد` (2) |
| **العميل** | 3 | `العميل` (22) · `العملاء` (4) · `عميل` (3) |
| **المركبة** | 2 | `المركبة` (16) · `مركبة` (2) — ✅ **لا يوجد `سيارة`**، المجال متّسق |
| **الخدمة** | 4 | `الخدمات` (21) · `الخدمة` (7) · `خدمة` (5) · `خدمات` (1) |
| **القطع** | 3 | `قطع الغيار` (7) · `القطعة` (2) · `قطعة` (1) |
| **كرت العمل** | 2 | `كرت صيانة` (2) · `كرت العمل` (1) |

### 7.1 تفاصيل مهمة

**🔴 «الفاتورة» معرَّفة ونكرة في نفس الواجهة:**
`work_orders.columns.total` و`work_orders.invoice_total` يستخدمان `الفاتورة`، بينما كل مكان آخر يستخدم `فاتورة`.

**🔴 التمييز الضريبي/غير الضريبي غير متّسق عبر 3 أسطح:**
| السطح | النص |
|---|---|
| `invoices.tax_invoice` | `فاتورة ضريبية` (القاموس) |
| `usePrint.js:136` | `فاتورة ضريبية` (مثبَّت) |
| `Invoices/Print.vue:220` | 🔴 **`فاتورة ضريبية مبسطة`** — **مصطلح قانوني ZATCA خاص، غائب عن القاموس والطباعة** |

⇒ **الأنواع الثلاثة تختلف.**الفاتورة الضريبية المبسطة نوع مستند قانوني **مختلف** عن الضريبية العادية.

**🔴 `VAT` (اختصار إنجليزي) داخل الملف العربي:**
`common.vat` = `'VAT'` في **ar.json وen.json معاً**. المستخدم العربي sees "VAT" بينما "ضريبة القيمة المضافة" في 4 مفاتيح على بُعد.

**🔴 عدّ العملاء عربي خاطئ نحوياً:**
`customers.total_count` = `'عميل'` ⇒ "عدد عميل". الصحيح: `'العملاء'` (بعد العدد جمع).
نفس الشيء: `vehicles.total_count` = `'مركبة'`.

**🔴 `work_orders.status.completed` vs `work_orders.status.done`:**
مفتاحان، نفس العربية `مكتمل`، إنجليزية مختلفة (`Completed` / `Done`).

---

## 8. الجمع والسياق — **غير مُدعوم إطلاقاً**

| الفحص | النتيجة |
|---|---|
| `$tc(` في `resources/js` | **0** |
| `trans_choice` في `app/` | **0** |

### الأثر: العربية بلا تمييز عدد

| المفتاح | العربية | المشكلة |
|---|---|---|
| `communications.results_count` | `{count} سجل` | يحتاج سجل/سجلات/سجلاً |
| `appointments.total_count` | `{count} موعد` | يحتاج موعد/مواعيد/موعداً |
| `workshop.total_active` | `{count} بند نشط` | يحتاج بند/بنود/بنداً |
| `customer_portal.duration_minutes` | `:count دقيقة` | يحتاج دقيقة/دقائق/دقيقة |
| `customers.total_count` | `عميل` | 🔴 **نحوياً خطأ** |
| `vehicles.total_count` | `مركبة` | 🔴 **نحوياً خطأ** |
| `messages.service_has_quote_lines` | `يوجد :count تقييم يستخدم هذه الخدمة` | يحتاج تقييم/تقييمان/تقييماً |

### جمع يدوي في PHP

```php
// app/Mail/SubscriptionRenewalReminderMail.php:31
$days = $this->daysRemaining === 1 ? 'غداً' : "خلال {$this->daysRemaining} أيام";
```
⇒ يعالج 1 مقابل الباقي فقط. **3–10 يحتاج `أياماً`، و11+ يحتاج `يوماً`.**

```php
// app/Services/InvoiceService.php:109-124
'أيام' / 'أسابيع' / 'أشهر' / 'سنوات'
```
⇒ نفس القاعدة `>= 3 && <= 10` مطبَّقة على السنوات — **قاعدة 3–10 مقلوبة للسنت** (العربية: 11 سنة مفرد).

### التذكير/التأنيث

`مدفوع` (مذكر) · `مدفوعة` (مؤنث) coexisting في نفس القاموس — **حل وسط لتPROCّ** بلا جمع. عيبه: كل نوع فاتورة جديد يحتاج مفتاحين، وينحرفان — وقد انحرفا فعلاً (`invoices.status.partial` = `مدفوعة جزئياً` مقابل `common.status_partial` = `مدفوع جزئياً`، مع اختلاف في حرف P الإنجليزي).

---

## 9. 🎯 False positive مهم — مفاتيح المحتوى ليست ترجمة

في `resources/js/Pages/Public/landing/*` تُمرَّر **مفاتيح محتوى JSON** عبر `$t()`:

`hero_title_ar` · `hero_subtitle_ar` · `audience_tag_ar` · `features_title_ar` · `pricing_subtitle_ar` · `testimonials_title_ar` · `footer_about_ar` · `copyright_text_ar` · `login_button_text_ar` · `title_ar` · `description_ar` · `maintenance_title_ar` …

**هذه ليست مفاتيح ترجمة** — هي حقول في `tenant_websites.settings` JSON.

⚠️ **علامة تصميم سيئة:** تمرير مفاتيح المحتوى عبر `$t()` يعني أن **حقلاً مفقوداً يُعرض كمفتاح محتوى** (`hero_title_ar` نصاً حرفياً على الصفحة العامة).

**التوصية:** فصل المحتوى عن i18n — أسماء الحقول تُقرأ من JSON مباشرة، والقيم تُمرَّر عبر `$t` **بعد** التحقق من وجودها.

---

## 10. البنية المقترحة

```
common/
  labels/    status date name total amount discount notes address email phone
             customer vehicle center service part currency branch
  actions/   save cancel close delete confirm edit view print export
  states/    draft cancelled active inactive pending approved completed
             paid unpaid partial          ← تسمية فقط، لا enum
  count/     customers vehicles items services parts records
  currency/  currency sar symbol
  colors/    white black silver gray red blue brown green gold orange
  pagination/ of showing page per_page first next prev last
  empty/     no_records_found no_results no_attachments_hint
  date/      date from to date_from date_to today yesterday
             this_week this_month this_year all_time
  file/      upload download delete attachment attachments reference_number
  app/       app_name app_slogan app_tagline
validation/  ← 🔴 تُحذف من Vue (PHP هو المالك)
actions/     save_invoice approve_quote …
navigation/  ← تُعاد تسمية `nav`
status/      invoice quote work_order work_order_item purchase_order
             purchase_invoice grn transfer contract employee subscription
             user appointment message
components/  pagination empty_state attachment_manager …
system/      tenants billing credits integrations dev_center
tenants/     profile branches notifications print website billing
customers/   vehicles contracts statements merge
workOrders/  items parts labor photos inspections activities
invoices/    credit_notes payments proforma
inventory/   parts warehouses transfers moves categories units
purchasing/  suppliers orders invoices grn returns
hr/          employees attendance leaves shifts payroll contracts documents
reports/     hub + reports/common/* + per-report namespaces
modules/     services_management packages pricing departments service_bays
             roles users
```

---

## 11. جدول الدمج المقترح

| المفتاح الحالي | المقترح | القرار | المخاطرة |
|---|---|---|---|
| `common.*` (195) | `common.labels.*` · `common.actions.*` · `common.states.*` · `common.count.*` · `common.date.*` · `common.empty.*` · `common.currency.*` · `common.colors.*` · `common.pagination.*` · `common.file.*` | **تقسيم** | منخفض — إعادة تنظيم بحت |
| `common.status` + 8 `*.status` | `common.labels.status` | **دمج التسمية** | منخفض |
| `common.status_draft` / `_cancelled` | `common.states.draft` / `_cancelled` | **دمج** | متوسط — تسمية فقط، لا enum |
| `common.status_paid` / `_partial` / `_unpaid` | `common.states.*` | **إعادة تسمية + تصحيح التذكير** | منخفض |
| `common.paid` · `invoices.total_paid` · `work_orders.paid` | `common.labels.paid_amount` | **إعادة تسمية** (مبلغ ≠ حالة) | منخفض |
| `common.currency` · `common.currency_sar` · `reports.*_report.currency` | `common.currency.sar` | **دمج** | منخفض |
| `common.no_records_found` · `no_results_search` · `no_attachments_hint` | `common.empty.*` | **نقل** (ويُصلح المفقود) | **صفر** |
| `common.attachments` · `common.pagination` · `common.main_center` · `common.payment_method` · `common.reference_number` · `common.cheque` | `common.file.*` · `common.labels.*` · `payments.methods.cheque` | **نقل** | **صفر** |
| `nav.*` (20) | `navigation.*` | **إعادة تسمية** | منخفض |
| `validation.*` (123 Vue) | — | 🔴 **حذف** | **صفر** — 110 منها ميتة |
| `reports.*_report.{print_report, back_to_reports, export_excel, date_range, today, yesterday, this_week, this_month, this_year, all_time, all_centers, date_from, date_to, to, from}` (~120) | `reports.common.*` | **دمج** ⭐ | منخفض — أعلى قيمة/أقل مخاطرة |
| `reports.*_report.center` / `.group_center` | `common.labels.center` / `common.labels.group_by_center` | **دمج** | منخفض |
| `reports.payments_report.shop` | `common.labels.center` | **إعادة تسمية + دمج** | منخفض — عيب تسمية |
| `system.credits.tenant_2..5` | `common.labels.tenant` + سياق الصف | **حذف 4** | **صفر** — تكرار نسخ/لصق |
| `work_orders.status.completed` + `work_orders.status.done` | `status.work_order.completed` | **دمج** | منخفض — مع التحقق من الـ enum |
| `work_orders.status.*` · `work_orders.item_status.*` · `work_orders.work_order_statuses.*` · `work_orders.item.status_*` | `status.work_order.*` · `status.work_order_item.*` | 🔴 **توحيد 4 أشجار** | **عالٍ** — آخر بند |
| `invoices.status.*` · `invoices.payment_status.*` · `invoices.purchases.statuses.*` | `status.invoice.*` · `status.purchase_invoice.*` | **إعادة تسمية** | متوسط — `paid` متطابق ⇒ يُدمج، `partial` يختلف بالتذكير ⇒ يُصحَّح ثم يُدمج |
| `payments.methods.*` · `payments.types.*` | **يُبقيان منفصلين** | — | 🔴 `methods` = قناة الدفع · `types` = نوع الدفعة (دفع/استرداد) |
| `company_profile.*` (240) | `tenants.profile.*` · `tenants.branches.*` · `tenants.address.*` … | **إعادة هيكلة** | متوسط — النسخة Vue stale (70 ميتة) |
| `system.*` (225 ميتة) | — | **تشذيب** | **صفر** |

---

## 12. الخطة المرحلية

| المرحلة | الفعل | المخاطرة | التوفير |
|---|---|---|---|
| **P0** | 🔴 **إضافة 44+42 مفتاحاً مفقوداً** — من قائمة `translations:check` | **صفر** | 0 |
| **P0** | 🔴 **تصحيح `en.json` → `common.app_name` = `'Khidmh Pro'`** | **صفر** | 0 |
| **P0** | 🔴 **إزالة السقوط إلى `'Laravel'`** في `app.js:31` | **صفر** | 0 |
| **P0** | إضافة `missing` handler في `i18n/index.js` يسجّل المفتاح في التطوير | **صفر** | 0 |
| **P0** | **توحيد اللغة بين Vue و PHP** — Vue الافتراضي `'ar'` و PHP `env('APP_LOCALE','en')` | منخفض | 0 |
| **P1** | ⭐ **دمج chrome التقارير → `reports.common.*`** | منخفض | **≈120** |
| **P1** | 🔴 **حذف `validation.*` من Vue** | **صفر** | **123** |
| **P1** | **تصحيح تذكير «paid»** (`مدفوع`/`مدفوعة`/`مسدد`) | متوسط | ≈10 |
| **P2** | تقسيم `common.*` | منخفض | 0 |
| **P2** | **صيغ جمع عربية** لـ 9 مفاتيح عدّ + `trans_choice` في 3 مواضع PHP | منخفض | 0 |
| **P2** | **حذف 1,400 مفتاحاً غير مستخدم** | منخفض | **1,400** |
| **P2** | حذف `system.credits.tenant_2..5` | **صفر** | 4 |
| **P3** | استخراج 969 نصاً Vue + 1,217 نصاً PHP | متوسط | 0 |
| **P3** | **إصلاح 6 انتهاكات اسم المنظومة** | **صفر** | 0 |
| **P4** | 🔴 **توحيد 4 أشجار حالة كرت العمل** | **عالٍ** | ≈130 |

---

## 13. ⚠️ قواعد الدمج — ما يجب عدم فعله

| ❌ لا تدمج | السبب |
|---|---|
| `invoices.status.paid` ⟷ `common.status_paid` ⟷ `reports.*_report.paid_badge` | 3 معانٍ + 3 صيغ تذكير عربي |
| `invoices.total_paid` ⟷ `invoices.status.paid` | المبلغ ≠ الحالة |
| `payments.methods.cash` ⟷ `invoices.cash` | قناة الدفع ≠ نقد |
| `payments.methods.*` ⟷ `payments.types.*` | القناة ≠ نوع الدفعة |
| `center_settings.profile.vat_number` ⟷ `company_profile.profile.vat_number` ⟷ `customers.form.tax_number` ⟷ `purchasing.suppliers.tax_number` | 4 كيانات مختلفة |
| `work_orders.status.cancelled` ⟷ `work_orders.item_status.cancelled` | كرت العمل ≠ البند |
| `reports.vehicles_report.status_completed` ⟷ `reports.vehicles_report.ticket_status_done` | حالة المركبة ≠ حالة التذكرة |
| `hr.contracts.status.draft` ⟷ `invoices.status.draft` | دورة حياة مستقلة |
| `common.title` ⟷ `common.address` | نفس العربية، معنيان مختلفان |
| `common.cancel` ⟷ `common.close` | الإلغاء ≠ الإغلاق — **يجب فصلهما** |
| `common.of` ⟷ `common.from` | «من» الوصل ≠ «من» التاريخ |

---

## 14. ملخص

| المحور | التقييم | الجملة القابلة للتنفيذ |
|---|---|---|
| توازن ar/en | ✅ ممتاز | 7,631 = 7,631، صفر اختلاف |
| المفاتيح المفقودة | 🔴 42/44 | أضفها — القائمة جاهزة من `translations:check` |
| حماية الانحدار | ✅ **موجودة وتعمل** | `I18nCoverageTest` بعتبة 0 — تُظهر الفشل |
| التكرار | ⚠️ 39% | ادمج chrome التقارير فقط أولاً |
| النصوص المثبَّتة | 🔴 969 + 1,217 | `usePrint.js` هو الأولوية (مالي + ثنائي اللغة) |
| اسم المنظومة | 🔴 6 انتهاكات | `en.json` = "Pro service" — أصلح سطراً واحداً |
| الجمع العربي | 🔴 غير موجود | `$tc` = 0 · `trans_choice` = 0 |
| اتساق المصطلحات | ⚠️ 6 صيغ للفاتورة | وثّق قراراً واحداً لكل مفهوم |
| المحتوى في `$t()` | ⚠️ تصميم سيء | افصل مفاتيح المحتوى عن i18n |
