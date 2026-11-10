# تقرير المقارنة التفصيلي: wrshah.com ↔ carag-v2
## تاريخ المراجعة: 2026-11-08
## عيّنة wrshah: مركز فريق الخدمة (7a1daac6f0@emailnox.live)

---

## أولاً: البنية التقنية الأساسية

| المعيار | wrshah.com | carag-v2 |
|---|---|---|
| **الواجهة الأمامية** | React + Material-UI + jQuery + Bootstrap | Vue 3 (Composition API) + Inertia.js + Tailwind CSS |
| **إدارة الحالة** | React hooks فقط | Vue 3 ref/computed/watch |
| **بناء الحزم** | Webpack (chunk splitting) | Vite |
| **الأيقونات** | Material Icons + Font Awesome | Heroicons (SVG inline) |
| **التعريب** | i18n (推测) | `vue-i18n` مع RTL تلقائي |
| **القوالب المطبوعة** | غير واضح | PrintEngine موحد + 4 قوالب متعددة الأحجام |
| **عدد النماذج الخلفية** | ~50 نموذج (推测) | **95+ نموذج** |
| **صفحات الواجهة الأمامية** | ~25 صفحة | **80+ صفحة Vue** |
| **ملفات الكومبوننت** | غير معروف | **120+ ملف Vue** |
| **سطور الكود** | ~50K سطر (推测) | **~140K سطر Vue** |

---

## ثانياً: مقارنة شاملة للوحدات / الأقسام

### 📌 وحدة لوحة التحكم (Dashboard)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| عدد KPI Cards | 8 بطاقات (طلب/صيانة مفتوحة/مكتمل/تجاوز/خروج سيارة/مستحق دفع/فاتورة/تجاوز) | 6+ بطاقات ديناميكية + Charts | تعادل |
| الرسوم البيانية | ❌ لا يوجد | ✅ RevenueBarChart, WeeklyBarChart, StatusBreakdown | carag-v2 |
| أداء الفنيين | ❌ لا يوجد | ✅ TechnicianPerformance widget | carag-v2 |
| جدول الأعمال اليومي | ❌ لا يوجد | ✅ TodaySchedule widget | carag-v2 |
| الخدمات الأعلى | ❌ لا يوجد | ✅ TopServices widget | carag-v2 |
| العملاء الأفضل | ❌ لا يوجد | ✅ TopCustomers widget | carag-v2 |
| الإشعارات الذكية | ✅ قائمة إشعارات جانبية | ✅ NotificationBell + Toast | تعادل |
| Quick Actions | ✅ SpeedDial عائم | ⚠️ QuickActions widget (ليس floating) | wrshah.com |
| تنسيق العرض | بطاقة خضراء/زرقاء مع أيقونات كبيرة | cards بيضاء حديثة مع trend badges | carag-v2 |
| Refresh يدوي | ✅ زر في الهيدر | ✅ زر refresh في الصفحة | تعادل |

### 📌 وحدة كروت العمل (Work Orders)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| صفحة الفهرس | `/requests` (page not found في العينة) | `WorkOrders/Index.vue` (1836 سطر) | carag-v2 |
| تفاصيل الكرت | موجود | `WorkOrders/Show.vue` (قوة متقدمة) | carag-v2 |
| حالات الكروت | basic statuses | 6+ حالات مع Workflow متكامل | carag-v2 |
| إدارة القطع | ✅ في الكرت | ✅ WorkOrderPartsController + models | تعادل |
| إدارة العمالة | ✅ في الكرت | ✅ WorkOrderLaborEntry + TechnicianController | carag-v2 |
| التقديرات الضوئية | ❌ لا يوجد | ✅ WorkOrderInspection + Signature | carag-v2 |
| تتبع الصور/المedia | ❌ لا يوجد | ✅ WorkOrderMediaController | carag-v2 |
| الطباعة المتعددة | ❌ نوع واحد | ✅ 4 قوالب طباعة (Condition/Payments/Proforma/Services) | carag-v2 |
| Workshop Board | ❌ لا يوجد | ✅ `Workshop/Board.vue` — لوحة مرئية | carag-v2 |

### 📌 وحدة التقييمات (Reviews/Ratings)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| صفحة التقييمات | ✅ موجودة في القائمة الجانبية | ✅ `Feedback/Index.vue` | تعادل |
| تقييم العملاء | أساسي | ✅ نظام feedback متكامل مع تصنيف | carag-v2 |
| رد المدير | غير واضح | ✅ Dashboard + SupportHub مع تصنيف تلقائي | carag-v2 |

### 📌 وحدة الفواتير (Invoices)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| الفواتير المباعة | ✅ `الفواتير` | `Invoices/Hub.vue` + Show (1782 سطر) | carag-v2 |
| ZATCA E-Invoicing | ✅ داخل ملف الشركة | ✅ IntegrationManager + ZatcaOnboardingController | carag-v2 |
| الفواتير المشتريات | ❌ غير ظاهر | ✅ `Purchasing/Invoices/Show.vue` (2912 سطر) | carag-v2 |
| الإيصالات البنكية | ❌ غير واضح | ✅ BankTransferReceipts Controller | carag-v2 |
| الدفع الجزئي | ❌ | ✅ SystemPaymentManager (دفع متعدد) | carag-v2 |

### 📌 وحدة المركبات (Vehicles)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| صفحة المركبات | ✅ `المركبات` | `Vehicles/Index.vue` + Show (2250 سطر) | carag-v2 |
| حالة المركبة | basic | ✅ VehicleConditionCategory + VehicleConditionItem | carag-v2 |
| ألوان السيارات | ✅ إعداد النظام | ✅ VehicleColor Model + Controller | carag-v2 |
| الماركة/الموديل | ✅ كاتالوج قطع غيار | ✅ VehicleMake + VehicleModel Models | carag-v2 |
| سجل المسافات | ❌ غير واضح | ✅ VehicleMileageController + VehicleMileageLog Model | carag-v2 |
| مسح/duplicate detection | ❌ | ✅ VehicleMergeController | carag-v2 |

### 📌 وحدة العملاء (Customers)

| الميزة | wrshah.com | carag-v2 |_FREQ### 📌 وحدة العملاء (Customers)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| صفحة العملاء | ✅ `العملاء` | `Customers/Index.vue` + Show (2641 سطر) | carag-v2 |
| دمج العملاء المكررين | ❌ | ✅ CustomerMergeController | carag-v2 |
| استيراد/تصدير | ❌ | ✅ CustomerImportExportController | carag-v2 |
| طباعة بيان الحساب | ❌ | ✅ PrintStatement.vue | carag-v2 |
| صلاحيات الوصول | ❌ | ✅ CustomerPortal + Permissions | carag-v2 |

### 📌 وحدة الشركاء (Partners)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| صفحة الشركاء | ✅ `الشركاء` في القائمة | ⚠️ غير واضح (لم يُعثَر على Partner Model) | wrshah.com |
| إدارة العقود | - | ✅ EmployeeContracts Controller | carag-v2 |

### 📌 وحدة الموردين (Suppliers)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| صفحة الموردين | ✅ `الموردين` | `Purchasing/Suppliers/Show.vue` (2236 سطر) | carag-v2 |
| أوامر الشراء | ❌ | ✅ PurchaseOrder + PurchaseOrderItem Controllers | carag-v2 |
| فواتير المشتريات | ❌ | ✅ PurchaseInvoice + PurchaseReturnInvoice | carag-v2 |
| إيصالات الاستلام | ❌ | ✅ GoodsReceivedNotes + GrnItem | carag-v2 |

### 📌 وحدة إدارة المتجر (Store Management)

| الميزة | wrshah.com | carag-v2 | الفائz |
|---|---|---|---|
| وجود الوحدة | ✅ `إدارة المتجر` في القائمة | ⚠️ Inventory module موجود لكن بدون واجهة متجر واضحة | wrshah.com |
| إدارة المخزون | ❌ | ✅ InventoryBalance, InventoryMove, InventoryTransfer | carag-v2 |
| وحدات القياس | ✅ وحدات المخزون | ✅ InventoryUnit Model | carag-v2 |
| حركة المخزون | ❌ | ✅ InventoryMove + Transfer systems | carag-v2 |

### 📌 unit الموارد البشرية (HR)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| وجود الوحدة | ✅ `شؤون الموظفين` | ✅ HR/Index.vue + 10+ controllers فرعية | تعادل |
| الحضور والانصراف | ✅ | ✅ Attendance + BiometricDevice | carag-v2 |
| الرواتب | ❌ غير واضح | ✅ Payroll + PayrollRun + PayrollItem | carag-v2 |
| الإجازات | ❌ | ✅ LeaveController | carag-v2 |
| العقود | ❌ | ✅ EmployeeContracts | carag-v2 |
|_shifts | ❌ | ✅ EmployeeShift + Shift Model | carag-v2 |
| البدلات والخصومات | ✅ علاوات/حسومات في الإعدادات | ✅ Allowance + Deduction Models | carag-v2 |
| الوثائق | ❌ | ✅ EmployeeDocuments | carag-v2 |
| الصلاحيات | ❌ | ✅ EmployeePermissions | carag-v2 |
| البوابة الذاتية | ❌ | ✅ EmployeePortal/Dashboard, MyTasks, Payslips | carag-v2 |

### 📌 وحدة التقارير (Reports)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| وجود الوحدة | ✅ `التقارير` | ✅ Reports/Hub.vue مع 10+ تقارير | تعادل |
| أنواع التقارير | limited | FinancialSummary, Services, Suppliers, Vehicles, Employees, StockActivities, PurchaseInvoices, CashRegister, TaxPeriods | carag-v2 |
| تصدير PDF | ❌ | ✅ كل التقارير تدعم التصدير | carag-v2 |
| تحليلات النظام | ❌ | ✅ AnalyticsHub + KPIs + ReportsTab | carag-v2 |

### 📌 وحدة إدارة النظام (System Administration)

| الميزة | wrshah.com | carag-v2 | الفائز |
|---|---|---|---|
| عدد الأقسام | 4 أقسام فقط | **22+ قسم** في نظام Nav | carag-v2 |
| ملف الشركة | ✅ شامل | ✅ CompanyProfile + TransactionController | تعادل |
| مراكز الصيانة | ✅ basic | ✅ Centers + Branches + WorkingHours | carag-v2 |
| إعدادات النظام | ✅ 15 تبويب | ✅ SettingsHub + GeneralSettings + PaymentSettings + Dunning | carag-v2 |
| كاتالوج قطع الغيار | ✅ page كاملة | ⚠️ Part model موجود لكن بدون كتالوج vehicle-specific | wrshah.com |
| Super Admin Dashboard | ❌ غير موجودة | ✅ لوحة ضخمة متكاملة | carag-v2 |
| إدارة المستأجرين | ❌ | ✅ Tenants/Index + Show مع Health Score | carag-v2 |
| المستخدمين والمشرفين | ❌ | ✅ AdminUsers + ActivityLog | carag-v2 |
| الخطط والاشتراكات | ✅ basic | ✅ Plans + Subscriptions + Invoices + Payments + PromoCodes | carag-v2 |
| التكاملات | ❌ | ✅ Integrations (ZATCA, SMS, WhatsApp, Banking) | carag-v2 |
| أرصدة SMS/WhatsApp | ❌ | ✅ Credits Hub (Sms + Whatsapp مع 4 tabs لكل منها) | carag-v2 |
| النسخ الاحتياطي | ❌ | ✅ BackupController | carag-v2 |
| بناء الموقع | ❌ | ✅ Website Manager (6 tabs) | carag-v2 |
| مركز المساعدة | ❌ | ✅ Help Center + SupportHub | carag-v2 |
| إدارة الملاحظات والبلاغات | ❌ | ✅ FeedbackManagement + ContactMessages | carag-v2 |
| الدفتر Yومية | ❌ | ✅ CompanyTransaction + System Announcements | carag-v2 |

---

## ثالثاً: الفروقات البرمجية التفصيلية

### 1. SpeedDial Float Button (زر عائلي)

**wrshah.com:**
```javascript
// MUI SpeedDial Implementation
<div className="MuiSpeedDial-root MuiSpeedDial-directionLeft">
  <button className="MuiFab-root MuiFab-sizeSmall MuiFab-primary">
    {/* Plus → Close animation */}
  </button>
  <div className="MuiSpeedDial-actions MuiSpeedDial-actionsClosed">
    <button title="إضافة تذكير"><i class="fa fa-calendar-check"></i></button>
    <button title="الطلب"><i class="fa fa-clipboard"></i></button>
    <button title="تقييم"><i class="fa fa-star"></i></button>
    <button title="المركبة"><i class="fa fa-car"></i></button>
    ...
  </div>
</div>
```
**الموقع:** fixed bottom-right, left:24px, top:~722px, size: 40×40px, borderRadius: 50%

**carag-v2 الحالي:**
- يوجد `QuickActions.vue` لكنه widget عادي في OverviewTab (grid layout)
- لا يوجد عنصر floating/fixed

**الإجراء المقترح:**
- إنشاء `FloatingSpeedDial.vue` جديد
- إدراجه في `AppLayout.vue` مع z-index عالي
- دعم RTL (فتح لليسار في العربية)

---

### 2. Subscription Warning Banner

**wrshah.com:**
```html
<div class="alert alert-warning alert-dismissible">
  اشتراكك سينتهي خلال 15 أيام. لتجديد اشتراكك
  <a href="#">اضغط هنا</a>
  <button type="button" class="close">×</button>
</div>
```
**الموقع:** أعلى المحتوى الرئيسي مباشرة، color: أصفر/برتقالي satura

**carag-v2 (AppLayout.vue:2291-2374):**
```vue
<div v-if="showExpirationNotice"
     :class="isSubscriptionExpired ? 'bg-rose-500/10' : 'bg-amber-500/10'">
  <!-- opacity 10% فقط = باهت جداً -->
</div>
```
**المشكلة:** اللون باهت (10% opacity)، ولا يوجد زر إغلاق (dismiss)
**الإجراء:** تعديل الألوان + إضافة dismiss functionality

---

### 3. Welcome Message

**wrshah.com:** داخل sidebar chat module
```html
<div class="chat-item-body">
  مرحباَ بكم في ورشة.كوم! لمزيد من التفاصيل ،
  <a href="#subscription">انقر هنا</a>
</div>
```

**carag-v2:** داخل PageHeader في Dashboard فقط
```javascript
const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return t('dashboard.greeting_morning');
    ...
});
// عرض: "صباح الخير، أحمد" في subtitle فقط
```

**الإجراء:** إضافة welcome banner يظهر في كل الصفحات

---

### 4. Breadcrumbs

**wrshah.com:** موجود في كل صفحة
```html
<nav class="breadcrumb">
  <a href="/">🏠</a> › 
  <a href="/administration">إدارة النظام</a> › 
  <span>ملف الشركة</span>
</nav>
```

**carag-v2:** Component موجود لكن non-existent usage
- `Breadcrumb.vue` موجود في Components
- Grep shows zero usage in pages

**الإجراء:** إضافة `<Breadcrumb>` إلى Dashboard و System pages

---

## رابعاً: مميزات carag-v2 الفريدة (غير موجودة في wrshah.com)

### 1. Multi-Tenancy كامل
- Isolation تام بين المستأجرين (TenantScoped, CenterScoped)
- Super Admin Dashboard لإدارة مئات المستأجرين
- TenantWebsite Builder — كل مستأجر له موقع خاص

### 2. نظام الصلاحيات المتقدم
- Policies + Gates server-side
- Role-based access control
- Impersonation (دخول لحساب مستأجر بصفتك مشرف)

### 3. ZATCA E-Invoicing متكامل
- Onboarding flow
- XML generation
- QR code embedding
- Compliance reporting

### 4. نظام الدفع متعدد المراحل
- Moyasar payment gateway integration
- Bank transfer receipt upload
- Dunning management (تتبع محاولات التحصيل)
- Promo codes system

### 5. نظام SMS & WhatsAppcredits
- SmsPackages + SmsPurchases + SmsUsage
- WhatsappPackages + WhatsappBalances + WhatsappUsage
- Communication Templates

### 6. لوحة Workshop Board مرئية
- `Workshop/Board.vue` — عرض كروت العمل كبطاقات بصرية
- sortable by status, technician, date

### 7. نظام الطباعتموحد
- `PrintEngine.vue` component موحد
- 4 templates: DefaultA4, Thermal80, Condition, Payments
- `usePrint()` composable

### 8. Command Palette (Cmd+K)
- بحث شامل عبر كل النظام
- shortcut keyboard global

### 9. تحليلات متقدمة
- RevenueBarChart, WeeklyBarChart
- StatusBreakdown, TechnicianPerformance
- AnalyticsHub مع KPIs و ReportsTab

### 10. NBackup System
- `TenantBackup` model
- `BackupController` with restore capability

### 11. Employee Self-Service Portal
- `EmployeePortal/Dashboard.vue`
- `MyTasks`, `Attendance`, `Payslips`, `Leaves`, `Profile`

### 12. System Announcements
- `SystemAnnouncement` model
- Per-tenant announcements
- Dismiss functionality

---

## خامساً: نقاط الضعف في wrshah.com (مقابل carag-v2)

| النقطة | التأثير |
|---|---|
| لا يوجد نظام أدوار | أي مستخدم يري كل شيء — خطر أمني |
| لا يوجد multi-tenancy visible | لا يمكن تشغيله كمنصة SaaS حقيقية |
| لا توجد رسوم بيانية | صعوبة اتخاذ قرارات مبنية على بيانات |
| لا يوجد backup system | خطر فقدان البيانات |
| لا يوجد dunning/collection system | صعوبة تحصيل الفواتير المتأخرة |
| لا يوجد self-service portal | الاعتماد على الدعم المباشر |
| no advanced search | البحث محدود |
| لا يوجد website builder | كل مستخدم يحتاج موقع خارجي |

---

## سادساً: التوصيات النهائية

### ✅ يجب إضافته من wrshah.com:

| # | الميزة | الجهد | الأولوية |
|---|---|---|---|
| 1 | Floating SpeedDial Button | متوسط | 🔴 عالية |
| 2 | Subscription Warning تحسين CSS | سهل | 🔴 عالية |
| 3 | Welcome Message Banner | سهل | 🟡 متوسطة |
| 4 | Breadcrumbs تفعيل | سهل | 🟡 متوسطة |
| 5 | Vehicle Parts Catalog | صعب | 🟢 منخفضة |

### ⚠️ يجب تحسينه في carag-v2:

| # | الميزة | التعديل |
|---|---|---|
| 1 | شريط الاشتراك | زيادة saturation + إضافة dismiss button |
| 2 | Status Badges | إضافة counters على sidebar items |
| 3 | QuickActions | تحويله إلى floating button |

### 🚫 لا داعي للإضافة (لدينا ما هو أفضل):

| الميزة السبب |
|---|
| Advanced Search | CommandPalette أقوى بكثير |
| Status Badges on nav | QuickActions يكفي |
| Filter chips in header | Not needed — we have better alternatives |

---

## سابعاً: خلاصة الأداء

| المعيار | wrshah.com | carag-v2 | الفرق |
|---|---|---|---|
| عدد النماذج الخلفية | ~50 | **95+** | +90% |
| عدد صفحات Vue | ~25 | **80+** | +220% |
| عدد ملفات الكومبوننت | غير معروف | **120+** | --- |
| سطور الكود | ~50K | **~140K** | +180% |
| وحدات النظام | 8 وحدات أساسية | **15+ وحدة** | +87% |
| Super Admin Panel | ❌ | ✅ كامل | فارق جذري |
| Multi-Tenancy | جزئي | ✅ كامل | فارق جذري |
| Security (Roles/Policies) | ❌ | ✅ كامل | فارق جذري |
| Printing System | basic | ✅ موحد ومتعدد القوالب | تفوق carag-v2 |
| Dashboard Analytics | أساسي | ✅ متقدم مع charts | تفوق carag-v2 |
| UX Polish | ✅ بسيط وأنيق | ⚠️ جيد لكن يحتاج تحسينات | تفوق wrshah.com |
| **التقييم العام** | **نظام ورشة SaaS مبسط** | **ERP Enterprise-grade** | carag-v2 يفوق بمراحل |
