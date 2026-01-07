<?php

namespace App\Support;

/**
 * Centralized Permissions Registry
 * 
 * This class defines ALL permissions used in the application.
 * Use these constants in policies instead of hardcoded strings to:
 * 1. Prevent typos
 * 2. Enable IDE autocomplete
 * 3. Make permission refactoring easier
 * 4. Provide a single source of truth
 */
class Permissions
{
    // ========================================
    // CRM MODULE
    // ========================================
    
    /** Customers */
    public const CUSTOMERS_VIEW = 'crm.customers.view';
    public const CUSTOMERS_CREATE = 'crm.customers.create';
    public const CUSTOMERS_UPDATE = 'crm.customers.update';
    public const CUSTOMERS_DELETE = 'crm.customers.delete';
    public const CUSTOMERS_PRINT = 'crm.customers.print';
    public const CUSTOMERS_EXPORT = 'crm.customers.export';
    public const CUSTOMERS_IMPORT = 'crm.customers.import';
    
    /** Vehicles */
    public const VEHICLES_VIEW = 'crm.vehicles.view';
    public const VEHICLES_CREATE = 'crm.vehicles.create';
    public const VEHICLES_UPDATE = 'crm.vehicles.update';
    public const VEHICLES_DELETE = 'crm.vehicles.delete';
    public const VEHICLES_PRINT = 'crm.vehicles.print';
    public const VEHICLES_EXPORT = 'crm.vehicles.export';
    public const VEHICLES_IMPORT = 'crm.vehicles.import';
    
    /** Vehicle Settings (Makes, Models, Colors) */
    public const VEHICLE_SETTINGS_VIEW = 'crm.vehicles.settings.view';
    public const VEHICLE_SETTINGS_MANAGE = 'crm.vehicles.settings.manage';
    
    /** Work Orders */
    public const WORK_ORDERS_VIEW = 'crm.work_orders.view';
    public const WORK_ORDERS_CREATE = 'crm.work_orders.create';
    public const WORK_ORDERS_UPDATE = 'crm.work_orders.update';
    public const WORK_ORDERS_DELETE = 'crm.work_orders.delete';
    public const WORK_ORDERS_PRINT = 'crm.work_orders.print';
    public const WORK_ORDERS_EXPORT = 'crm.work_orders.export';
    public const WORK_ORDERS_IMPORT = 'crm.work_orders.import';

    // ========================================
    // PURCHASING MODULE
    // ========================================

    /** Suppliers */
    public const SUPPLIERS_VIEW = 'purchasing.suppliers.view';
    public const SUPPLIERS_CREATE = 'purchasing.suppliers.create';
    public const SUPPLIERS_UPDATE = 'purchasing.suppliers.update';
    public const SUPPLIERS_DESTROY = 'purchasing.suppliers.destroy';
    
    // ========================================
    // QUOTES MODULE
    // ========================================
    
    public const QUOTES_VIEW = 'quotes.view';
    public const QUOTES_CREATE = 'quotes.create';
    public const QUOTES_UPDATE = 'quotes.update';
    public const QUOTES_DELETE = 'quotes.delete';
    public const QUOTES_APPROVE = 'quotes.approve';
    
    // ========================================
    // SERVICES MODULE
    // ========================================
    
    /** Services */
    public const SERVICES_VIEW = 'services.view';
    public const SERVICES_CREATE = 'services.create';
    public const SERVICES_UPDATE = 'services.update';
    public const SERVICES_DELETE = 'services.delete';
    
    /** Departments */
    public const DEPARTMENTS_VIEW = 'services.departments.view';
    public const DEPARTMENTS_MANAGE = 'services.departments.manage';
    
    // ========================================
    // INVOICING MODULE
    // ========================================
    
    public const INVOICES_VIEW = 'invoices.view';
    public const INVOICES_CREATE = 'invoices.create';
    public const INVOICES_EXTRA_DISCOUNT = 'invoices.extra_discount';
    
    // ========================================
    // WORK CARDS MODULE (Legacy/Future)
    // ========================================
    
    public const WORKCARDS_VIEW = 'workcards.view';
    public const WORKCARDS_CREATE = 'workcards.create';
    public const WORKCARDS_UPDATE = 'workcards.update';
    public const WORKCARDS_DELETE = 'workcards.delete';
    public const WORKCARDS_LINES_DISCOUNT = 'workcards.lines.discount';

    // ========================================
    // HR MODULE
    // ========================================
    
    /** HR Dashboard */
    public const HR_VIEW = 'hr.view';
    
    /** Employees */
    public const HR_EMPLOYEES_VIEW = 'hr.employees.view';
    public const HR_EMPLOYEES_CREATE = 'hr.employees.create';
    public const HR_EMPLOYEES_UPDATE = 'hr.employees.update';
    public const HR_EMPLOYEES_DELETE = 'hr.employees.delete';
    
    /** Attendance */
    public const HR_ATTENDANCE_VIEW = 'hr.attendance.view';
    public const HR_ATTENDANCE_MANAGE = 'hr.attendance.manage';
    
    /** Leaves */
    public const HR_LEAVES_VIEW = 'hr.leaves.view';
    public const HR_LEAVES_MANAGE = 'hr.leaves.manage';
    public const HR_LEAVES_APPROVE = 'hr.leaves.approve';
    
    /** Settings */
    public const HR_SETTINGS_MANAGE = 'hr.settings.manage';
    
    /** Payroll */
    public const HR_PAYROLL_VIEW = 'hr.payroll.view';
    public const HR_PAYROLL_CREATE = 'hr.payroll.create'; // Also implies manage/calculate
    public const HR_PAYROLL_UPDATE = 'hr.payroll.update';
    public const HR_PAYROLL_DELETE = 'hr.payroll.delete';
    
    /** Other Payments */
    public const HR_PAYMENTS_VIEW = 'hr.payments.view';
    public const HR_PAYMENTS_CREATE = 'hr.payments.create';
    public const HR_PAYMENTS_UPDATE = 'hr.payments.update';
    public const HR_PAYMENTS_DELETE = 'hr.payments.delete';
    public const HR_PAYMENTS_APPROVE = 'hr.payments.approve';

    // ========================================
    // INVENTORY MODULE
    // ========================================

    public const INVENTORY_VIEW = 'inventory.view';
    public const INVENTORY_SETTINGS_MANAGE = 'inventory.settings.manage';
    // Future permissions placeholders
    public const INVENTORY_STOCK_VIEW = 'inventory.stock.view';
    public const INVENTORY_MOVES_VIEW = 'inventory.moves.view';
    public const INVENTORY_MOVES_CREATE = 'inventory.moves.create';

    // ========================================
    // USER MANAGEMENT
    // ========================================
    
    public const USERS_VIEW = 'users.view';
    public const USERS_CREATE = 'users.create';
    public const USERS_UPDATE = 'users.update';
    public const USERS_DELETE = 'users.delete';

    // ========================================
    // TENANT SETTINGS
    // ========================================

    /** Company Profile */
    public const COMPANY_MANAGE = 'settings.company.manage';

    /** Centers (Branches) */
    public const CENTERS_VIEW = 'settings.centers.view';
    public const CENTERS_MANAGE = 'settings.centers.manage';

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get all permission constants as an array.
     * Useful for seeding and documentation.
     * 
     * @return array<string>
     */
    public static function all(): array
    {
        $reflection = new \ReflectionClass(self::class);
        return array_values($reflection->getConstants());
    }

    /**
     * Get permissions grouped by module.
     * 
     * @return array<string, array<string>>
     */
    public static function byModule(): array
    {
        return [
            'settings_general' => [
                self::COMPANY_MANAGE,
                self::CENTERS_VIEW,
                self::CENTERS_MANAGE,
            ],
            'crm_customers' => [
                self::CUSTOMERS_VIEW,
                self::CUSTOMERS_CREATE,
                self::CUSTOMERS_UPDATE,
                self::CUSTOMERS_DELETE,
                self::CUSTOMERS_PRINT,
                self::CUSTOMERS_EXPORT,
                self::CUSTOMERS_IMPORT,
            ],
            'crm_vehicles' => [
                self::VEHICLES_VIEW,
                self::VEHICLES_CREATE,
                self::VEHICLES_UPDATE,
                self::VEHICLES_DELETE,
                self::VEHICLES_PRINT,
                self::VEHICLES_EXPORT,
                self::VEHICLES_IMPORT,
                self::VEHICLE_SETTINGS_VIEW,
                self::VEHICLE_SETTINGS_MANAGE,
            ],
            'crm_work_orders' => [
                self::WORK_ORDERS_VIEW,
                self::WORK_ORDERS_CREATE,
                self::WORK_ORDERS_UPDATE,
                self::WORK_ORDERS_DELETE,
            ],
            'quotes' => [
                self::QUOTES_VIEW,
                self::QUOTES_CREATE,
                self::QUOTES_UPDATE,
                self::QUOTES_DELETE,
                self::QUOTES_APPROVE,
            ],
            'services' => [
                self::SERVICES_VIEW,
                self::SERVICES_CREATE,
                self::SERVICES_UPDATE,
                self::SERVICES_DELETE,
                self::DEPARTMENTS_VIEW,
                self::DEPARTMENTS_MANAGE,
            ],
            'invoices' => [
                self::INVOICES_VIEW,
                self::INVOICES_CREATE,
                self::INVOICES_EXTRA_DISCOUNT,
            ],
            'purchasing_suppliers' => [
                self::SUPPLIERS_VIEW,
                self::SUPPLIERS_CREATE,
                self::SUPPLIERS_UPDATE,
                self::SUPPLIERS_DESTROY,
            ],
            'workcards' => [
                self::WORKCARDS_VIEW,
                self::WORKCARDS_CREATE,
                self::WORKCARDS_UPDATE,
                self::WORKCARDS_DELETE,
                self::WORKCARDS_LINES_DISCOUNT,
            ],
            'hr_general' => [
                self::HR_VIEW,
                self::HR_SETTINGS_MANAGE,
            ],
            'hr_employees' => [
                self::HR_EMPLOYEES_VIEW,
                self::HR_EMPLOYEES_CREATE,
                self::HR_EMPLOYEES_UPDATE,
                self::HR_EMPLOYEES_DELETE,
            ],
            'hr_attendance' => [
                self::HR_ATTENDANCE_VIEW,
                self::HR_ATTENDANCE_MANAGE,
            ],
            'hr_leaves' => [
                self::HR_LEAVES_VIEW,
                self::HR_LEAVES_MANAGE,
                self::HR_LEAVES_APPROVE,
            ],
            'hr_payroll' => [
                self::HR_PAYROLL_VIEW,
                self::HR_PAYROLL_CREATE,
                self::HR_PAYROLL_UPDATE,
                self::HR_PAYROLL_DELETE,
            ],
            'hr_payments' => [
                self::HR_PAYMENTS_VIEW,
                self::HR_PAYMENTS_CREATE,
                self::HR_PAYMENTS_UPDATE,
                self::HR_PAYMENTS_DELETE,
                self::HR_PAYMENTS_APPROVE,
            ],
            'user_management' => [
                self::USERS_VIEW,
                self::USERS_CREATE,
                self::USERS_UPDATE,
                self::USERS_DELETE,
            ],
            'inventory' => [
                self::INVENTORY_VIEW,
                self::INVENTORY_SETTINGS_MANAGE,
                self::INVENTORY_STOCK_VIEW,
                self::INVENTORY_MOVES_VIEW,
                self::INVENTORY_MOVES_CREATE,
            ],
        ];
    }

    /**
     * Get readable description for a permission.
     * 
     * @param string $permission
     * @return string
     */
    public static function describe(string $permission): string
    {
        return __('permissions.' . str_replace('.', '_', $permission));
    }
}
