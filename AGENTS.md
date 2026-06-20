# 🤖 Carag V2 - Agent Team

هذا الملف يشرح هيكل المشروع لمطورين الذكاء الاصطناعي (Mavis Agents).

## هيكل المشروع

```
carag-v2/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── App/           # Controllers التطبيق
│   │   │   ├── Api/           # API Controllers
│   │   │   ├── Auth/          # Authentication
│   │   │   ├── Public/        # Public routes
│   │   │   └── System/        # System management
│   │   ├── Middleware/        # Custom middleware
│   │   └── Requests/          # Form requests
│   ├── Models/                # Eloquent models
│   ├── Services/              # Business logic services
│   ├── Actions/               # Action classes
│   └── Enums/                 # Enum classes
├── database/
│   ├── migrations/           # Database migrations
│   ├── seeders/             # Database seeders
│   └── factories/            # Model factories
├── resources/
│   └── js/
│       ├── Pages/            # Vue components (Inertia)
│       ├── Components/       # Reusable Vue components
│       └── Layouts/          # Layout components
├── routes/
│   ├── web.php              # Web routes (~300 routes)
│   ├── api.php              # API routes
│   └── auth.php             # Auth routes
└── tests/
    ├── Feature/             # Feature tests (13 tests)
    └── Unit/               # Unit tests (3 tests)
```

## الوحدات الرئيسية (Modules)

### 1. العملاء والمركبات (Customers & Vehicles)
- **Controllers:** `CustomerController`, `VehicleController`
- **Models:** `Customer`, `Vehicle`
- **Features:** استيراد/تصدير XLSX، merge customers، تتبع المركبات

### 2. أوامر العمل (Work Orders)
- **Controller:** `WorkOrderController`
- **Models:** `WorkOrder`, `WorkOrderItem`, `WorkOrderPhoto`, `WorkOrderInspection`
- **Services:** `WorkOrderPartsService`
- **Features:** إدارة الخدمات، القطع، الفحوصات، التوقيع، الصور
- **Composables:** `useWorkOrderItems`, `useWorkOrderStatus`, `useWorkOrderNotes`

#### WorkOrders Page Architecture

**File:** `resources/js/Pages/WorkOrders/Show.vue` (~893 lines)

Show.vue هو الصفحة الرئيسية لأمر العمل. أُنشئ كـ composable-first refactor:

**الـ Components المستخرجة (`Components/WorkOrders/`):**

| Component | يحتوي |
|---|---|
| `WorkOrderHeader.vue` | Status buttons, print, edit, back URL, status badge |
| `WorkOrderCustomerCard.vue` | Plate, customer, WhatsApp, VIN, odometer, getColorHex |
| `WorkOrderFinancialSummary.vue` | Services/parts/grand totals + paid + balance |
| `WorkOrderInfoCards.vue` | Entry / expected end / duration, with overdue detection |
| `WorkOrderComplaintAssessment.vue` | Customer complaint + initial assessment |
| `WorkOrderTabsContainer.vue` | Tab bar + v-model dispatcher |
| `WorkOrderServicesTab.vue` | Departments accordion + service items + add/remove/edit |
| `WorkOrderNotesTab.vue` | Notes list/grid with search + add/delete |
| `WorkOrderPhotosTab.vue` | Photos grid with hover overlay + view/download/delete |
| `WorkOrderActivitiesTab.vue` | Activity timeline with icons |
| `WorkOrderAttachmentsTab.vue` | File grid with size + view/delete |

**الـ Composables (`Composables/`):**

| Composable | يحتوي |
|---|---|
| `useWorkOrderItems.js` | `displayDepartments`, `availableDepartments`, `addDepartment`, `removeDepartment`, `toggleDepartment`, `getDepartmentItems` |
| `useWorkOrderStatus.js` | `isReadOnly`, `changeStatus`, `showExitModal`, `showHoldModal`, exit/hold handlers |
| `useWorkOrderNotes.js` | `allNotes`, `showServiceModal`, `showItemModal`, service modal plumbing, `handleAddNote`, `handleDeleteNote` |

**Magic Strings (sentinel keys):**
```javascript
import { PACKAGES_DEPT_KEY, UNASSIGNED_DEPT_KEY } from '@/Composables/useWorkOrderItems';
// PACKAGES_DEPT_KEY = 'packages'    // virtual bucket for service bundles
// UNASSIGNED_DEPT_KEY = '0'         // legacy/unassigned items
```

**Contract Pattern:**
- Components تستقبل data كـ Props
- Components ت发射 events للخارج
- Composables تحتفظ بالـ reactive state و business logic
- Show.vue يستدعي composables ويستخدم القيم مباشرة في الـ template

**TODO:**
- [ ] استكمال partial reloads (الأصناف اللي تحتاج `only: ['workOrder', 'itemsByDepartment']`)
- [ ] WorkOrderController refactor (1200+ lines)

### 3. عروض الأسعار (Quotes)
- **Controller:** `QuoteController`, `QuoteApprovalController`
- **Models:** `Quote`, `QuoteLine`, `QuotePart`
- **Features:** موافقات عبر روابط عامة

### 4. الفواتير والمدفوعات (Invoices & Payments)
- **Controllers:** `InvoicesController`, `PaymentsController`
- **Models:** `Invoice`, `InvoiceLine`, `Payment`
- **Services:** `InvoiceService`, `PaymentService`, `Billing`
- **Features:** ZATCA integration، QR codes، طباعة حرارية

### 5. المشتريات (Purchasing)
- **Controllers:** `PurchaseOrdersController`, `GoodsReceivedNotesController`
- **Models:** `PurchaseOrder`, `PurchaseInvoice`, `Supplier`
- **Features:** طلبات شراء، استلام بضائع (GRN)

### 6. المخزون (Inventory)
- **Controllers:** `InventoryBalanceController`, `InventoryMoveController`
- **Models:** `Part`, `Warehouse`, `InventoryBalance`, `InventoryMove`
- **Services:** `WorkOrderPartsService`
- **Features:** تتبع stock، transfers، adjustments

### 7. الموارد البشرية (HR)
- **Controllers:** `HR/HRController`, `PayrollController`, `AttendanceController`
- **Models:** `HR/Employee`, `HR/Attendance`, `HR/PayrollRun`
- **Features:** رواتب، حضور/غياب، إجازات، عقود

### 8. لوحة الموظف (Employee Portal)
- **Route:** `/app/my/*` (requires 'employee' role)
- **Features:** حضور، إجازات، كشوف رواتب، ملفي الشخصي

## قواعد المساهمة

### عند العمل على هذا المشروع:

1. **التأكيد من وجود tenant_id:**
```php
// دائماً استخدم:
$workOrder->tenant_id
$request->user()->tenant_id

// وليس:
Tenant::current()->id
```

2. **Center Context:**
```php
// يجب وجود center_id في العمليات
$workOrder->center_id
```

3. **Authorization:**
```php
// دائماً استخدم:
$this->authorize('action', Model::class);
```

4. **Logging:**
```php
// عند العمليات المهمة:
$workOrder->logActivity('action', 'description');
```

5. **Print Engine:**
```php
// استخدم PrintEngine الموحد:
Inertia::render('Invoices/Print/TemplateName', [...])
```

## أنماط الكود

### Controller Pattern:
```php
class ExampleController
{
    use AuthorizesRequests;

    public function index(): Response { /* ... */ }
    public function store(Request $request): RedirectResponse { /* ... */ }
    public function show(Model $model): Response { /* ... */ }
    public function update(Request $request, Model $model): RedirectResponse { /* ... */ }
    public function destroy(Model $model): RedirectResponse { /* ... */ }
}
```

### Service Pattern:
```php
class ExampleService
{
    public function doSomething(array $data): Model
    {
        // Business logic here
    }
}
```

### Action Pattern:
```php
class CreateExampleAction
{
    public function execute(User $user, array $data): Model
    {
        // Implementation
    }
}
```

## المسارات الرئيسية (Routes)

### Public Routes:
- `/view/quote/{uuid}` - عرض عرض سعر عام

### Authenticated Routes:
- `/app/*` - Application routes (requires 2FA)
- `/app/my/*` - Employee portal

### Admin Routes:
- `/app/settings/*` - Settings management
- `/app/hr/*` - HR management

## الاختبارات

- **Location:** `tests/Feature/` و `tests/Unit/`
- **Test Results:** 106 passed / 11 pre-existing failed (TaxCalculationTest rounding)
- **Test Database:** SQLite

## Environment Variables

```env
APP_NAME=Carag
APP_ENV=local
APP_KEY=
DB_CONNECTION=mysql
MAIL_MAILER=smtp
QUEUE_CONNECTION=redis
```

## ملاحظات مهمة للمطورين

1. **RTL Support:** المشروع يدعم RTL (العربية)
2. **Multi-tenancy:** كل مستأجر له data isolation
3. **2FA:** المصادقة الثنائية إلزامية
4. **ZATCA:** التكامل مع نظام الفوترة السعودي

---

*آخر تحديث: 2026-06-18*