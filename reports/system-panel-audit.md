# تقرير مراجعة شاملة — لوحة النظام (System Panel)
**التاريخ:** 2026-07-21
**المشروع:** Carag V2
**الـ Branch:** `feature/phase-2-goal-3`
**نطاق المراجعة:** `app/Http/Controllers/System/` + `resources/js/Pages/System/` + `resources/js/Layouts/SystemLayout.vue` + `routes/web.php` (مجموعة `system/*`)

---

## 1. ملخص تنفيذي

اللوحة مبنية بعمق وتغطي **24 controller** و **39 صفحة Vue** و **100+ route**. البنية العامة جيدة، لكن:

- 🔴 **مشكلة حرجة واحدة:** روابط الـ Create/Edit لـ **Plans** و **PromoCodes** ستطلق 404 (الـ Controller يستدعي `System/Plans/Form` و `System/PromoCodes/Form` لكن الصفحات غير موجودة).
- 🟠 **3 نواقص حرجة أخرى** (Tenants/Show tabs، Integrations/Show، Announcements/Show) موجودة لكن تحتاج تحسين.
- 🟡 **تكرار فادح في Layout:** قائمة الـ Sidebar مكتوبة مرتين 100% (Desktop + Mobile) داخل نفس الـ Component.
- 🟡 **i18n مختلط:** الـ Developer Center خليط EN/AR، والتواريخ في الـ Layout تستخدم `toLocaleDateString` بدون RTL-aware.
- 🟢 الباقي: أنماط موحدة، `useToast/ConfirmModal` متاحان، RTL مدعوم، Dark mode شغّال.

---

## 2. خريطة اللوحة (ما هو موجود)

### 2.1 هيكل الـ Sidebar (الترتيب الحالي)

| # | المجموعة | العنصر | Route | Page | Status |
|---|---|---|---|---|---|
| 1 | Overview | الرئيسية | `/system` | `Dashboard.vue` | ✅ |
| 2 | الموقع العام | إعدادات الموقع | `/system/settings/website` | `Website/Index.vue` + 6 Tabs | ✅ |
| 3 | | رسائل الزوار | `/system/settings/contact-messages` | `ContactMessages/Index.vue` | ✅ |
| 4 | إدارة العملاء | المستأجرين | `/system/tenants` | `Tenants/Index.vue` + `Show.vue` | ✅ |
| 5 | | الاشتراكات | `/system/subscriptions` | `Subscriptions/Index.vue` + `Show.vue` | ✅ |
| 6 | | الفواتير | `/system/invoices` | `Invoices/Index.vue` + `Show.vue` | ✅ |
| 7 | | الأقساط | `/system/installments` | `Installments/Index.vue` + `Show.vue` | ✅ |
| 8 | إعدادات الخدمة | الباقات | `/system/plans` | `Plans/Index.vue` | ⚠️ Create/Edit مكسور |
| 9 | | الرموز الترويجية | `/system/promo-codes` | `PromoCodes/Index.vue` | ⚠️ Create/Edit مكسور |
| 10 | بوابة التواصل | الإعلانات | `/system/announcements` | `Announcements/Index/Create/Show` | ✅ |
| 11 | | قوالب التواصل | `/system/communication/templates` | `Communication/Templates/Index/Edit` | ✅ |
| 12 | | رصيد SMS | `/system/sms/packages` | `Credits/SmsPackages/Balances/Purchases/Usage` | ✅ |
| 13 | | رصيد WhatsApp | `/system/whatsapp/packages` | `Credits/WhatsappPackages/Balances/Usage` | ✅ |
| 14 | إدارة النظام | المسؤولين | `/system/admin-users` | `AdminUsers/Index.vue` (Modal-based) | ✅ |
| 15 | | التكاملات | `/system/integrations` | `Integrations/Index.vue` + `Show.vue` | ✅ |
| 16 | | الإعدادات العامة | `/system/settings/general` | `Settings/GeneralSettings.vue` | ✅ |
| 17 | | إعدادات الدفع | `/system/settings/payment` | `Settings/PaymentSettings.vue` | ✅ |
| 18 | | مركز التطوير | `/system/developer` | `Developer/Index.vue` | ✅ (i18n مختلط) |
| 19 | (حساب المستخدم) | (داخل User Menu) | `/system/profile` | `Profile/Index.vue` | ✅ |
| 20 | | 2FA Setup | `/system/security/2fa` | `Security/TwoFactorSetup.vue` | ✅ |
| 21 | (Login flow) | 2FA Challenge | `/2fa/challenge` | `Security/TwoFactorChallenge.vue` | ✅ |
| 22 | (Impersonation) | Login as Tenant | `POST /system/tenants/{id}/impersonate` | — (Redirect) | ✅ |
| 23 | | Stop Impersonation | `POST /impersonate/stop` | — (Redirect) | ✅ |
| 24 | (Top bar) | Activity Log | `/system/activity-log` | `AdminUsers/ActivityLog.vue` | ✅ |

### 2.2 Controllers + Models + Routes

| Controller | LOC | يستدعي View | Routes الرئيسية | ملاحظات |
|---|---:|---|---|---|
| `SystemDashboardController` | 58 | `System/Dashboard` | index | stats + recent tenants + balances |
| `TenantsController` | 227 | `System/Tenants/Index`+`Show` | index, show, suspend, activate, extendTrial, destroy | destroy خطير — يحذف كل شي forceDelete |
| `SubscriptionsController` | 206 | `System/Subscriptions/Index`+`Show` | CRUD + cancel/activate/extend | `store()` method موجود لكن بدون page |
| `PlansController` | 127 | `System/Plans/Index` + **`Form`** ❌ | CRUD | 🔴 Form.vue مفقود |
| `PromoCodesController` | ~190 | `System/PromoCodes/Index` + **`Form`** ❌ | CRUD | 🔴 Form.vue مفقود |
| `AdminUsersController` | 169 | `System/AdminUsers/Index`+`ActivityLog` | CRUD | Index.vue فيه modal ذاتي → شغّال |
| `IntegrationsController` | 319 | `System/Integrations/Index`+`Show` | CRUD + test + balance | providers: unifonic/twilio/whatsapp_cloud/smtp/authentica |
| `SmsCreditsController` | ~190 | `System/Credits/Sms*` | packages/balances/purchases/usage | 4 صفحات |
| `WhatsappCreditsController` | ~170 | `System/Credits/Whatsapp*` | packages/balances/usage | 3 صفحات |
| `AnnouncementsController` | 194 | `System/Announcements/Index/Create/Show` | CRUD + publish/unpublish | يدمج channels: email/sms/in_app |
| `CommunicationTemplatesController` | 41 | `System/Communication/Templates/Index+Edit` | index/edit/update | resource محدود بـ 3 methods |
| `PaymentController` | ~150 | `System/Payment/Checkout/Success/Failed` | checkout/callback/success/failed/retry | مفيش موافقة من الـ tenant flow |
| `PaymentSettingsController` | 163 | `System/Settings/PaymentSettings` | index/update | 5 gateways: moyasar/tap/paytabs/tabby/tamara |
| `WebsiteSettingsController` | 229 | `System/Website/Index` (6 tabs) | index/update/upload-image | 16 tabs في الـ index.vue |
| `GeneralSettingsController` | 45 | `System/Settings/GeneralSettings` | index/update | 2FA SMS enable toggle فقط |
| `ContactMessageController` | 54 | `System/ContactMessages/Index` | index/read/destroy | 15 per page |
| `ProfileController` | 100 | `System/Profile/Index` | index/update/updatePassword | من `Auth::guard('admin')` |
| `TwoFactorController` | 228 | `System/Security/TwoFactorSetup+Challenge` | setup/send/enable/disable/regenerate/challenge/verify | TOTP + SMS + Recovery codes |
| `AdminUsersController` (ActivityLog) | — | `System/AdminUsers/ActivityLog` | activityLog | filter by admin_id/action |
| `DeveloperController` | 149 | `System/Developer/Index` | index/audit/graph/ai-advice | ⚠️ `getGraph()` يرجع **mocked data** ثابت |
| `InstallmentsController` | ~120 | `System/Installments/Index+Show` | index/show/markPaid/updateOverdue | |
| `SubscriptionInvoicesController` | ~150 | `System/Invoices/Index+Show` | index/show/download/send/markPaid/cancel/regenerate-pdf | |
| `TenantSecurityController` | 27 | — (back redirect) | update2FASettings | مفيش page مستقلة — يتم استدعاؤها من Show tenant |
| `ImpersonationController` | 86 | — (redirect) | start/stop | ⚠️ منطق حساس — مفيش audit log |

**المجموع:** 24 controller، ~2,900 سطر PHP.

---

## 3. النواقص والمشاكل (مرتبة بالأولوية)

### 🔴 P0 — مكسور (يطلق 500/404)

#### 3.1 `System/Plans/Form.vue` مفقود
- **الدليل:** `PlansController.php:32` و `PlansController.php:75` كلاهما يستدعيان `Inertia::render('System/Plans/Form', ...)` لكن لا يوجد ملف.
- **النتيجة:** `/system/plans/create` و `/system/plans/{id}/edit` يطلقان 500.
- **الـ workaround الحالي:** `Plans/Index.vue` فيه modal inline لإنشاء/تعديل، لكن الـ routes `/plans/create` و `/plans/{id}/edit` مكسورة.
- **الإصلاح المقترح:** الخيار (أ): إنشاء `Form.vue` مكرّر. الخيار (ب) — **الموصى به:** حذف الـ routes لـ `create/edit` من `web.php` لأن الـ Index.vue يغطيها بـ modal، أو توجيه الـ routes لـ Index.vue مع anchor modal.

#### 3.2 `System/PromoCodes/Form.vue` مفقود
- نفس المشكلة: `PromoCodesController.php:43` و `:86` يستدعيان `Form` لكن الملف مفقود.
- `/system/promo-codes/create` و `/system/promo-codes/{id}/edit` يطلقان 500.
- نفس الحل: إمّا إنشاء Form.vue أو تعديل الـ routes لتشير للـ modal.

#### 3.3 `Subscriptions/Store` method بدون page
- `SubscriptionsController::create()` غير موجود، لكن `store()` يقبل POST من `/system/subscriptions`.
- `Subscriptions/Index.vue` فيه `openModal()` لاستدعاء `create`، لكن مفيش route handler للـ "create view".
- **التشخيص:** الـ Index.vue بيعمل submit لـ POST `/system/subscriptions` مباشرة، فلو الـ Index.vue شغّال، كل شي تمام. لكن ما في page تأكيد أو review.

---

### 🟠 P1 — مشاكل هيكلية / UX

#### 3.4 تكرار Sidebar فادح في `SystemLayout.vue`
- **السطور 42–561:** Desktop sidebar (~520 سطر)
- **السطور 789–1326:** Mobile drawer (~540 سطر) — **نسخة 100% مكررة** من نفس الـ nav.
- أي تعديل في القائمة (إضافة رابط، تغيير label، إعادة ترتيب) لازم يتعمل في مكانين.
- **الإصلاح:** استخراج قائمة `const navSections = [...]` في `<script setup>` واستخدامها مرتين — مرة في Desktop، مرة في Mobile.

#### 3.5 `Tenants/Show.vue` ضخم بدون تقسيم
- **909 سطر** في ملف واحد. يعرض: usage analytics (6 cards) + recent work orders + plans + security + impersonate + suspend/activate + extend trial.
- **الإصلاح:** استخراجه لـ composables/components بنفس النمط المتّبع في `WorkOrders/Show.vue` (WorkOrderHeader, WorkOrderCustomerCard, إلخ):
  - `<TenantHeader/>` — name + status + actions
  - `<TenantAnalyticsCards/>` — 6 usage cards
  - `<TenantRecentActivity/>` — work orders list
  - `<TenantSubscriptionPanel/>` — current plan + extend
  - `<TenantSecurityPanel/>` — 2FA enforcement (يستخدم `TenantSecurityController`)

#### 3.6 `Developer/Index.vue` i18n مختلط
- **السطور 12-13:** "Developer Center" + "Codebase Health, Security Verification, and AI Architectural Audits" (EN).
- باقي الصفحة: "Release Status: Failed", "Overall Release Score", "Run Fresh Audit" — كلها EN.
- مفيش ترجمة AR. مع إن باقي اللوحة عربي.
- **الإصلاح:** استخدام `$t('developer.title')` إلخ، أو إنشاء `lang/ar.json` + `lang/en.json` keys للـ Developer Center.

#### 3.7 `getGraph()` يرجع mocked data
- `DeveloperController.php:73-98`: الـ nodes والـ links مكتوبة hardcoded (WorkOrders ↔ Inventory/Vehicles/Customers/Invoices/Notifications).
- **الإصلاح:** استبدالها بـ scanner حقيقي يقرأ `app/` ويبني graph بناءً على imports/references.

#### 3.8 `DeveloperController::aiAdvice` رد مزيّف
- `DeveloperController.php:103-148`: يبني رد ثابت من `AuditViolation` بدون استدعاء AI حقيقي. `solution` يقول "Extract logic from X into Action class" — template copy-paste.
- **الإصلاح:** تكامل مع `LlmCall` skill أو OpenAI API حقيقي.

#### 3.9 `ImpersonationController` بدون audit log
- `ImpersonationController.php`: start/stop بس — ما في `logActivity('impersonate_start', ...)`. لو admin عمل impersonate لـ tenant، ما في أثر مسجّل.
- **الإصلاح:** إضافة `AdminActivityLog::create(['action' => 'impersonate', 'target_tenant_id' => ...])`.

#### 3.10 التواريخ في الـ Sidebar hardcoded
- `SystemLayout.vue:587-593`: يستخدم `new Date().toLocaleDateString('ar-SA-u-nu-latn', ...)` بدون احترام timezone (السيرفر) أو hydration SSR.
- **الإصلاح:** نقل التاريخ للـ dashboard header (موجود بالفعل هناك) وحذفه من الـ sidebar.

---

### 🟡 P2 — تحسينات معمارية

#### 3.11 Controllers طويلة بدون Action classes
| Controller | LOC | يستحق Action؟ |
|---|---:|---|
| `TenantsController` | 227 | ✅ خاصة `destroy` و `suspend` |
| `IntegrationsController` | 319 | ✅ 5+ test methods تستحق Service |
| `WebsiteSettingsController` | 229 | ✅ لوح الـ 16 setting |
| `DeveloperController` | 149 | ✅ |
| `TenantsController::destroy` | — | 🔴 **خطير**: forceDelete لـ كل شي بدون أرشفة |

نمط الـ AGENTS.md الحالي يقول "استخدم Action classes" — هذا الـ controller group ما يلتزم.

#### 3.12 `TenantsController::destroy` خطير بدون backup
- `web.php:725` + `TenantsController.php:172-209`: يحذف customer/center/user/tenant نهائياً مع `forceDelete`.
- **ما في:**
  - Archive/backup قبل الحذف
  - Audit log
  - Confirmation email للـ tenant
  - Option "soft delete only" للـ system admin
- **التوصية:** إضافة `archived_at` flag، أو نقل البيانات لـ `tenants_archive` table قبل الحذف.

#### 3.13 `SubscriptInvoices` بدون List/Detail page improvements
- `Invoices/Index.vue` (186 سطر) و `Invoices/Show.vue` (217 سطر) — لم يُفحص بدقة لكن يستحق تنقيح مماثل للـ WorkOrders.

#### 3.14 Developer Center بدون Rate Limiting
- `POST /system/developer/audit` يطلق full codebase scan — ما في throttle.
- **الإصلاح:** `->middleware('throttle:1,30')` (مرة كل 30 ثانية).

#### 3.15 `Auth::guard('admin')` vs `Auth::user()` متناقض
- `ProfileController.php:30`: `Auth::guard('admin')` (صحيح، ده guard الـ admin).
- `TwoFactorController.php:31`: `auth()->user()` (الافتراضي = `web` guard = tenant user، مش admin).
- لو admin سجل دخول بـ `admin` guard ثم انتقل لـ 2FA setup، `auth()->user()` ممكن يرجع null أو user تاني.
- **التشخيص:** `EnsureSystemAdmin` middleware يستخدم كلا الـ guards. الـ inconsistency يخلّي `TwoFactorController` يخفق لو الـ guard مش الافتراضي.
- **الإصلاح:** `TwoFactorController` يجب يستخدم `Auth::guard('admin')->user()` أو يكون explicit عن الـ guard.

#### 3.16 الترجمة في الـ Sidebar hardcoded
- `SystemLayout.vue`: كل labels (الرئيسية، المستأجرين، الإعلانات...) مكتوبة كنص عربي ثابت، مش `$t('nav.tenants')`.
- **الإصلاح:** استخدام i18n keys المتفق عليها من AGENTS.md.

#### 3.17 `form.sponsor.layout` غير موحّد
- بعض الصفحات (Subscriptions, Plans, PromoCodes, AdminUsers) تستخدم `useForm` + Modal inline.
- أخرى (Announcements, Tenants Show, Integrations Show) تستخدم صفحات منفصلة.
- **التوصية:** توحيد النمط: إما كل شي Modal (للـ CRUD البسيط) أو كل شي Page (للـ Show + Edit).

---

## 4. الترتيب المقترح للقائمة الجانبية (تحسين UX)

### الترتيب الحالي (مُرتَّب حسب Sidebar الحالي):
1. الرئيسية
2. الموقع العام: إعدادات الموقع → رسائل الزوار
3. إدارة العملاء: المستأجرين → الاشتراكات → الفواتير → الأقساط
4. إعدادات الخدمة: الباقات → الرموز الترويجية
5. بوابة التواصل: الإعلانات → قوالب التواصل → رصيد SMS → رصيد WhatsApp
6. إدارة النظام: المسؤولين → التكاملات → الإعدادات العامة → إعدادات الدفع → مركز التطوير

### الترتيب المُحسَّن المقترح (حسب تكرار الاستخدام):

| الترتيب | المجموعة | العنصر | السبب |
|---|---|---|---|
| 1 | Overview | الرئيسية | نقطة الدخول |
| 2 | إدارة العملاء | المستأجرين | أعلى استخدام (admin يفتحها 80% من الوقت) |
| 3 | | الاشتراكات | مرتبط مباشرة بالمستأجرين |
| 4 | | الفواتير | تقاطع طبيعي بعد الاشتراكات |
| 5 | | الأقساط | نفس الفئة |
| 6 | المالية | إعدادات الدفع | أكثر استخداماً من "العامة" |
| 7 | التكاملات | رصيد SMS | يحتاج متابعة يومية |
| 8 | | رصيد WhatsApp | نفس الفئة |
| 9 | | التكاملات | إعداد تقني أقل تكراراً |
| 10 | الاتصال | الإعلانات | قد يكون متكرر في حالات الطوارئ |
| 11 | | رسائل الزوار | دخول أقل |
| 12 | إعدادات الخدمة | الباقات | إعداد نادر |
| 13 | | الرموز الترويجية | إعداد نادر |
| 14 | | قوالب التواصل | إعداد نادر |
| 15 | إدارة النظام | المسؤولين | وصول متكرر |
| 16 | | سجل النشاط | debug/support |
| 17 | | الإعدادات العامة | إعداد أولي فقط |
| 18 | | مركز التطوير | استخدام متقدم |
| 19 | الموقع العام | إعدادات الموقع | إعداد نادر |

### التوصية المحدّدة:
- **نقل "المستأجرين" للمركز 2** مباشرة بعد الرئيسية (مش ثالث مجموعة).
- **دمج "إعدادات الدفع" مع "الفواتير"** في مجموعة "المالية".
- **إخفاء "مركز التطوير" خلف role check** (super_admin فقط) — لا يجب أن يظهر لـ admin/support.
- **إضافة Badge مع counter** بجانب "رسائل الزوار" و "الإعلانات غير المنشورة" (موجود جزئياً في الرسائل).

---

## 5. خريطة الصفحات (تقييم الجودة)

| الصفحة | LOC | الجودة | ملاحظات |
|---|---:|---|---|
| `Dashboard.vue` | 378 | ⭐⭐⭐⭐ | ممتاز — provider balances + recent tenants + trial alerts. يحتاج "30 day revenue chart" |
| `Tenants/Index.vue` | 181 | ⭐⭐⭐⭐ | نظيف، filters + table. يحتاج bulk actions |
| `Tenants/Show.vue` | 909 | ⭐⭐ | **ضخم جداً** — يحتاج refactor لمكونات |
| `Subscriptions/Index.vue` | 580 | ⭐⭐⭐⭐ | stats + filters + modal — جيد |
| `Subscriptions/Show.vue` | 238 | ⭐⭐⭐ | مختصر — يحتاج timeline للمدفوعات |
| `Invoices/Index.vue` | 186 | ⭐⭐⭐ | أساسي |
| `Invoices/Show.vue` | 217 | ⭐⭐⭐ | أساسي |
| `Installments/Index.vue` | ~150 | ⭐⭐ | مختصر — يحتاج filters أكثر |
| `Installments/Show.vue` | ~150 | ⭐⭐ | أساسي |
| `Plans/Index.vue` | 629 | ⭐⭐⭐⭐ | grid + modal ذاتي — أنيق |
| `PromoCodes/Index.vue` | 333 | ⭐⭐⭐ | modal ذاتي — جيد |
| `AdminUsers/Index.vue` | 284 | ⭐⭐⭐⭐ | modal ذاتي — roles + permissions |
| `AdminUsers/ActivityLog.vue` | ~100 | ⭐⭐⭐ | مختصر — يحتاج filters أقوى |
| `Integrations/Index.vue` | 169 | ⭐⭐⭐⭐ | grouped by type — أنيق |
| `Integrations/Show.vue` | 261 | ⭐⭐⭐ | dynamic config fields — جيد |
| `Announcements/Index.vue` | ~150 | ⭐⭐⭐ | أساسي |
| `Announcements/Create.vue` | 128 | ⭐⭐⭐ | — |
| `Announcements/Show.vue` | 99 | ⭐⭐ | قصير — يحتاج delivery analytics |
| `Communication/Templates/Index.vue` | ~100 | ⭐⭐ | مختصر |
| `Communication/Templates/Edit.vue` | ~120 | ⭐⭐⭐ | — |
| `Credits/Sms*.vue` (×4) | ~150 each | ⭐⭐⭐ | أساسيات |
| `Credits/Whatsapp*.vue` (×3) | ~150 each | ⭐⭐⭐ | أساسيات |
| `Settings/GeneralSettings.vue` | ~50 | ⭐⭐ | 2FA SMS toggle فقط — **محتوى ضئيل** |
| `Settings/PaymentSettings.vue` | ~250 | ⭐⭐⭐⭐ | 5 gateways — قوي |
| `Website/Index.vue` | 206 | ⭐⭐⭐⭐⭐ | **16 tabs، كل tab ملف مستقل** — أنماط ممتاز |
| `Website/Tabs/*.vue` (×7) | varies | ⭐⭐⭐⭐ | تنظيم نظيف |
| `Profile/Index.vue` | ~150 | ⭐⭐⭐ | 2FA QR + password + profile |
| `Security/TwoFactorSetup.vue` | ~150 | ⭐⭐⭐ | TOTP + SMS options |
| `Security/TwoFactorChallenge.vue` | ~50 | ⭐⭐ | — |
| `Payment/Checkout.vue` | ~150 | ⭐⭐⭐ | moyasar redirect |
| `Payment/Success.vue` | ~50 | ⭐⭐ | — |
| `Payment/Failed.vue` | ~50 | ⭐⭐ | — |
| `ContactMessages/Index.vue` | ~100 | ⭐⭐⭐ | أساسي |
| `Developer/Index.vue` | 315 | ⭐⭐⭐ | **i18n مختلط EN/AR** + tabs 4 + AI mock |

---

## 6. الـ Middleware & Security

| Middleware | يعمل؟ | ملاحظات |
|---|---|---|
| `EnsureSystemAdmin` | ✅ | منطقي صحيح، يستخدم `admin` guard + `is_system_admin` flag |
| `EnsureTwoFactorEnabled` | ✅ | للـ tenant users |
| `EnsureTenantActive` | ✅ | للـ tenant access |
| `EnsureCenterContext` | ✅ | للـ center-scoped routes |
| `SetLocale` | ✅ | ar/en |
| `ConvertArabicNumerals` | ✅ | helper |
| `SentryContext` | ✅ | breadcrumb |
| `TrackAiUsage` | ✅ | للـ AI features |
| `PreventBackHistory` | ✅ | cache headers |

**مشكلة:** `EnsureSystemAdmin` مطبّق على `/system/*` group — جيد. لكن `/2fa/challenge` و `/impersonate/stop` خارج الـ group، يستخدمان `web` middleware فقط (أقل أماناً).

---

## 7. خارطة طريق مقترحة (Roadmap)

### Phase A — إصلاحات حرجة (يوم واحد)
1. ✅ حذف routes `create/edit` من Plans و PromoCodes (أو إنشاء `Form.vue`).
2. ✅ توحيد `Auth::guard` في `TwoFactorController`.
3. ✅ إضافة `throttle:1,30` لـ `POST /system/developer/audit`.

### Phase B — تنظيف Layout + i18n (يومان)
4. استخراج قائمة nav من `SystemLayout.vue` (إزالة التكرار 540 سطر).
5. i18n كامل لـ `Developer/Index.vue` + 7 نصوص hardcoded في الـ Sidebar.
6. Refactor `Tenants/Show.vue` (909 سطر) → 5 مكونات.

### Phase C — تحسينات معمارية (3 أيام)
7. تقسيم `IntegrationsController` (319 سطر) → Service + Action.
8. تقسيم `TenantsController::destroy` → `ArchiveTenantAction` + backup table.
9. إضافة audit log في `ImpersonationController::start/stop`.
10. استبدال `DeveloperController::getGraph` و `aiAdvice` بنسخة حقيقية.

### Phase D — UX Polish (يومان)
11. ترتيب Sidebar الجديد (انظر القسم 4).
12. توحيد نمط CRUD (Modal vs Page).
13. Badge counters (إعلانات غير منشورة، tenants trial).
14. Date timezone + SSR hydration.

### Phase E — محتوى مفقود (يومان)
15. `Settings/GeneralSettings.vue` — إضافة 5-7 settings (timezone, currency, etc.) بدل 1 فقط.
16. `Installments/Index.vue` — إضافة filters + export CSV.
17. `Invoices/Index.vue` — إضافة status filter + export.

---

## 8. ملخص الأرقام

| المؤشر | القيمة |
|---|---:|
| عدد الـ Controllers في `System/` | **24** |
| عدد الـ Routes تحت `/system/*` | **~100** |
| عدد صفحات Vue في `Pages/System/` | **39 ملف** |
| إجمالي سطور SystemLayout.vue | **1,409** |
| إجمالي سطور Tenants/Show.vue | **909** |
| صفحات بها modal ذاتي (لا تحتاج Form.vue) | **4** (Plans, PromoCodes, AdminUsers, Integrations Index) |
| صفحات مفقودة (Form.vue لـ P0) | **2** (Plans, PromoCodes) |
| i18n hardcoded في Layout | **~22 نص** |
| Mocked data في Controllers | **2** (Developer getGraph + aiAdvice) |
| Controllers بدون Action class | **18/24** (75%) |

---

## 9. الخلاصة

اللوحة **وظيفية إلى حد بعيد** وتغطي العمليات الحرجة (إدارة المستأجرين، الاشتراكات، الفواتير، الإعلانات، الإعدادات). لكن:

1. **أكبر مشكلة فورية:** الـ Routes لـ Plans/PromoCodes create/edit مكسورة (Form.vue مفقود).
2. **أكبر مشكلة طويلة المدى:** `Tenants/Show.vue` و `SystemLayout.vue` يحتاجان refactor لتقليل التكرار والاقتراب من نمط `WorkOrders/Show.vue` المتّفق عليه في AGENTS.md.
3. **أقل أولوية:** i18n الـ Developer Center و mocked data في DeveloperController.

ابدأ بـ **Phase A** (يوم واحد) لإصلاح الـ 500/404، ثم **Phase B** (يومان) لتحسين قابلية الصيانة.
