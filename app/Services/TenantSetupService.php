<?php

namespace App\Services;

use App\Support\Permissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantSetupService
{
    /**
     * Seeds default roles and permissions for a specific tenant.
     *
     * @param int $tenantId
     * @return void
     */
    public function seedRolesForTenant(int $tenantId): void
    {
        // Define default roles with their permissions
        $roles = $this->getDefaultRoles();

        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                [
                    'name' => $roleName, 
                    'guard_name' => 'web',
                    'tenant_id' => $tenantId
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
            if (!$role->wasRecentlyCreated) {
                $role->update([
                    'label_ar' => $roleData['label_ar'],
                    'label_en' => $roleData['label_en'],
                    'description' => $roleData['description'],
                ]);
            }

            // Sync permissions
            $role->syncPermissions($roleData['permissions']);
        }
    }

    /**
     * Get default roles definitions.
     * 
     * @return array
     */
    public function getDefaultRoles(): array
    {
        return [
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
                    Permissions::HR_LEAVES_VIEW,
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
        ];
    }
}
