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
    
    /** Vehicles */
    public const VEHICLES_VIEW = 'crm.vehicles.view';
    public const VEHICLES_CREATE = 'crm.vehicles.create';
    public const VEHICLES_UPDATE = 'crm.vehicles.update';
    public const VEHICLES_DELETE = 'crm.vehicles.delete';
    
    /** Vehicle Settings (Makes, Models, Colors) */
    public const VEHICLE_SETTINGS_VIEW = 'crm.vehicles.settings.view';
    public const VEHICLE_SETTINGS_MANAGE = 'crm.vehicles.settings.manage';
    
    /** Work Orders */
    public const WORK_ORDERS_VIEW = 'crm.work_orders.view';
    public const WORK_ORDERS_CREATE = 'crm.work_orders.create';
    public const WORK_ORDERS_UPDATE = 'crm.work_orders.update';
    public const WORK_ORDERS_DELETE = 'crm.work_orders.delete';
    
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
            'CRM - Customers' => [
                self::CUSTOMERS_VIEW,
                self::CUSTOMERS_CREATE,
                self::CUSTOMERS_UPDATE,
                self::CUSTOMERS_DELETE,
            ],
            'CRM - Vehicles' => [
                self::VEHICLES_VIEW,
                self::VEHICLES_CREATE,
                self::VEHICLES_UPDATE,
                self::VEHICLES_DELETE,
                self::VEHICLE_SETTINGS_VIEW,
                self::VEHICLE_SETTINGS_MANAGE,
            ],
            'CRM - Work Orders' => [
                self::WORK_ORDERS_VIEW,
                self::WORK_ORDERS_CREATE,
                self::WORK_ORDERS_UPDATE,
                self::WORK_ORDERS_DELETE,
            ],
            'Quotes' => [
                self::QUOTES_VIEW,
                self::QUOTES_CREATE,
                self::QUOTES_UPDATE,
                self::QUOTES_DELETE,
                self::QUOTES_APPROVE,
            ],
            'Services' => [
                self::SERVICES_VIEW,
                self::SERVICES_CREATE,
                self::SERVICES_UPDATE,
                self::SERVICES_DELETE,
                self::DEPARTMENTS_VIEW,
                self::DEPARTMENTS_MANAGE,
            ],
            'Invoices' => [
                self::INVOICES_VIEW,
                self::INVOICES_CREATE,
                self::INVOICES_EXTRA_DISCOUNT,
            ],
            'Work Cards (Legacy)' => [
                self::WORKCARDS_VIEW,
                self::WORKCARDS_CREATE,
                self::WORKCARDS_UPDATE,
                self::WORKCARDS_DELETE,
                self::WORKCARDS_LINES_DISCOUNT,
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
        $descriptions = [
            // CRM - Customers
            self::CUSTOMERS_VIEW => 'View customer records',
            self::CUSTOMERS_CREATE => 'Create new customers',
            self::CUSTOMERS_UPDATE => 'Edit customer information',
            self::CUSTOMERS_DELETE => 'Delete customers',
            
            // CRM - Vehicles
            self::VEHICLES_VIEW => 'View vehicle records',
            self::VEHICLES_CREATE => 'Register new vehicles',
            self::VEHICLES_UPDATE => 'Edit vehicle information',
            self::VEHICLES_DELETE => 'Delete vehicles',
            self::VEHICLE_SETTINGS_VIEW => 'View vehicle makes/models/colors',
            self::VEHICLE_SETTINGS_MANAGE => 'Manage vehicle makes/models/colors',
            
            // CRM - Work Orders
            self::WORK_ORDERS_VIEW => 'View work orders',
            self::WORK_ORDERS_CREATE => 'Create new work orders',
            self::WORK_ORDERS_UPDATE => 'Edit work orders',
            self::WORK_ORDERS_DELETE => 'Delete work orders',
            
            // Quotes
            self::QUOTES_VIEW => 'View quotes',
            self::QUOTES_CREATE => 'Create new quotes',
            self::QUOTES_UPDATE => 'Edit quotes',
            self::QUOTES_DELETE => 'Delete quotes',
            self::QUOTES_APPROVE => 'Approve/reject quotes',
            
            // Services
            self::SERVICES_VIEW => 'View service catalog',
            self::SERVICES_CREATE => 'Add new services',
            self::SERVICES_UPDATE => 'Edit services',
            self::SERVICES_DELETE => 'Delete services',
            self::DEPARTMENTS_VIEW => 'View departments',
            self::DEPARTMENTS_MANAGE => 'Create/edit/delete departments',
            
            // Invoices
            self::INVOICES_VIEW => 'View invoices',
            self::INVOICES_CREATE => 'Create invoices',
            self::INVOICES_EXTRA_DISCOUNT => 'Apply extra discounts to invoices',
        ];

        return $descriptions[$permission] ?? $permission;
    }
}
