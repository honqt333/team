# تحليل كود wrshah.com التقني (من فحص المتصفح الفعلي)
## تاريخ التحليل: 2026-11-08
## المنهجية: فحص DOM + Network + React Fiber + globalConfig + API endpoints

---

## أولاً: البنية التقنية المكتشفة

### 1.1 Stack التقني
| العنصر | القيمة المكتشفة |
|---|---|
| **Library** | React 18 (من `ReactDOM.createRoot`) |
| **UI Framework** | Material-UI (MUI v5) — `MuiSpeedDial`, `MuiFab`, `MuiButton` |
| **CSS Framework** | Bootstrap 4.6 (`navbar`, `card`, `collapse`, `dropdown`) |
| **Build Tool** | Webpack 5 (chunk splitting: `main.xxx.chunk.js`, `1.xxx.chunk.js`) |
| **Icons** | Font Awesome 5 + Material Icons |
| **State Management** | React Context (من `Provider` في Fiber tree) |
| **Routing** | React Router (`Route`, `MenuContextProvider`) |
| **API Layer** | REST endpoints على `/data/api` |

### 1.2 globalConfig (من `window.globalConfig`)
```javascript
{
  "apiUrl": "/data/api",
  "host": "https://app.wrshah.com",
  "defaultPageSize": "15",
  "defaultTimeZoneWrtGMT": "3",        // Saudi Arabia (GMT+3)
  "defaultLanguage": "ar",
  "defaultCompanyName": "wrshah.com",
  "paymentWidgetURL": "https://oppwa.com/v1/paymentWidgets.js",
  "mapAPI": "AIzaSyCFgfotjA2Sbp6xYE5Izy5OQe-t9c_D7wM",  // Google Maps
  "sharedApiUrl": "https://shared.wrshah.com/data/api",
  "videoRecordingBitRate": 60000,
  "videoMaxRecordTime": 120000,
  "isBETA": false
}
```

### 1.3 المكتبات المحملة (من Network tab)
```
jquery.min.js                    // jQuery 3.x
bootstrap.min.js                 // Bootstrap 4.6
popper.min.js                    // Popper.js for tooltips
draggable.bundle.legacy.js       // Draggable (for modals/lists)
swap-animation.js                // Animation library
dropzone.min.js                  // File upload
prism.js                         // Syntax highlighting
autosize.min.js                  // Auto-resize textareas
list.min.js                      // List.js for filtering/sorting
theme.js                         // Custom theme
globalConfig.js                  // Config loader
```

---

## ثانياً: هيكل التطبيق (React Component Tree)

### 2.1 Root Level (من React Fiber)
```
InitialLoader → Loader → ThreeDots
ToastContainer
Provider (React Context)
  ErrorBoundary
    PageRemindersButton
    ReminderWatcher
    Menu
      Route → MenuContextProvider
      Route → TopNav + Content areas
Help (component)
```

### 2.2 Layout Structure (من DOM)
```html
<div id="root">
  <div class="layout layout-nav-side">
    <nav class="navbar navbar-expand-lg sticky-top navbar-dark bg-dark">
      <!-- Top Navigation -->
    </nav>
    <div class="collapse navbar-collapse flex-column">
      <ul class="navbar-nav d-lg-block">
        <!-- Sidebar Navigation -->
      </ul>
    </div>
    <main class="main-container">
      <!-- Page Content -->
    </main>
  </div>
</div>
```

---

## ثالثاً: المسارات (Routes) المكتشفة

| المسار | العنوان | الوحدة |
|---|---|---|
| `/home` | الصفحة الرئيسية | Dashboard |
| `/tickets/openTickets` | كروت الصيانة المفتوحة | Workshop |
| `/tickets/closedTickets` | كروت مكتملة | Workshop |
| `/invoices/salesInvoices` | الفواتير المباعة | Invoices |
| `/invoices/purchaseInvoices` | فواتير المشتريات | Purchasing |
| `/storeManagement/storeStock` | مخزون المتجر | Inventory |
| `/storeManagement/storePurchases` | مشتريات المتجر | Inventory |
| `/storeManagement/storeSales` | مبيعات المتجر | Inventory |
| `/storeManagement/stockActivities` | حركة المخزون | Inventory |
| `/hrManagement/employees` | الموظفين | HR |
| `/hrManagement/payroll` | الرواتب | HR |
| `/hrManagement/vacations` | الإجازات | HR |
| `/administration/companyProfile` | ملف الشركة | Admin |
| `/administration/shops` | مراكز الصيانة | Admin |
| `/administration/sparePartsCatalog` | كتالوج قطع الغيار | Admin |
| `/administration/systemSettings` | إعدادات النظام | Admin |
| `/reports/*` | 15+ تقارير | Reports |
| `/advance-search` | بحث متقدم | Global |
| `/userProfile` | الملف الشخصي | User |

---

## رابعاً: API Endpoints المكتشفة

### 4.1 من Network tab
| Endpoint | الوصف | الباراميترز |
|---|---|---|
| `/data/api/lookupData?shopId=2&userId=0` | تحميل البيانات المرجعية (lookups) | `shopId`, `userId` |
| `/data/api/notifications?userId=0&pageSize=15&page=1` | الإشعارات | pagination |
| `/data/api/home/ticketSummary?shopId=2` | ملخص الكروت للـ dashboard | `shopId` |

### 4.2 Shared API (من globalConfig)
```
Base: https://shared.wrshah.com/data/api
```

---

## خامساً: مكونات UI الرئيسية (من MUI Classes)

### 5.1 Navigation Components
| MUI Class | الاستخدام |
|---|---|
| `MuiSpeedDial-root` | زر الإجراءات العائم (FAB) |
| `MuiSpeedDial-fab` | الزر الرئيسي (Plus → Close) |
| `MuiSpeedDialAction-fab` | إجراءات SpeedDial |
| `MuiSpeedDialIcon-root` | أيقونة الفتح/الإغلاق |

### 5.2 Layout Components
| Bootstrap Class | الاستخدام |
|---|---|
| `navbar navbar-expand-lg sticky-top navbar-dark bg-dark` | Top navigation bar |
| `collapse navbar-collapse flex-column` | Sidebar container |
| `navbar-nav d-lg-block` | Navigation list |
| `main-container` | Content area |
| `layout layout-nav-side` | Root layout wrapper |

### 5.3 Data Display
| Class | الاستخدام |
|---|---|
| `content-list` | List container |
| `content-list-body` | List items |
| `card-body` | Card content |
| `chat-module` | Notifications/reminders sidebar |
| `infinite-scroll-component` | Paginated lists |

---

## سادساً: كود صفحة نموذجية (tickets/openTickets)

### 6.1 هيكل الصفحة
```jsx
// من DOM snapshot
<div className="layout layout-nav-side">
  <nav className="navbar navbar-expand-lg sticky-top navbar-dark bg-dark">
    <NavbarBrand />
    <TopNav />
  </nav>
  <div className="collapse navbar-collapse flex-column">
    <ul className="navbar-nav d-lg-block">
      <SidebarNavigation />
    </ul>
  </div>
  <main className="main-container">
    <div className="page-header">
      <h2>كروت الصيانة المفتوحة</h2>
    </div>
    <Tabs defaultActiveKey="open">
      <TabPane tabKey="open">المفتوحة (0)</TabPane>
      <TabPane tabKey="completed">مكتمل (0)</TabPane>
      <TabPane tabKey="overdue">تجاوز تاريخ الاستحقاق (0)</TabPane>
      <TabPane tabKey="due">المبالغ المستحقة (0)</TabPane>
      <TabPane tabKey="onHold">معلق (0)</TabPane>
    </Tabs>
    <FilterBar>
      <Select placeholder="تصنيف العميل" />
      <SearchInput placeholder="رقم كرت الصيانة" />
      <SortDropdown />
      <Button>إعادة تعيين الفلاتر</Button>
    </FilterBar>
    <ContentList data={[]} />  {/* Infinite scroll */}
    <SpeedDial>  {/* MUI Floating Action Button */}
      <SpeedDialAction icon="calendar-check" title="إضافة تذكير" />
      <SpeedDialAction icon="clipboard" title="الطلب" />
      <SpeedDialAction icon="star" title="تقييم" />
      <SpeedDialAction icon="car" title="المركبة" />
      <SpeedDialAction icon="user" title="العميل" />
      <SpeedDialAction icon="handshake" title="الشركاء" />
      <SpeedDialAction icon="truck" title="مورد" />
      <SpeedDialAction icon="file-invoice" title="فاتورة" />
    </SpeedDial>
  </main>
</div>
```

---

## سابعاً: تحليل صفحة الموظفين (hrManagement/employees)

### 7.1 فلاتر البحث
```jsx
<FilterBar>
  <Select placeholder="المتجر" />
  <SearchInput placeholder="الموظف/العامل" />
  <Select placeholder="مسمى الوظيفة" />
  <Select placeholder="الجنسية" />
  <Select placeholder="الصلاحيات" />
  <Select placeholder="مستند" />
  <Button>إعادة تعيين الفلاتر</Button>
</FilterBar>

<Tabs>
  <TabPane tabKey="active">فعال</TabPane>
  <TabPane tabKey="inactive">غير فعال</TabPane>
</Tabs>
```

### 7.2 حقول الموظف (من الفلاتر)
- المتجر (Shop/Center)
- الموظف/العامل
- مسمى الوظيفة
- الجنسية
- الصلاحيات
- مستند

---

## ثامناً: تحليل صفحة الرواتب (hrManagement/payroll)

### 8.1 تبويبات الرواتب
```jsx
<Tabs>
  <TabPane tabKey="monthly">الرواتب الشهرية</TabPane>
  <TabPane tabKey="other">المدفوعات الأخرى</TabPane>
</Tabs>
<Button>add</Button>  {/* إضافة راتب */}
```

---

## تاسعاً: صفحات الإدارة (Administration)

### 9.1 نظام الإعدادات (systemSettings) - 15 تبويب
| التبويب | الوصف |
|---|---|
| العلاوات | Allowances |
| ألوان السيارة | Vehicle Colors |
| الحسومات | Deductions/Discounts |
| الماركة | Vehicle Makes |
| الموديل | Vehicle Models |
| أنواع التذكير | Reminder Types |
| أدوار فني الخدمة | Service Technician Roles |
| الشروط والأحكام | Terms & Conditions |
| بادئات رسائل الواتساب | WhatsApp Prefixes |
| عناوين الوثائق | Document Titles |
| فئات حساب الدخل | Income Account Categories |
| فئات قطع الغيار | Spare Parts Categories |
| مسمى الوظيفة | Job Titles |
| نموذج حالة المركبة | Vehicle Condition Models |
| نوع الموظف | Employee Types |
| وحدات المخزون | Inventory Units |
| إعدادات أخرى | Other Settings |

---

## عاشراً: المقارنة التقنية النهائية

### wrshah.com vs carag-v2

| المعيار | wrshah.com | carag-v2 |
|---|---|---|
| **Frontend** | React + MUI + Bootstrap | Vue 3 + Inertia + Tailwind |
| **State** | React Context | Vue 3 ref/computed |
| **Routing** | React Router | Inertia.js (SSR) |
| **API** | REST `/data/api` | Laravel Routes + Inertia props |
| **Auth** | Session + JWT (في cookies) | Laravel Sanctum |
| **Build** | Webpack 5 | Vite |
| **UI Components** | MUI (Material-UI) | Custom + Headless UI |
| **CSS** | Bootstrap + CSS-in-JS | Tailwind CSS |
| **Icons** | Font Awesome + Material Icons | Heroicons (SVG) |
| **Printing** | غير واضح (ممكن server-side) | PrintEngine.vue (Client-side) |
| **Charts** | غير مرئي | Chart.js / Recharts |
| **Real-time** | `ReminderWatcher` polling | Laravel Echo / Pusher |

### ميزات wrshah.com البرمجية
| الميزة | الدليل |
|---|---|
| SpeedDial FAB | `MuiSpeedDial-root` في كل الصفحات |
| Chat/Reminders sidebar | `chat-module` classes |
| Infinite Scroll Lists | `infinite-scroll-component` |
| Multi-language | `defaultLanguage: "ar"` في config |
| Payment Widget | `oppwa.com` integration |
| Google Maps | Map API key في config |
| Video Recording | `videoRecordingBitRate` في config |
| Shared API | `shared.wrshah.com` subdomain |

---

## الخلاصة

**wrshah.com نظام React احترافي** مع:
- ✅ Material-UI (MUI v5) component library
- ✅ React Router + Context state management
- ✅ Webpack 5 with code splitting
- ✅ SpeedDial FAB UX pattern
- ✅ Infinite scroll pagination
- ✅ Chat/Reminders real-time sidebar
- ✅ Multi-tab pages with filters
- ✅ Shared API subdomain
- ✅ Payment gateway integration (OPPWA)
- ✅ Google Maps integration

**الفرق الجوهري عن carag-v2:**
- wrshah.com = **SPA React** (Client-side rendering)
- carag-v2 = **Inertia.js + Vue** (Server-driven SPA)

كلاهما enterprise-grade لكن بمعماريات مختلفة جذرياً.
