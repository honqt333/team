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
- **Controller:** `WorkOrderController` (1200+ lines - needs refactor)
- **Models:** `WorkOrder`, `WorkOrderItem`, `WorkOrderPhoto`, `WorkOrderInspection`
- **Services:** `WorkOrderPartsService`
- **Features:** إدارة الخدمات، القطع، الفحوصات، التوقيع، الصور

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
- **Coverage الحالي:** ~15% ( يحتاج تحسين )
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

*آخر تحديث: 2026-05-31*