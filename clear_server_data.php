<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tablesToTruncate = [
    // Customers & Vehicles
    'customers',
    'vehicles',

    // Invoices & Payments
    'invoices',
    'invoice_lines',
    'payments',
    'company_transactions',
    'purchase_invoices',
    'purchase_invoice_lines',
    'purchase_return_invoices',
    'purchase_return_invoice_lines',

    // Work Orders & Quotes
    'work_orders',
    'work_order_items',
    'work_order_item_parts',
    'work_order_item_technician',
    'work_order_departments',
    'work_order_damage_marks',
    'work_order_activities',
    'work_order_photos',
    'work_order_attachments',
    'work_order_item_notes',
    'vehicle_mileage_logs',
    'quotes',
    'quote_lines',
    'quote_parts',
    'quote_departments',

    // Inspections / Assessments
    'work_order_inspections',

    // Employees & HR
    'hr_employees',
    'hr_employee_contracts',
    'hr_employee_documents',
    'hr_attendances',
    'hr_leaves',
    'hr_payroll_runs',
    'hr_payroll_items',
    'hr_other_payments',
    'hr_employee_types',
    'hr_job_titles',

    // Parts & Inventory
    'parts',
    'inventory_balances',
    'inventory_moves',
    'inventory_transfers',
    'inventory_transfer_items',
    'inventory_units',
    'goods_received_notes',
    'grn_items',
    'purchase_orders',
    'purchase_order_items',
    'suppliers',

    // Notifications
    'internal_notifications',
];

echo "Wiping tables...\n";
DB::transaction(function () use ($tablesToTruncate) {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    foreach ($tablesToTruncate as $table) {
        DB::table($table)->truncate();
        echo "- Truncated: $table\n";
    }
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
});
echo "Database clean complete!\n";
