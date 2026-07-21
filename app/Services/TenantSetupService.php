<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HR\EmployeeType;
use App\Models\HR\JobTitle;
use App\Models\InventoryUnit;
use App\Support\Permissions;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantSetupService
{
    /**
     * Seeds default roles and permissions for a specific tenant.
     */
    public function seedRolesForTenant(int $tenantId): void
    {
        // Clear Spatie permissions cache to ensure newly migrated permissions are available
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Define default roles with their permissions
        $roles = $this->getDefaultRoles();

        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'tenant_id' => $tenantId,
                ],
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'tenant_id' => $tenantId,
                    'label_ar' => $roleData['label_ar'],
                    'label_en' => $roleData['label_en'],
                    'description' => $roleData['description'],
                ]
            );

            // Update existing roles if they exist to ensure new fields are populated
            if (! $role->wasRecentlyCreated) {
                $role->update([
                    'label_ar' => $roleData['label_ar'],
                    'label_en' => $roleData['label_en'],
                    'description' => $roleData['description'],
                ]);
            }

            // Sync permissions
            try {
                $role->syncPermissions($roleData['permissions']);
            } catch (PermissionDoesNotExist $e) {
                if (! app()->runningUnitTests()) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Seed default per-tenant lookup data: inventory units, employee types,
     * job titles. Idempotent — uses firstOrCreate keyed on
     * (tenant_id, name_ar) so re-runs won't create duplicates.
     *
     * Note: Nationalities are global (not tenant-scoped) and live in
     * `Database\Seeders\NationalitiesSeeder`. They are seeded once on
     * `php artisan db:seed`, not per-tenant.
     */
    public function seedDefaultsForTenant(int $tenantId): void
    {
        $this->seedInventoryUnits($tenantId);
        $this->seedEmployeeTypes($tenantId);
        $this->seedJobTitles($tenantId);
    }

    /**
     * Default inventory units (وحدات المخزون).
     * Per-tenant, so each tenant can rename/add/delete freely afterwards.
     */
    protected function seedInventoryUnits(int $tenantId): void
    {
        $units = $this->getDefaultInventoryUnits();

        foreach ($units as $unit) {
            InventoryUnit::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name_ar' => $unit['name_ar'],
                ],
                [
                    'tenant_id' => $tenantId,
                    'name_ar' => $unit['name_ar'],
                    'name_en' => $unit['name_en'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Default employee types (أنواع الموظفين).
     */
    protected function seedEmployeeTypes(int $tenantId): void
    {
        $types = $this->getDefaultEmployeeTypes();

        foreach ($types as $type) {
            EmployeeType::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name_ar' => $type['name_ar'],
                ],
                [
                    'tenant_id' => $tenantId,
                    'name_ar' => $type['name_ar'],
                    'name_en' => $type['name_en'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Default job titles (المسميات الوظيفية).
     * The `default_role_name` field maps each title to a system role
     * so that new employees with this title automatically receive the
     * corresponding role. See EmployeeObserver for the assignment logic.
     */
    protected function seedJobTitles(int $tenantId): void
    {
        $titles = $this->getDefaultJobTitles();

        foreach ($titles as $title) {
            JobTitle::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name_ar' => $title['name_ar'],
                ],
                [
                    'tenant_id' => $tenantId,
                    'name_ar' => $title['name_ar'],
                    'name_en' => $title['name_en'],
                    'default_role_name' => $title['default_role_name'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Default inventory units — bilingual AR/EN.
     *
     * @return array<int, array{name_ar: string, name_en: string}>
     */
    public function getDefaultInventoryUnits(): array
    {
        return [
            ['name_ar' => 'حبة',  'name_en' => 'Piece'],
        ];
    }

    /**
     * Default employee types — bilingual AR/EN.
     *
     * @return array<int, array{name_ar: string, name_en: string}>
     */
    public function getDefaultEmployeeTypes(): array
    {
        return [
            ['name_ar' => 'دائم',  'name_en' => 'Permanent'],
            ['name_ar' => 'مؤقت',  'name_en' => 'Temporary'],
        ];
    }

    /**
     * Default job titles — bilingual AR/EN with optional default role
     * mapping. Roles that don't exist yet are silently skipped
     * (the safety net lets this run on a fresh tenant before all
     * roles are seeded).
     *
     * @return array<int, array{name_ar: string, name_en: string, default_role_name?: string|null}>
     */
    public function getDefaultJobTitles(): array
    {
        return [
            ['name_ar' => 'إداري',     'name_en' => 'Admin',         'default_role_name' => 'receptionist'],
            ['name_ar' => 'بنشري',     'name_en' => 'Buncher',       'default_role_name' => 'technician'],
            ['name_ar' => 'سائق',      'name_en' => 'Driver',        'default_role_name' => 'technician'],
            ['name_ar' => 'سمكري',     'name_en' => 'Bodywork Tech', 'default_role_name' => 'technician'],
            ['name_ar' => 'ميكانيكي',  'name_en' => 'Mechanic',      'default_role_name' => 'technician'],
            ['name_ar' => 'كهربائي',   'name_en' => 'Electrician',   'default_role_name' => 'technician'],
            ['name_ar' => 'عامل',      'name_en' => 'Worker',        'default_role_name' => 'technician'],
            ['name_ar' => 'محاسب',     'name_en' => 'Accountant',    'default_role_name' => 'accountant'],
            ['name_ar' => 'مدير',      'name_en' => 'Manager',       'default_role_name' => 'branch_manager'],
            ['name_ar' => 'مشرف',      'name_en' => 'Supervisor',    'default_role_name' => 'branch_manager'],
        ];
    }

    /**
     * Get default roles definitions.
     *
     * Every role's `permissions` array is deduplicated on the way out
     * to prevent pastes like `Permissions::HR_LEAVES_VIEW` showing up
     * twice (which would have caused Spatie to call
     * `syncPermissions()` with the same permission twice and spam
     * the pivot table). The authoritative list of permissions is
     * still in `App\Support\Permissions`.
     *
     * @return array<string, array{label_ar:string,label_en:string,description:string,permissions:array<int,string>}>
     */
    public function getDefaultRoles(): array
    {
        $raw = [
            'super_admin' => [
                'label_ar' => 'مدير عام',
                'label_en' => 'Super Admin',
                'description' => 'كامل الصلاحيات - الوصول لجميع أقسام النظام',
                'permissions' => Permissions::all(), // All permissions
            ],
            'branch_manager' => [
                'label_ar' => 'مدير فرع',
                'label_en' => 'Branch Manager',
                'description' => 'إدارة الفرع - صلاحيات تشغيلية كاملة داخل الفرع',
                'permissions' => [
                    // CRM - Customers (no delete)
                    Permissions::CUSTOMERS_VIEW,
                    Permissions::CUSTOMERS_CREATE,
                    Permissions::CUSTOMERS_UPDATE,
                    // CRM - Vehicles (no delete)
                    Permissions::VEHICLES_VIEW,
                    Permissions::VEHICLES_CREATE,
                    Permissions::VEHICLES_UPDATE,
                    Permissions::VEHICLE_SETTINGS_VIEW,
                    // CRM - Work Orders (no delete)
                    Permissions::WORK_ORDERS_VIEW,
                    Permissions::WORK_ORDERS_CREATE,
                    Permissions::WORK_ORDERS_UPDATE,
                    // Quotes (all)
                    Permissions::QUOTES_VIEW,
                    Permissions::QUOTES_CREATE,
                    Permissions::QUOTES_UPDATE,
                    Permissions::QUOTES_APPROVE,
                    // Services (view only)
                    Permissions::SERVICES_VIEW,
                    Permissions::DEPARTMENTS_VIEW,
                    // Invoices
                    Permissions::INVOICES_VIEW,
                    Permissions::INVOICES_CREATE,
                    // HR (View only)
                    Permissions::HR_VIEW,
                    Permissions::HR_EMPLOYEES_VIEW,
                    Permissions::HR_ATTENDANCE_VIEW,
                    Permissions::HR_LEAVES_VIEW,
                    Permissions::HR_LEAVES_APPROVE,
                    // Payroll & Payments
                    // NOTE: branch_manager is operational, not admin,
                    // so DELETE on payroll/payments is intentionally
                    // omitted. The hr role keeps full delete rights.
                    Permissions::HR_PAYROLL_VIEW,
                    Permissions::HR_PAYROLL_CREATE,
                    Permissions::HR_PAYROLL_UPDATE,
                    Permissions::HR_PAYMENTS_VIEW,
                    Permissions::HR_PAYMENTS_CREATE,
                    Permissions::HR_PAYMENTS_UPDATE,
                    Permissions::HR_PAYMENTS_APPROVE,
                ],
            ],
            'receptionist' => [
                'label_ar' => 'موظف استقبال',
                'label_en' => 'Receptionist',
                'description' => 'الاستقبال - استقبال العملاء وإنشاء كروت العمل وعروض الأسعار',
                'permissions' => [
                    // CRM - Customers (create/edit only)
                    Permissions::CUSTOMERS_VIEW,
                    Permissions::CUSTOMERS_CREATE,
                    Permissions::CUSTOMERS_UPDATE,
                    // CRM - Vehicles (create/edit only)
                    Permissions::VEHICLES_VIEW,
                    Permissions::VEHICLES_CREATE,
                    Permissions::VEHICLES_UPDATE,
                    // CRM - Work Orders (create only)
                    Permissions::WORK_ORDERS_VIEW,
                    Permissions::WORK_ORDERS_CREATE,
                    // Quotes (create/edit)
                    Permissions::QUOTES_VIEW,
                    Permissions::QUOTES_CREATE,
                    Permissions::QUOTES_UPDATE,
                    // Services (view for pricing)
                    Permissions::SERVICES_VIEW,
                    Permissions::DEPARTMENTS_VIEW,
                ],
            ],
            'technician' => [
                'label_ar' => 'فني',
                'label_en' => 'Technician',
                'description' => 'فني صيانة - مشاهدة كروت العمل المسندة فقط',
                'permissions' => [
                    // Work Orders (view and update status only)
                    Permissions::WORK_ORDERS_VIEW,
                    Permissions::WORK_ORDERS_UPDATE,
                    // Vehicles (view for inspection)
                    Permissions::VEHICLES_VIEW,
                    // Services (view for reference)
                    Permissions::SERVICES_VIEW,
                ],
            ],
            'accountant' => [
                'label_ar' => 'محاسب',
                'label_en' => 'Accountant',
                'description' => 'المالية - إدارة الفواتير والمدفوعات والموردين',
                'permissions' => [
                    // Customers (view only)
                    Permissions::CUSTOMERS_VIEW,
                    // Vehicles (view only)
                    Permissions::VEHICLES_VIEW,
                    // Work Orders (view only)
                    Permissions::WORK_ORDERS_VIEW,
                    // Invoices (full access)
                    Permissions::INVOICES_VIEW,
                    Permissions::INVOICES_CREATE,
                    Permissions::INVOICES_EXTRA_DISCOUNT,
                    // Purchasing
                    Permissions::SUPPLIERS_VIEW,
                    Permissions::SUPPLIERS_CREATE,
                    Permissions::SUPPLIERS_UPDATE,
                    Permissions::SUPPLIERS_DESTROY,
                    // Payroll & Payments (Financials)
                    Permissions::HR_PAYROLL_VIEW,
                    Permissions::HR_PAYMENTS_VIEW,
                    Permissions::HR_PAYMENTS_APPROVE,
                ],
            ],
            'hr' => [
                'label_ar' => 'موارد بشرية',
                'label_en' => 'HR Manager',
                'description' => 'الموارد البشرية - إدارة الموظفين والرواتب',
                'permissions' => [
                    Permissions::HR_VIEW,
                    Permissions::HR_SETTINGS_MANAGE,
                    Permissions::HR_EMPLOYEES_VIEW,
                    Permissions::HR_EMPLOYEES_CREATE,
                    Permissions::HR_EMPLOYEES_UPDATE,
                    Permissions::HR_EMPLOYEES_DELETE,
                    Permissions::HR_ATTENDANCE_VIEW,
                    Permissions::HR_ATTENDANCE_MANAGE,
                    Permissions::HR_LEAVES_VIEW,
                    Permissions::HR_LEAVES_MANAGE,
                    Permissions::HR_LEAVES_APPROVE,
                    // Payroll & Payments
                    Permissions::HR_PAYROLL_VIEW,
                    Permissions::HR_PAYROLL_CREATE,
                    Permissions::HR_PAYROLL_UPDATE,
                    Permissions::HR_PAYROLL_DELETE,
                    Permissions::HR_PAYMENTS_VIEW,
                    Permissions::HR_PAYMENTS_CREATE,
                    Permissions::HR_PAYMENTS_UPDATE,
                    Permissions::HR_PAYMENTS_DELETE,
                    Permissions::HR_PAYMENTS_APPROVE,
                ],
            ],
            'employee' => [
                'label_ar' => 'موظف',
                'label_en' => 'Employee',
                'description' => 'موظف - الوصول للبوابة الذاتية فقط (معلوماته الشخصية، حضوره، إجازاته، رواتبه)',
                'permissions' => [
                    Permissions::EMPLOYEE_PROFILE_VIEW,
                    Permissions::EMPLOYEE_ATTENDANCE_VIEW,
                    Permissions::EMPLOYEE_LEAVES_VIEW,
                    Permissions::EMPLOYEE_LEAVES_REQUEST,
                    Permissions::EMPLOYEE_PAYSLIPS_VIEW,
                    Permissions::EMPLOYEE_REQUESTS_CREATE,
                ],
            ],
        ];

        // Deduplicate each role's permission list. Past edits left
        // duplicates in branch_manager / accountant / hr, which
        // would have produced duplicate pivot rows.
        foreach ($raw as $name => $data) {
            $raw[$name]['permissions'] = array_values(array_unique($data['permissions']));
        }

        return $raw;
    }
}
