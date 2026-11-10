# تقرير مقارنة لوحة النظام: wrshah.com ↔ carag-v2
## تاريخ المراجعة: 2026-11-08
## عيّنة wrshah: مركز فريق الخدمة (7a1daac6f0@emailnox.live)

---

## أولاُ: هيكل إدارة النظام في wrshah.com

### مسار الوصول
القائمة الجانبية ← **إدارة النظام** → `/administration`

### الصفحات الفرعية (4 أقسام فقط):
| القسم | المسار | المحتوى |
|---|---|---|
| ملف الشركة | `/administration/companyProfile` | معلومات عامة / VAT / ZATCA / تواصل / اسم المستخدم / اشتراك / سجل الاشتراكات / إيرادات وإدارة النفقات |
| مراكز الصيانة | `/administration/shops` | قائمة المراكز (مركز واحد معروض) |
| كاتالوج قطع الغيار | `/administration/sparePartsCatalog` | فلترة بالماركة/الموديل/السنة/المتجر/الفئة/رقم القطعة + زر إضافة |
| إعدادات النظام | `/administration/systemSettings` | 15 تبويب (بدلات، ألوان سيارات، خصومات، ماركات، موديلات، أنواع تذكير، أدوار فنيي خدمة، شروط وأحكام، بادئات واتساب، عناوين وثائق، فئات حساب الدخل، فئات قطع غيار، مسمى وظيفي، نموذج حالة مركبة，نوع موظف، وحدات مخزون، إعدادات أخرى) |

### نظام التحكم:
- **نوع الحساب**: SaaS أحادي المستأجر (كل مستخدم يتعامل مع مركزه الوحيد)
- **الصلاحيات**: لا يوجد نظام أدوار مفصّل — المستخدم صاحب المركز له صلاحية كاملة على كل شيء
- **تعدد المراكز**: موجود نظرياً لكن الحساب التجريبي يحتوي مركزاَ واحداً فقط
- **لوحة تحكم النظام العليا**: غير متوفرة — لا توجد لوحة "Super Admin" لإدارة مستخدمين متعددين أو خطط اشتراك

---

## ثانياُ: هيكل لوحة النظام في carag-v2

### مسار الوصول
القائمة الجانبية ← **الإعدادات** → `/app/settings` (للمستأجر)
أو `/system` (لـ Super Admin)

### المكونات الحالية:

#### أ) لوحة System Admin (للمشرف الأعلى — `/system`)
| القسم | الملف | المحتوى |
|---|---|---|
| Dashboard | `System/Dashboard.vue` | نظرة عامة على النظام |
| Tenants | `Tenants/Index.vue` + `Show.vue` | قائمة المستأجرين + عرض تفصيلي لكل مستأجر |
| Users | `AdminUsers/Index.vue` + `ActivityLog.vue` | إدارة المستخدمين وسجل النشاط |
| Plans & Subscriptions | `Plans/Index.vue`, `Subscriptions/Index.vue`, `Create.vue`, `Show.vue` | الخطط والاشتراكات |
| Invoices | `Invoices/Index.vue`, `Show.vue` | الفواتير المالية |
| Payments | `Payment/Checkout.vue`, `Success.vue`, `Failed.vue` | بوابة الدفع |
| Settings Hub | `Settings/SettingsHub.vue` | 4 تبويبات (Company / General / Dunning / Payment) |
| Analytics | `Analytics/AnalyticsHub.vue`, `Kpis.vue`, `Tabs/*.vue` | التحليلات والتقارير |
| Announcements | `Announcements/Index.vue`, `Create.vue`, `Show.vue` | الإعلانات العامة |
| Communication Templates | `Communication/Templates/Index.vue` + `Edit.vue` | قوالب الاتصالات |
| Integrations | `Integrations/Index.vue`, `Show.vue` | التكاملات (ZATCA, SMS, WhatsApp...) |
| Credits Hub | `Credits/SmsHub.vue`, `WhatsappHub.vue` + tabs فرعية | أرصدة SMS و WhatsApp |
| Promo Codes | `PromoCodes/Index.vue` | رموز الخصم |
| Feedback Management | `Feedbacks/Index.vue` | إدارة ملاحظات العملاء |
| Contact Messages | `ContactMessages/Index.vue` | رسائل التواصل |
| Support Hub | `Support/SupportHub.vue` مع 3 تبويبات | دعم العملاء |
| Help Center | `Help/Index.vue` | مركز المساعدة |
| Security | `Security/TwoFactorSetup.vue`, `TwoFactorChallenge.vue` | الأمانثنائي |
| Developer | `Developer/Index.vue` | أدوات المطور |
| Bank Transfer Receipts | `BankTransferReceipts/Index.vue`, `Show.vue` | إيصالات التحويل البنكي |
| Backups | `Backups/Index.vue` | النسخ الاحتياطي |
| Website Manager | `Website/Index.vue` مع 6 تبويبات | General, Landing, Pages, SEO/Social, Lists, Integrations |
| Profile | `Profile/Index.vue` | الملف الشخصي للمشرف |

#### ب) لوحة Tenant Settings (للمستأجر — `/app/settings`)
الملفات الموجودة تشمل:
- `Settings/Branches/BranchFormModal.vue`
- `Settings/Centers/Show.vue`
- `Settings/Company/Index.vue`
- `Settings/Website.vue`
- `Settings/Integrations.vue`

---

## ثالثاُ: المقارنة التفصيلية

### 1. نطاق الصلاحيات

| الميزة | wrshah.com | carag-v2 |
|---|---|---|
| Multi-Tenant | نعم (SaaS) لكن بدون لوحة Super Admin مرئية | نعم مع لوحة Super Admin كاملة |
| User Roles | لا يوجد (مستخدم واحد = صاحب المركز) | نظام صلاحيات كامل (Policy/Gate) |
| Center Switching | موجود (زر في الهيدر) | موجود (+ تبديل Top/Side Nav) |
| Impersonation | غير ظاهر | متاح عبر `ImpersonationController` |
| Super Admin Dashboard | غير موجودة | موجودة بكاملها |

### 2. الإدارة المالية والاشتراكات

| الميزة | wrshah.com | carag-v2 |
|---|---|---|
| صفحة الاشتراك | داخل ملف الشركة (تبويب "الاشتراك") | لوحات منفصلة: Plans + Subscriptions + Invoices + Payments |
| تتبع الإيرادات | تبويب "الإيرادات / النفقات الإدارة" داخل ملف الشركة | System Invoices + Bank Transfer Receipts + Promo Codes |
| بوابة دفع | ربط مباشر من صفحة الاشتراك | `Payment/Checkout` مع Success/Failed pages |
| SaaS Billing | بسيط (نطاق واحد) | متعدد المستويات (plans, invoices, dunning) |

### 3. الإعدادات التشغيلية

| الميزة | wrshah.com | carag-v2 |
|---|---|---|
| إعدادات متعددة التبويبات | 15 تبويب في صفحة واحدة | مقسّمة عبر Settings Hub + صفحات منفصلة |
| كاتالوج قطع الغيار | صفحة متخصصة مع فلترة متقدمة (ماركة/موديل/سنة/متجر/فئة/رقم قطعة) | موجود ضمن Inventory模块 لكن دون كتالوج vehicle-specific |
| المراكز/الفرع | صفحة واحدة لمراكز الصيانة | Centers + Branches مع modals منفصلة |
| القوالب والتواصل | بادئات واتساب فقط | Communication Templates شاملة |
| تكامل ZATCA | ضمن ملف الشركة (تبويب ZATCA) | ضمن Integrations + Settings |

### 4. التحليلات والتقارير

| الميزة | wrshah.com | carag-v2 |
|---|---|---|
| Dashboard تحليلي | KPI cards أساسية فقط (0 في العيّنة) | 4 Tabs (Overview/Financial/Operations/Alerts) + Charts + Technician Performance |
| تقارير مخصصة | غير ظاهر | Reports Hub مع تصدير PDF |
| Analytics للوحة النظام | لا يوجد | AnalyticsHub + KPIs + ReportsTab |

### 5. الدعم والمساعدة

| الميزة | wrshah.com | carag-v2 |
|---|---|---|
| زر المساعدة | ` help` في الهيدر | Help Page متكامل |
| بلاغات المشاكل | ` feedback` في الهيدر | FeedbackManagement + ContactMessages |
| تذاكر الدعم | غير ظاهر | SupportHub مع تصنيف تلقائي |

### 6. الميزات الجمالية والتجربة (UI/UX)

| الميزة | wrshah.com | carag-v2 |
|---|---|---|
| SpeedDial (زر عائلي) | موجود | QuickActions widget (ليس floating) |
| Subscription Expiry Banner | شcribes أصفر واضح | SystemAnnouncementBanner (عام أكثر) |
| Welcome Message | "مرحباَ بكم في ورشة.(bbox!" | لا يوجد tرحيب ديناميكي |
| Breadcrumbs | موجود في كل صفحة إدارة | موجود جزئياُ |
| Notification Bell + Count | جرسة + عداد + "مسح الكل" | NotificationBell component موجود |
| Advanced Search | رابط منفصل "بحث متقدم" | CommandPalette (Cmd+K) — أفضل |
| Dark Mode | غير ظاهر | Toggle كامل |
| Language Toggle | English link في الأعلى | زر English/العربية في الهيدر |
| Responsive | Mobile sidebar hamburger | Mobile sidebar + hamburger |

---

## رابعاُ: ما يمتلكه carag-v2 ولا يملكه wrshah.com

### مزايا فوّقة:
1. **Super Admin Dashboard** كاملة — إدارة مئات المستأجرين
2. **نظام صلاحيات أدوار** (Policies + Gates) — wrshah.com لا يملكه
3. **Multi-Tenancy حقيقي** — isolation تام بين المستأجرين
4. **لوحة Analytics متقدمة** — رسوم بيانية + KPI trends
5. **نظام Dunning** — تتبع محاولات تحصيل الفواتير المتأخرة
6. **نظام Promo Codes** — رموز خصم للاشتراكات
7. **إدارة Backup** - نسخ احتياطي للنظام
8. **Website Builder** — بناء مواقع المستأجرين من الداخل
9. **Integration Manager** — تكامل ZATCA, SMS, WhatsApp, Banking
10. **Impersonation** — دخول لحساب مستأجر بصفتك مشرف
11. **Two-Factor Authentication** — أمان إضافي
12. **Command Palette (Cmd+K)** — بحث شامل

### ما ينقص carag-v2 لمساواة wrshah.com:
1. **SpeedDial Float Button** — زر دائري عائم للوصول السريع (مشروع QuickActions الحالي ليس نفس التجربة)
2. **Subscription Expiry Banner** — تنبيه محدد لانتهاء الاشتراك
3. **Welcome Dynamic Message** — رسالة ترحيب ديناميكية للمستخدم
4. **Vehicle Parts Catalog** — كتالوج قطع غيار مرتبط بالماركة/الموديل (مثل wrshah.com)
5. **Advanced Global Search** — صفحة بحث مستقل مثل "بحث متقدم"
6. **Breadcrumb navigation** — في بعض صفحات النظام الداخلية

---

## خامساُ: خلاصة الحكم

| المعيار | winner |
|---|---|
| تعقيد النظام وقوته | carag-v2 (فارق شاسع) |
| تعدد المستأجرين | carag-v2 (لوحة Super Admin كاملة) |
| البساطة وسهولة الاستخدام | wrshah.com (أقل تعقيداُ) |
| لوحة النظام الإدارية | carag-v2 (أعلى بمراحل) |
| تجربة المستخدم UI | تعادل — كل واحد أسلوب مختلف |
| المزايا الجمالية | wrshah.com له مميزات بسيطة جذابة (SpeedDial, banners) |

**التقييم العام**:
- carag-v2 هو **نظام Enterprise-grade SaaS** بقدرات Super Admin تتجاوز بكثير ما يقدمه wrshah.com
- wrshah.com نموذج **ورشة فردية مبسّطة** — ممتازة للمستخدم النهائي لكنها أضعف تقنياُ
- carag-v2 يحتاج إضافة مزايا UX بسيطة من wrshah.com (SpeedDial, subscription banners, welcome message, vehicle parts catalog) ليكتمل جمالياُ وعملياُ
