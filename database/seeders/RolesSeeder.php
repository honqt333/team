<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates default system roles with their permissions.
     * These roles serve as templates for tenant-specific roles.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define default roles with their permissions
        $roles = [
            'super_admin' => [
                'ar' => 'مدير عام',
                'en' => 'Super Admin',
                'description' => 'Full system access - all permissions',
                'permissions' => Permissions::all(), // All permissions
            ],
            'admin' => [
                'ar' => 'مدير',
                'en' => 'Admin',
                'description' => 'Administrative access - most permissions except system settings',
                'permissions' => [
                    // CRM - Customers
                    Permissions::CUSTOMERS_VIEW,
                    Permissions::CUSTOMERS_CREATE,
                    Permissions::CUSTOMERS_UPDATE,
                    Permissions::CUSTOMERS_DELETE,
                    // CRM - Vehicles
                    Permissions::VEHICLES_VIEW,
                    Permissions::VEHICLES_CREATE,
                    Permissions::VEHICLES_UPDATE,
                    Permissions::VEHICLES_DELETE,
                    Permissions::VEHICLE_SETTINGS_VIEW,
                    Permissions::VEHICLE_SETTINGS_MANAGE,
                    // CRM - Work Orders
                    Permissions::WORK_ORDERS_VIEW,
                    Permissions::WORK_ORDERS_CREATE,
                    Permissions::WORK_ORDERS_UPDATE,
                    Permissions::WORK_ORDERS_DELETE,
                    // Quotes
                    Permissions::QUOTES_VIEW,
                    Permissions::QUOTES_CREATE,
                    Permissions::QUOTES_UPDATE,
                    Permissions::QUOTES_DELETE,
                    Permissions::QUOTES_APPROVE,
                    // Services
                    Permissions::SERVICES_VIEW,
                    Permissions::SERVICES_CREATE,
                    Permissions::SERVICES_UPDATE,
                    Permissions::SERVICES_DELETE,
                    Permissions::DEPARTMENTS_VIEW,
                    Permissions::DEPARTMENTS_MANAGE,
                    // Invoices
                    Permissions::INVOICES_VIEW,
                    Permissions::INVOICES_CREATE,
                    Permissions::INVOICES_EXTRA_DISCOUNT,
                ],
            ],
            'manager' => [
                'ar' => 'مدير فرع',
                'en' => 'Branch Manager',
                'description' => 'Branch management - operational permissions without deletion',
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
                ],
            ],
            'receptionist' => [
                'ar' => 'موظف استقبال',
                'en' => 'Receptionist',
                'description' => 'Front desk operations - quotes and work order creation',
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
                'ar' => 'فني',
                'en' => 'Technician',
                'description' => 'Technical work only - view assigned work orders, no financial data',
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
                'ar' => 'محاسب',
                'en' => 'Accountant',
                'description' => 'Financial operations - invoices and customer financials',
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
                ],
            ],
        ];

        // Create roles
        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['name' => $roleName, 'guard_name' => 'web']
            );

            // Sync permissions
            $role->syncPermissions($roleData['permissions']);

            $this->command->info("Role '{$roleName}' created with " . count($roleData['permissions']) . ' permissions.');
        }

        $this->command->info('');
        $this->command->info('Default roles seeded successfully!');
    }
}
