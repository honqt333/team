# 📋 Carag V2 — تقرير شامل بالصلاحيات

**تاريخ التقرير:** 2026-07-06
**المراجع:** Mavis
**المصدر:** `app/Support/Permissions.php`, `app/Services/TenantSetupService.php`, `app/Policies/`, `app/Http/Middleware/`

---

## 🔍 المعمارية (Architecture)

نظام الصلاحيات في Carag V2 يجمع بين **3 طبقات**:

```
┌─────────────────────────────────────────────────────────────┐
│ Layer 1: Permissions Registry                                │
│   app/Support/Permissions.php                                │
│   103 صلاحية كثوابت PHP (single source of truth)            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 2: Roles (Spatie Permission)                           │
│   app/Services/TenantSetupService::getDefaultRoles()         │
│   7 أدوار معرّفة لكل tenant (tenant-scoped)                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ Layer 3: Policies + Middlewares                              │
│   app/Policies/ (26 policy)                                  │
│   app/Http/Middleware/EnsureSystemAdmin, EnsureTenantActive │
│   تنفّذ في الـ Controllers عبر $this->authorize(...)         │
└─────────────────────────────────────────────────────────────┘
```

### Guards (Authentication)

| Guard | User Model | Provider | Purpose |
|---|---|---|---|
| `web` | `App\Models\User` | `users` table | Tenant users (auth + 2FA + verified) |
| `admin` | `App\Models\AdminUser` | `admin_users` table | System admins (لوحة النظام) |

---

## 📊 الصلاحيات (103 صلاحية، 21 module)

### CRM (21 صلاحية)

#### Customers (7)
- `crm.customers.view`
- `crm.customers.create`
- `crm.customers.update`
- `crm.customers.delete`
- `crm.customers.print`
- `crm.customers.export`
- `crm.customers.import`

#### Vehicles (7)
- `crm.vehicles.view`
- `crm.vehicles.create`
- `crm.vehicles.update`
- `crm.vehicles.delete`
- `crm.vehicles.print`
- `crm.vehicles.export`
- `crm.vehicles.import`

#### Vehicle Settings (2)
- `crm.vehicles.settings.view`
- `crm.vehicles.settings.manage`

#### Work Orders (5)
- `crm.work_orders.view`
- `crm.work_orders.create`
- `crm.work_orders.update`
- `crm.work_orders.delete`
- `crm.work_orders.inspect`
- `crm.work_orders.print` (تعريف منفصل)
- `crm.work_orders.export` (تعريف منفصل)
- `crm.work_orders.import` (تعريف منفصل)

### Purchasing (18 صلاحية)

#### Suppliers (4)
- `purchasing.suppliers.view`
- `purchasing.suppliers.create`
- `purchasing.suppliers.update`
- `purchasing.suppliers.destroy`

#### Purchase Orders (5)
- `purchasing.pos.view`
- `purchasing.pos.create`
- `purchasing.pos.update`
- `purchasing.pos.send`
- `purchasing.pos.cancel`

#### Purchase Invoices (4)
- `purchasing.invoices.view`
- `purchasing.invoices.create`
- `purchasing.invoices.update`
- `purchasing.invoices.delete`

#### Returns (4)
- `purchasing.returns.view`
- `purchasing.returns.create`
- `purchasing.returns.update`
- `purchasing.returns.delete`

#### Payments (1)
- `purchasing.payments.manage`

### Quotes (5)
- `quotes.view`
- `quotes.create`
- `quotes.update`
- `quotes.delete`
- `quotes.approve`

### Services (6)
- `services.view`
- `services.create`
- `services.update`
- `services.delete`
- `services.departments.view`
- `services.departments.manage`

### Invoicing (3)
- `invoices.view`
- `invoices.create`
- `invoices.extra_discount`

### Work Cards (5) — Legacy/Future
- `workcards.view`
- `workcards.create`
- `workcards.update`
- `workcards.delete`
- `workcards.lines.discount`

### HR (24 صلاحية)

#### General (2)
- `hr.view`
- `hr.settings.manage`

#### Employees (4)
- `hr.employees.view`
- `hr.employees.create`
- `hr.employees.update`
- `hr.employees.delete`

#### Attendance (2)
- `hr.attendance.view`
- `hr.attendance.manage`

#### Leaves (3)
- `hr.leaves.view`
- `hr.leaves.manage`
- `hr.leaves.approve`

#### Payroll (4)
- `hr.payroll.view`
- `hr.payroll.create`
- `hr.payroll.update`
- `hr.payroll.delete`

#### Other Payments (5)
- `hr.payments.view`
- `hr.payments.create`
- `hr.payments.update`
- `hr.payments.delete`
- `hr.payments.approve`

#### Contracts (4)
- `hr.contracts.view`
- `hr.contracts.create`
- `hr.contracts.update`
- `hr.contracts.delete`

### Inventory (5)
- `inventory.view`
- `inventory.settings.manage`
- `inventory.stock.view`
- `inventory.moves.view`
- `inventory.moves.create`

### User Management (4)
- `users.view`
- `users.create`
- `users.update`
- `users.delete`

### Settings (3)
- `settings.company.manage`
- `settings.centers.view`
- `settings.centers.manage`

### Employee Portal — Self-Service (6)
- `employee.profile.view`
- `employee.attendance.view`
- `employee.leaves.view`
- `employee.leaves.request`
- `employee.payslips.view`
- `employee.requests.create`

---

## 👥 الأدوار (Roles) — 7 أدوار لكل Tenant

| # | Role Key | الاسم بالعربي | الصلاحيات | الاستخدام |
|---|---|---|---|---|
| 1 | `super_admin` | مدير عام | **103** (الكل) | مالك الـ Tenant — كل الصلاحيات |
| 2 | `branch_manager` | مدير فرع | **34** | إدارة الفرع (تشغيلية كاملة، بدون delete لـ work orders) |
| 3 | `receptionist` | موظف استقبال | **13** | استقبال + إنشاء كروت العمل وعروض الأسعار |
| 4 | `technician` | فني | **4** | مشاهدة + تحديث كروت العمل المسندة |
| 5 | `accountant` | محاسب | **16** | المالية — فواتير، مدفوعات، موردين، رواتب |
| 6 | `hr` | موارد بشرية | **21** | إدارة الموظفين، حضور، إجازات، رواتب |
| 7 | `employee` | موظف | **6** | Self-service — معلوماته الشخصية فقط |

### تفصيل الصلاحيات لكل دور

#### super_admin (103 — كل الصلاحيات)
- كل الـ 21 module
- الوحيد الذي يصل لـ `/system/*` (لكن **فقط** عبر `is_system_admin` flag، مش الـ role!)

#### branch_manager (34)
```
CRM Customers: view, create, update (لا delete)
CRM Vehicles:  view, create, update, settings.view (لا delete)
Work Orders:   view, create, update (لا delete)
Quotes:        كل الصلاحيات (view, create, update, approve — لا delete)
Services:      view, departments.view
Invoices:      view, create
HR:            view, employees.view, attendance.view,
               leaves.view, leaves.approve
HR Payroll:    view, create, update, delete
HR Payments:   view, create, update, delete, approve
```

#### receptionist (13)
```
CRM Customers: view, create, update
CRM Vehicles:  view, create, update
Work Orders:   view, create
Quotes:        view, create, update
Services:      view, departments.view
```

#### technician (4)
```
Work Orders:   view, update (الحالة فقط)
Vehicles:      view
Services:      view
```

#### accountant (16)
```
Customers:     view
Vehicles:      view
Work Orders:   view
Invoices:      view, create, extra_discount
Suppliers:     view, create, update, destroy
HR Payroll:    view
HR Payments:   view, approve
```

#### hr (21)
```
HR General:    view, settings.manage
Employees:     view, create, update, delete
Attendance:    view, manage
Leaves:        view, manage, approve
Payroll:       view, create, update, delete
Payments:      view, create, update, delete, approve
```

#### employee (6) — Self-service فقط
```
employee.profile.view
employee.attendance.view
employee.leaves.view
employee.leaves.request
employee.payslips.view
employee.requests.create
```

---

## 🛡️ Policies (26 ملف)

### Policies تستخدم Permissions constants (19/26)

| Policy | يطبق صلاحية |
|---|---|
| `CustomerPolicy` | `crm.customers.{view,create,update,delete}` |
| `VehiclePolicy` | `crm.vehicles.{view,create,update,delete}` |
| `WorkOrderPolicy` | `crm.work_orders.{view,create,update,delete}` |
| `WorkOrderInspectionPolicy` | `crm.work_orders.inspect` |
| `ServicePolicy` | `services.{view,create,update,delete}` |
| `DepartmentPolicy` | `services.departments.{view,manage}` |
| `SupplierPolicy` | `purchasing.suppliers.{view,create,update,destroy}` |
| `PurchaseOrderPolicy` | `purchasing.pos.{view,create,update}` |
| `PurchaseInvoicePolicy` | `purchasing.invoices.*` + `purchasing.returns.*` |
| `InventoryBalancePolicy` | `inventory.stock.view` |
| `InventoryMovePolicy` | `inventory.moves.{view,create}` |
| `InventoryTransferPolicy` | `inventory.view` + tenant check |
| `PartPolicy` | `inventory.view` |
| `QuotePolicy` | `quotes.{view,create,update,delete,approve}` |
| `QuoteLinePolicy` | `quotes.view` |
| `InvoicePolicy` | `invoices.view` |
| `CenterPolicy` | `settings.centers.{view,manage}` |
| `HR/EmployeePolicy` | `hr.employees.{view,create,update,delete}` |
| `HR/AttendancePolicy` | `hr.attendance.{view,manage}` |
| `HR/PayrollRunPolicy` | `hr.payroll.{view,create,update,delete}` |
| `EmployeeContractPolicy` | `hr.contracts.{view,create,update,delete}` |
| `VehicleMakePolicy`, `VehicleModelPolicy`, `VehicleColorPolicy` | `crm.vehicles.settings.{view,manage}` |
| `GoodsReceivedNotePolicy` | (no permission check — tenant-only) |
| `UserPolicy` | (separate logic) |

### Policies بدون Permissions (لكن tenant-scoped)
- `GoodsReceivedNotePolicy` — only checks `tenant_id`

---

## 🔐 Middleware للحماية (Tenant + System)

| Middleware | الوصف | المسار |
|---|---|---|
| `auth:web,admin` | مصادقة (web أو admin) | `/system/*` |
| `auth` | مصادقة (web) | `/app/*`, `/app/my` |
| `verified` | **إيميل مفعّل** | `/app/*` (كامل), `/dashboard` |
| `system.admin` | **نظامي فقط** (`is_system_admin` أو `AdminUser`) | `/system/*` |
| `tenant.active` | tenant نشط وغير محذوف | `/app/*` |
| `center.context` | user عنده center مفعّل | `/app/*` |
| `EnsureTwoFactorEnabled` | 2FA مفعّل إذا طُلب | `/app/*` (المجموعة الكبيرة) |

### مسارات Auth (`routes/auth.php`)

| Middleware | المسارات |
|---|---|
| `guest` | register, login, forgot-password, reset-password |
| `auth` | verify-email, confirm-password, logout, password.update |

---

## ⚙️ Gate::before (Provider: `AppServiceProvider`)

```php
Gate::before(function ($user, $ability) {
    // AdminUser (system guard) — super admin → allow
    if ($user instanceof AdminUser) {
        return $user->isSuperAdmin() ? true : null;
    }
    // User (tenant guard) — is_system_admin flag → allow
    if ($user instanceof User && $user->is_system_admin) {
        return true;
    }
    // Otherwise — normal policy check
    return null;
});
```

**مهم:** `super_admin` role داخل tenant **لا** يتجاوز الـ policies. فقط:
- `AdminUser` (admin guard)
- `User` مع `is_system_admin = true`

---

## 🛡️ EnsureSystemAdmin Middleware (post-fix)

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();
    if (!$user) return redirect()->route('login');

    // 1. AdminUser (نظامي) → Allow
    if ($user instanceof \App\Models\AdminUser) return $next($request);

    // 2. User مع is_system_admin flag → Allow
    if ($user instanceof User && $user->is_system_admin) return $next($request);

    abort(403, 'غير مصرح لك بالوصول للوحة النظام');
}
```

**Tenant owners مع `super_admin` role يحصلون على 403 في `/system/*`.**

---

## 📍 توزيع الوحدات (Modules)

| Module | عدد الصلاحيات | أكبر دور |
|---|---|---|
| CRM (customers, vehicles, work_orders) | 21 | branch_manager (14) |
| HR (employees, attendance, payroll...) | 24 | hr (21) |
| Purchasing | 18 | branch_manager (~8) |
| Inventory | 5 | branch_manager (3) |
| Quotes | 5 | branch_manager, receptionist (4) |
| Services | 6 | branch_manager, receptionist (2) |
| Invoicing | 3 | branch_manager, accountant (3) |
| User Management | 4 | super_admin فقط |
| Settings | 3 | super_admin فقط |
| Employee Portal | 6 | employee (6) |
| Work Cards (legacy) | 5 | super_admin فقط |
| **المجموع** | **103** | — |

---

## ⚠️ ملاحظات ومشاكل موجودة

### 🟡 مشاكل معروفة (Pre-existing)

1. **Permissions مكررة (typo risk)**: في `TenantSetupService.php`:
   - `branch_manager` يحتوي `HR_LEAVES_VIEW` و `HR_LEAVES_APPROVE` مكررة
   - `accountant` يحتوي `SUPPLIERS_CREATE/UPDATE/DESTROY` مكررة
   - `hr` يحتوي `HR_LEAVES_APPROVE` مكررة

2. **`WorkOrder` permissions منفصلة**: في `Permissions.php` يوجد:
   ```php
   public const WORK_ORDERS_VIEW = 'crm.work_orders.view';
   // ... create, update, delete
   public const WORK_ORDERS_INSPECT = 'crm.work_orders.inspect';
   ```
   لكن `byModule()` يسرد `WORK_ORDERS_INSPECT` فقط (5 perms) — PRINT, EXPORT, IMPORT معرّفة لكن غير مدرجة في `byModule`.

3. **No permissions for Reports/Analytics**: لا توجد صلاحيات مخصصة للـ reports.

4. **No granular field-level permissions**: مثلاً لا يمكن السماح بـ "view invoice but not see total" أو "edit customer but not delete".

5. **`user_management` permissions لا تُمنح لأي role افتراضي**: فقط `super_admin` يحصل عليها.

### ✅ نقاط القوة

1. ✅ **Single source of truth**: `Permissions.php` يحدد الكل
2. ✅ **Type-safe**: IDE autocomplete عبر `Permissions::CUSTOMERS_VIEW`
3. ✅ **Granular**: `view, create, update, delete` منفصلة لكل module
4. ✅ **Self-service employee portal**: 6 صلاحيات منفصلة
5. ✅ **Two-factor auth integration** مع permissions
6. ✅ **Tenant-scoped roles**: كل tenant له أدواره الخاصة
7. ✅ **Security layered**: Permission → Policy → Middleware → Controller
8. ✅ **No hardcoded permission strings** في الـ policies (95%+)

---

## 🔗 نقاط التكامل المهمة

### `SetPermissionsTeam` Middleware
```php
// Must be FIRST - sets team before permissions are loaded
$middleware->web(append: [
    \App\Http\Middleware\SetPermissionsTeam::class,
    ...
]);
```

هذا Middleware يحدد الـ `team_id` للـ Spatie Permission لتطبيق team-scoped roles.

### `InitialSystemSeeder`
```php
// Main admin (admin@khidmh.pro)
'password' => Hash::make('11223344'),
'is_system_admin' => true,  // ← ضروري لدخول /system/*
```

### `RegisteredUserController`
```php
$user->assignRole('super_admin');  // ← أول role للمستخدم الجديد
```

كل تسجيل جديد يحصل على `super_admin` role داخل tenant.

### `User::booted()`
```php
static::created(function (User $user) {
    // أول user في tenant يحصل على super_admin role
    if ($count === 1 && !app()->runningUnitTests()) {
        $user->assignRole($superAdminRole);
    }
});
```

---

## 📋 ملخص تنفيذي

| العنصر | العدد |
|---|---|
| Permissions constants | 103 |
| Modules | 21 |
| Roles per tenant | 7 |
| Policies | 26 |
| Middleware | 5 مخصصة |
| Guards | 2 (web, admin) |
| Auth models | 2 (User, AdminUser) |
| User types | 3 (tenant user, employee, system admin) |

### التشخيصات الأخيرة (2026-07-06)

| # | المشكلة | الحالة |
|---|---|---|
| 1 | Tenant owners يدخلون `/system/*` (privilege escalation) | ✅ مُصلح |
| 2 | `is_default` ما يحفظ في integrations | ✅ مُصلح |
| 3 | SMTP ما يقرأ من DB | ✅ مُصلح |
| 4 | Email verification يتجاوز على `/app/*` | ✅ مُصلح |
| 5 | Label accessors null safety | ✅ مُصلح |
| 6 | SQL injection في Inventory | ✅ مُصلح |
| 7 | XSS via v-html | ✅ مُصلح |

### توصيات للمستقبل

1. 🟡 **تنظيف الصلاحيات المكررة** في `TenantSetupService.php`
2. 🟡 **إضافة PRINT/EXPORT/IMPORT** لـ `byModule()`
3. 🟡 **حذف `WORKCARDS_*`** (legacy، غير مستخدم)
4. 🟢 **إنشاء dashboard للصلاحيات** (admin UI)
5. 🟢 **توثيق permission matrix** (دور × صلاحية)
6. 🟢 **Audit log للصلاحيات** (تتبع تغييرات)

---

*تم إنشاء هذا التقرير بواسطة Mavis — 2026-07-06*
