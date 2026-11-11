<?php

declare(strict_types=1);

namespace App\Support;

use ReflectionClass;

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

    /** Work Order Inspections */
    public const WORK_ORDERS_INSPECT = 'crm.work_orders.inspect';

    public const APPOINTMENTS_MANAGE = 'crm.appointments.manage';

    // ========================================
    // PURCHASING MODULE
    // ========================================

    /** Suppliers */
    public const SUPPLIERS_VIEW = 'purchasing.suppliers.view';

    public const SUPPLIERS_CREATE = 'purchasing.suppliers.create';

    public const SUPPLIERS_UPDATE = 'purchasing.suppliers.update';

    public const SUPPLIERS_DESTROY = 'purchasing.suppliers.destroy';

    /** Purchase Orders */
    public const POS_VIEW = 'purchasing.pos.view';

    public const POS_CREATE = 'purchasing.pos.create';

    public const POS_UPDATE = 'purchasing.pos.update';

    public const POS_SEND = 'purchasing.pos.send';

    public const POS_CANCEL = 'purchasing.pos.cancel';

    /** Purchase Invoices */
    public const PURCHASE_INVOICES_VIEW = 'purchasing.invoices.view';

    public const PURCHASE_INVOICES_CREATE = 'purchasing.invoices.create';

    public const PURCHASE_INVOICES_UPDATE = 'purchasing.invoices.update';

    public const PURCHASE_INVOICES_DELETE = 'purchasing.invoices.delete';

    /** Purchase Returns */
    public const RETURNS_VIEW = 'purchasing.returns.view';

    public const RETURNS_CREATE = 'purchasing.returns.create';

    public const RETURNS_UPDATE = 'purchasing.returns.update';

    public const RETURNS_DELETE = 'purchasing.returns.delete';

    /** Purchase Payments / Refunds */
    public const PURCHASE_PAYMENTS_MANAGE = 'purchasing.payments.manage';

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

    /** Employee Contracts */
    public const HR_CONTRACTS_VIEW = 'hr.contracts.view';

    public const HR_CONTRACTS_CREATE = 'hr.contracts.create';

    public const HR_CONTRACTS_UPDATE = 'hr.contracts.update';

    public const HR_CONTRACTS_DELETE = 'hr.contracts.delete';

    // ========================================
    // PHASE 0 POLICY PERMISSIONS
    // ========================================

    /** Payments Policy Permissions */
    public const PAYMENTS_VIEW = 'payments.view';

    public const PAYMENTS_CREATE = 'payments.create';

    public const PAYMENTS_UPDATE = 'payments.update';

    public const PAYMENTS_DELETE = 'payments.delete';

    public const PAYMENTS_REFUND = 'payments.refund';

    /** Leaves Policy Permissions */
    public const LEAVES_VIEW = 'leaves.view';

    public const LEAVES_VIEW_ALL = 'leaves.view-all';

    public const LEAVES_CREATE = 'leaves.create';

    public const LEAVES_APPROVE = 'leaves.approve';

    /** Payroll Policy Permissions */
    public const PAYROLL_VIEW = 'payroll.view';

    public const PAYROLL_PROCESS = 'payroll.process';

    public const PAYROLL_APPROVE = 'payroll.approve';

    public const PAYROLL_DISBURSE = 'payroll.disburse';

    /** Work Order Items Policy Permissions */
    public const WORK_ORDER_ITEMS_VIEW = 'work-order-items.view';

    public const WORK_ORDER_ITEMS_CREATE = 'work-order-items.create';

    public const WORK_ORDER_ITEMS_UPDATE = 'work-order-items.update';

    public const WORK_ORDER_ITEMS_DELETE = 'work-order-items.delete';

    // ========================================
    // EMPLOYEE PORTAL (Self-Service)
    // ========================================

    /** Employee can view their own profile/data */
    public const EMPLOYEE_PROFILE_VIEW = 'employee.profile.view';

    /** Employee can view their own attendance records */
    public const EMPLOYEE_ATTENDANCE_VIEW = 'employee.attendance.view';

    /** Employee can view their own leaves */
    public const EMPLOYEE_LEAVES_VIEW = 'employee.leaves.view';

    /** Employee can request leaves */
    public const EMPLOYEE_LEAVES_REQUEST = 'employee.leaves.request';

    /** Employee can view their own payslips */
    public const EMPLOYEE_PAYSLIPS_VIEW = 'employee.payslips.view';

    /** Employee can submit general requests */
    public const EMPLOYEE_REQUESTS_CREATE = 'employee.requests.create';

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

    /** Tenant public website (Phase 6 — site settings + publishing) */
    public const WEBSITE_VIEW = 'settings.website.view';

    public const WEBSITE_MANAGE = 'settings.website.manage';

    /** Cloud Storage & System Attachments Management */
    public const ATTACHMENTS_VIEW = 'settings.attachments.view';

    public const ATTACHMENTS_DELETE = 'settings.attachments.delete';

    // ========================================
    // SYSTEM PANEL (/system)
    //
    // The platform-admin panel. These abilities are NOT Spatie
    // permissions: they are resolved by the system permission
    // registry (App\Support\SystemPermissions) against the role and
    // the permission list stored on the admin account.
    //
    // The naming convention is `<group>.<action>` — the same shape the
    // tenant-side permissions use, so the two live side by side
    // without ambiguity.
    //
    // Grouping mirrors SystemPermissions::GROUPS, which is the single
    // place the registry maps an ability to its display group.
    // ========================================

    /** Tenants — viewing the tenant list and a tenant's detail */
    public const SYSTEM_TENANTS_VIEW = 'system.tenants.view';

    /** Tenants — lifecycle changes: suspend, activate, extend trial, dunning */
    public const SYSTEM_TENANTS_MANAGE = 'system.tenants.manage';

    /** Tenants — hard delete (the most destructive action in the panel) */
    public const SYSTEM_TENANTS_DELETE = 'system.tenants.delete';

    /** Tenants — impersonate a tenant user */
    public const SYSTEM_TENANTS_IMPERSONATE = 'system.tenants.impersonate';

    /** Billing — viewing subscriptions and invoices */
    public const SYSTEM_SUBSCRIPTIONS_VIEW = 'system.subscriptions.view';

    /** Billing — creating, cancelling and extending subscriptions */
    public const SYSTEM_SUBSCRIPTIONS_MANAGE = 'system.subscriptions.manage';

    /** Billing — viewing invoices */
    public const SYSTEM_INVOICES_VIEW = 'system.invoices.view';

    /** Billing — cancelling invoices, marking them paid, sending them */
    public const SYSTEM_INVOICES_MANAGE = 'system.invoices.manage';

    /** Billing — the plan catalogue */
    public const SYSTEM_PLANS_MANAGE = 'system.plans.manage';

    /** Billing — promo codes */
    public const SYSTEM_PROMOCODES_MANAGE = 'system.promocodes.manage';

    /** Billing — payment gateway configuration (holds secret keys) */
    public const SYSTEM_PAYMENT_SETTINGS = 'system.payment.settings';

    /** Billing — retrying a failed payment */
    public const SYSTEM_PAYMENT_RETRY = 'system.payment.retry';

    /** Settings — the platform settings pages (general, company, help, website, dunning) */
    public const SYSTEM_SETTINGS_MANAGE = 'system.settings.manage';

    /** Settings — third-party integrations */
    public const SYSTEM_INTEGRATIONS_MANAGE = 'system.integrations.manage';

    /** Settings — platform announcements broadcast to tenants */
    public const SYSTEM_ANNOUNCEMENTS_MANAGE = 'system.announcements.manage';

    /** Settings — communication templates (email / SMS bodies) */
    public const SYSTEM_TEMPLATES_MANAGE = 'system.templates.manage';

    /** Support — tenant feedback tickets */
    public const SYSTEM_FEEDBACKS_MANAGE = 'system.feedbacks.manage';

    /** Support — contact messages from the public site */
    public const SYSTEM_CONTACT_MESSAGES_MANAGE = 'system.contact_messages.manage';

    /** Credits — SMS and WhatsApp credit balances and packages */
    public const SYSTEM_CREDITS_MANAGE = 'system.credits.manage';

    /** Panel — the admin notification list */
    public const SYSTEM_NOTIFICATIONS_MANAGE = 'system.notifications.manage';

    /** Panel — the developer centre (audit runner, dependency graph, AI advice) */
    public const SYSTEM_DEVELOPER_ACCESS = 'system.developer.access';

    /** Panel — reading the admin roster (a diagnostic surface) */
    public const SYSTEM_ADMIN_USERS_VIEW = 'system.admin_users.view';

    /** Panel — creating, editing and deleting system-admin accounts */
    public const SYSTEM_ADMIN_USERS_MANAGE = 'system.admin_users.manage';

    /** Panel — reading the admin activity log */
    public const SYSTEM_ACTIVITY_LOG_VIEW = 'system.activity_log.view';

    /** Self — the signed-in admin's own profile and password */
    public const SYSTEM_PROFILE_MANAGE = 'system.profile.manage';

    /** Self — the signed-in admin's own two-factor authentication */
    public const SYSTEM_SECURITY_2FA_MANAGE = 'system.security.2fa.manage';

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get all permission constants as an array.
     *
     * This includes BOTH the tenant-side permissions and the system
     * panel's abilities. Use `tenantPermissions()` when the list is
     * going to a tenant role — handing a tenant owner a system-panel
     * ability would be a privilege escalation, since every new signup
     * is assigned the tenant-scoped `super_admin` role.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        $reflection = new ReflectionClass(self::class);

        return array_values($reflection->getConstants());
    }

    /**
     * The permissions that belong to a TENANT role.
     *
     * Everything except the system panel's abilities.
     *
     * @return array<string>
     */
    public static function tenantPermissions(): array
    {
        $system = SystemPermissions::names();

        return array_values(array_filter(
            self::all(),
            fn (string $permission) => ! in_array($permission, $system, true)
        ));
    }

    /**
     * The permissions that belong to the SYSTEM panel.
     *
     * @return array<string>
     */
    public static function systemPermissions(): array
    {
        return SystemPermissions::names();
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
                self::WEBSITE_VIEW,
                self::WEBSITE_MANAGE,
                self::ATTACHMENTS_VIEW,
                self::ATTACHMENTS_DELETE,
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
                self::WORK_ORDERS_INSPECT,
                self::WORK_ORDERS_PRINT,
                self::WORK_ORDERS_EXPORT,
                self::WORK_ORDERS_IMPORT,
                self::APPOINTMENTS_MANAGE,
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
            'purchasing_pos' => [
                self::POS_VIEW,
                self::POS_CREATE,
                self::POS_UPDATE,
                self::POS_SEND,
                self::POS_CANCEL,
            ],
            'purchasing_invoices' => [
                self::PURCHASE_INVOICES_VIEW,
                self::PURCHASE_INVOICES_CREATE,
                self::PURCHASE_INVOICES_UPDATE,
                self::PURCHASE_INVOICES_DELETE,
                self::PURCHASE_PAYMENTS_MANAGE,
            ],
            'purchasing_returns' => [
                self::RETURNS_VIEW,
                self::RETURNS_CREATE,
                self::RETURNS_UPDATE,
                self::RETURNS_DELETE,
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
                self::HR_CONTRACTS_VIEW,
                self::HR_CONTRACTS_CREATE,
                self::HR_CONTRACTS_UPDATE,
                self::HR_CONTRACTS_DELETE,
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
            'employee_portal' => [
                self::EMPLOYEE_PROFILE_VIEW,
                self::EMPLOYEE_ATTENDANCE_VIEW,
                self::EMPLOYEE_LEAVES_VIEW,
                self::EMPLOYEE_LEAVES_REQUEST,
                self::EMPLOYEE_PAYSLIPS_VIEW,
                self::EMPLOYEE_REQUESTS_CREATE,
            ],
            'phase_0_policies' => [
                self::PAYMENTS_VIEW,
                self::PAYMENTS_CREATE,
                self::PAYMENTS_UPDATE,
                self::PAYMENTS_DELETE,
                self::PAYMENTS_REFUND,
                self::LEAVES_VIEW,
                self::LEAVES_VIEW_ALL,
                self::LEAVES_CREATE,
                self::LEAVES_APPROVE,
                self::PAYROLL_VIEW,
                self::PAYROLL_PROCESS,
                self::PAYROLL_APPROVE,
                self::PAYROLL_DISBURSE,
                self::WORK_ORDER_ITEMS_VIEW,
                self::WORK_ORDER_ITEMS_CREATE,
                self::WORK_ORDER_ITEMS_UPDATE,
                self::WORK_ORDER_ITEMS_DELETE,
            ],
        ];
    }

    /**
     * Get readable description for a permission.
     */
    public static function describe(string $permission): string
    {
        return __('permissions.'.str_replace('.', '_', $permission));
    }
}
